<?php

namespace Eeti\Controllers;

use Respect\Validation\Validator as v;
use Eeti\Models\File;
use Eeti\Models\User;
use Eeti\Exceptions\FailedUploadException;

class FileController extends Controller
{
	private function handleFileUpload($request, $user) {
		$files = $request->getUploadedFiles();

		if (!isset($files['file']) || $files['file']->getError() !== UPLOAD_ERR_OK) {
			throw new FailedUploadException("File upload failed", $files['file']->getError() ?? -1);
		}

		$file = $files['file'];

		$clientFilename = $file->getClientFilename();

		if (strpos($clientFilename, '.') !== false) {
			$possibleExts = explode(".", $clientFilename);
			$ext = $possibleExts[count($possibleExts) - 1];
		}

		$fileRecord = File::create([
			'owner_id' => $user->id,
			'ext' => $ext,
		]);

		$filename = $fileRecord->id;

		if ($ext !== null) {
			$filename .= '.' . $ext;
			$fileRecord->ext = $ext;
			$fileRecord->save();
		}

		try {
			$file->moveTo($this->container['settings']['upload']['path'] . $filename);
		} catch (InvalidArgumentException $e) {
			$fileRecord->delete();
			throw new FailedUploadException("File moving failed", $files['file']->getError() ?? -1);
		}

		return $this->container->router->pathFor('file.view', [
			'filename' => $filename,
		]);
	}

	public function getUpload($request, $response) {
		return $this->container->view->render($response, 'file/upload.twig');
	}

	public function postUpload($request, $response) {
		try {
			return $response->withRedirect($this->handleFileUpload($request, $this->container->auth->user()));
		} catch (FailedUploadException $e) {
			$this->container->flash->addMessage('danger', '<b>Oh no!</b> We couldn\'t upload your file. It\'s likely too big.');
			return $response->withRedirect($this->container->router->pathFor('file.upload'));
		}
	}

	public function sharexUpload($request, $response) {
		$identifier = $request->getParam('identifier');
		$password   = $request->getParam('password');

		$user = User::where('email', $identifier)->orWhere('username', $identifier)->first();

		if (!$user || !password_verify($password, $user->password)) {
			return $response->withStatus(403)->write("Invalid credentials given.");;
		}

		try {
			return $response->write($request->getUri()->getBaseUrl() . $this->handleFileUpload($request, $user));
		} catch (FailedUploadException $e) {
			return $response->withStatus(500)->write("Upload failed! File likely too large.");
		}
	}

	public function viewFile($request, $response, $args) {
		$filename = $args['filename'];
		$filepath = $this->container['settings']['upload']['path'] . $filename;
		$id       = strpos($filename, '.') !== false ? explode('.', $filename)[0] : $filename;

		if (!file_exists($filepath) || file_get_contents($filepath) === false || File::where('id', $id)->count() === 0) {
			throw new \Slim\Exception\NotFoundException($request, $response);
		}

		return $response->withHeader('Content-Type', mime_content_type($filepath))->write(file_get_contents($filepath));
	}
}
