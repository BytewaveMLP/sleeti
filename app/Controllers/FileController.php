<?php

namespace Eeti\Controllers;

use Respect\Validation\Validator as v;
use Eeti\Models\File;
use Eeti\Models\User;
use Eeti\Auth\Auth;
use Eeti\Exceptions\FailedUploadException;

class FileController extends Controller
{
	/**
	 * Handles file uploads from clients
	 * @param  \Eeti\Models\User $user    The user to associate with the uploaded file
	 */
	private function handleFileUpload($request, $user) {
		$files = $request->getUploadedFiles();
		$ext = null;

		// If file upload fails, explain why
		if (!isset($files['file']) || $files['file']->getError() !== UPLOAD_ERR_OK) {
			throw new FailedUploadException("File upload failed", $files['file']->getError() ?? -1);
		}

		$file = $files['file'];

		$clientFilename = $file->getClientFilename();

		// Gets the upload file's extension
		if (strpos($clientFilename, '.') !== false) {
			$possibleExts = explode(".", $clientFilename);
			$ext = array_pop($possibleExts);
		}

		$dbFilename = ($ext !== null ? implode('.', $possibleExts) : $clientFilename);

		if ($dbFilename === '') {
			$dbFilename = null;
		}

		$fileRecord = File::create([
			'owner_id' => $user->id,
			'filename' => $dbFilename,
		]);

		$filename = $fileRecord->id;

		if ($dbFilename !== null) {
			$filename .= '-' . $dbFilename;
		}

		if ($ext !== null) {
			$filename .= '.' . $ext;
			$fileRecord->ext = $ext;
			$fileRecord->save();
		}

		$path = ($this->container['settings']['site']['upload']['path'] ?? $this->container['settings']['upload']['path']) . $fileRecord->user->id;

		try {
			// Move file to uploaded files path
			if (!is_dir($path)) {
				mkdir($path);
			}
			$file->moveTo($path . '/' . $filename);
		} catch (InvalidArgumentException $e) {
			// Remove inconsistent file record
			$fileRecord->delete();
			throw new FailedUploadException("File moving failed", $files['file']->getError() ?? -1);
		}

		return $this->container->router->pathFor('file.view', [
			'filename' => $filename,
		]);
	}

	public function getUpload($request, $response) {
		return $this->container->view->render($response, 'upload/file.twig');
	}

	public function postUpload($request, $response) {
		try {
			return $response->withRedirect($this->handleFileUpload($request, $this->container->auth->user()));
		} catch (FailedUploadException $e) {
			$this->container->flash->addMessage('danger', '<b>Oh no!</b> We couldn\'t upload your file. It\'s likely too big.');
			return $response->withRedirect($this->container->router->pathFor('file.upload'));
		}
	}

	/**
	 * Handles file uploads from ShareX
	 *
	 * TODO: add upload keys instead of plaintext username/password
	 */
	public function sharexUpload($request, $response) {
		$identifier = $request->getParam('identifier');
		$password   = $request->getParam('password');

		if (!$this->container->auth->attempt($identifier, $password)) {
			return $response->withStatus(403)->write("Invalid credentials given.");;
		}

		try {
			return $response->write($request->getUri()->getBaseUrl() . $this->handleFileUpload($request, $this->container->auth->user()));
		} catch (FailedUploadException $e) {
			// TODO: improve error handling
			return $response->withStatus(500)->write("Upload failed! File likely too large.");
		}
	}

	public function viewFile($request, $response, $args) {
		$filename = $args['filename'];
		$id       = (int) (strpos($filename, '-') !== false ? explode('-', $filename)[0] : $filename);
		$ext      = null;

		if (strpos($filename, '.') !== false) {
			$possibleExts = explode('.', $filename);
			$ext          = $possibleExts[count($possibleExts) - 1];
		}

		$filename = (strpos($filename, '-') !== false ? explode('-', $filename) : null);

		if ($filename !== null) {
			array_shift($filename);
			$filename = implode('-', $filename);
			$filename = ($ext !== null ? str_replace('.' . $ext, '', $filename) : $filename);
		}

		if (File::where('id', $id)->where('filename', $filename)->where('ext', $ext)->count() === 0) {
			var_dump($filename);
			var_dump($id);
			var_dump($ext);
			die();
			throw new \Slim\Exception\NotFoundException($request, $response);
		}

		$filepath  = $this->container['settings']['site']['upload']['path'] ?? $this->container['settings']['upload']['path'];
		$filepath .= File::where('id', $id)->where('ext', $ext)->first()->getPath();

		if (!file_exists($filepath) || file_get_contents($filepath) === false) {
			var_dump($filepath);
			die();
			throw new \Slim\Exception\NotFoundException($request, $response);
		}

		// Output file with file's MIME content type
		return $response->withHeader('Content-Type', mime_content_type($filepath))->write(file_get_contents($filepath));
	}

	public function deleteFile($request, $response, $args) {
		$filename = $args['filename'];
		$id       = strpos($filename, '.') !== false ? explode('.', $filename)[0] : $filename;

		if (File::where('id', $id)->count() === 0) {
			throw new \Slim\Exception\NotFoundException($request, $response);
		}

		$filepath  = $this->container['settings']['site']['upload']['path'] ?? $this->container['settings']['upload']['path'];
		$filepath .= File::where('id', $id)->first()->getPath();

		if (!file_exists($filepath) || file_get_contents($filepath) === false) {
			throw new \Slim\Exception\NotFoundException($request, $response);
		}

		if ($this->container->auth->user()->id != File::where('id', $id)->first()->owner_id && !$this->container->auth->user()->isModerator()) {
			// Slap people on the wrist who try to delete files they shoudn't be able to
			return $response->withStatus(403)->redirect($this->container->router->pathFor('home'));
		}

		if (unlink($filepath)) {
			File::where('id', $id)->delete();
		}

		return $response;
	}

	public function getPaste($request, $response) {
		return $this->container->view->render($response, 'upload/paste.twig');
	}

	public function postPaste($request, $response) {
		$file = File::create([
			'owner_id' => $this->container->auth->user()->id,
			'ext' => 'txt',
		]);

		file_put_contents($this->container['settings']['site']['upload']['path'] . $file->getPath(), $request->getParam('paste'));

		return $response->withRedirect($this->container->router->pathFor('file.view', [
			'filename' => $file->id . '.' . $file->ext,
		]));
	}

	public function getSharex($request, $response) {
		return $this->container->view->render($response, 'upload/sharex.twig', [
			'site' => [
				'url' => $request->getUri(),
			],
		]);
	}
}
