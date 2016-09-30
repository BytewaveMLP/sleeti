<?php

namespace Sleeti\Controllers;

use Respect\Validation\Validator as v;
use Sleeti\Models\File;
use Sleeti\Models\User;
use Sleeti\Auth\Auth;
use Sleeti\Exceptions\FailedUploadException;

class FileController extends Controller
{
	/**
	 * Handles file uploads from clients
	 * @param  \Sleeti\Models\User $user    The user to associate with the uploaded file
	 */
	private function handleFileUpload($request, $user) {
		$files = $request->getUploadedFiles();

		// If file upload fails, explain why
		if (!isset($files['file']) || $files['file']->getError() !== UPLOAD_ERR_OK) {
			throw new FailedUploadException("File upload failed", $files['file']->getError() ?? -1);
		}

		$file = $files['file'];

		$clientFilename = $file->getClientFilename();
		$dbFilename     = pathinfo($clientFilename, PATHINFO_FILENAME);
		$ext            = pathinfo($clientFilename, PATHINFO_EXTENSION);

		// Maintain cross-platform compatability by ensuring all file names are valid in NTFS
		if (strpbrk($dbFilename, "\\/?%*:|\"<>") || strpbrk($ext, "\\/?%*:|\"<>")) {
			throw new FailedUploadException("Invalid filename or extension (filename: " . ($dbFilename) . ", ext: " . ($ext) . ").", $files['file']->getError() ?? -1);
		}

		if ($dbFilename === '') {
			$dbFilename = null;
		}

		if ($ext === '') {
			$ext = null;
		}

		$privStr = $request->getParam('privacy');

		if ($privStr == 'public') {
			$privacy = 0;
		} elseif ($privStr == 'unlisted') {
			$privacy = 1;
		} elseif ($privStr == 'private') {
			$privacy = 2;
		} else {
			$privacy = $user->default_privacy_state;
		}

		$fileRecord = File::create([
			'owner_id'      => $user->id,
			'filename'      => $dbFilename,
			'privacy_state' => $privacy,
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

		return $filename;
	}

	public function getUpload($request, $response) {
		return $this->container->view->render($response, 'upload/file.twig');
	}

	public function postUpload($request, $response) {
		try {
			$filename = $this->handleFileUpload($request, $this->container->auth->user());
		} catch (FailedUploadException $e) {
			$this->container->flash->addMessage('danger', '<b>Oh no!</b> We couldn\'t upload your file. Either the file name contains invalid characters, your file is too large, or we had trouble in handling. Sorry!');
			return $response->withRedirect($this->container->router->pathFor('file.upload'));
		}

		$this->container->flash->addMessage('success', '<b>Woohoo!</b> Your file was uploaded successfully. <a href="' . $this->container->router->pathFor('file.view', [
			'filename' => $filename,
		]) . '">Click here</a> to view it.');
		return $response->withRedirect($this->container->router->pathFor('file.upload'));
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
			return $response->write($request->getUri()->getBaseUrl() . $this->container->router->pathFor('file.view', [
				'filename' => $this->handleFileUpload($request, $this->container->auth->user())
			]));
		} catch (FailedUploadException $e) {
			// TODO: improve error handling
			return $response->withStatus(500)->write($e->getMessage());
		}
	}

	public function viewFile($request, $response, $args) {
		$filename = $args['filename'];
		$name     = pathinfo($filename, PATHINFO_FILENAME);
		$id       = (int) (strpos($name, '-') !== false ? explode('-', $name)[0] : $name);
		$name     = (strpos($name, '-') !== false ? substr($name, strpos($name, '-') + 1) : null);
		$ext      = pathinfo($filename, PATHINFO_EXTENSION);

		if ($ext === '') {
			$ext = null;
		}

		$files = File::where('id', $id)->where('filename', $name)->where('ext', $ext);

		if ($files->count() === 0) {
			throw new \Slim\Exception\NotFoundException($request, $response);
		}

		$file = $files->first();

		$filepath  = $this->container['settings']['site']['upload']['path'] ?? $this->container['settings']['upload']['path'];
		$filepath .= $file->getPath();

		if (!file_exists($filepath) || file_get_contents($filepath) === false) {
			throw new \Slim\Exception\NotFoundException($request, $response);
		}

		// Check privacy state of file, show error if the user isn't authenticated when they need to be
		if ($file->privacy_state == 2 && !$this->container->auth->check()) {
			$this->container->flash->addMessage('danger', '<b>Whoops!</b> You need to sign in before you can view this file.');
			return $response->withStatus(403)->withRedirect($this->container->router->pathFor('auth.signin'));
		}

		// Output file with file's MIME content type

		$handle = fopen($filepath, 'rb');

		if ($handle === false) {
			$this->container->flash->addMessage('danger', '<b>Uh oh!</b> We had an issue while trying to show you this file. Sorry! Try again later.');
			return $response->withStatus(500)->withRedirect($this->container->router->pathFor('home'));
		}

		$respone = $response->withHeader('Content-Type', mime_content_type($filepath));

		while (!feof($handle)) {
			$buffer = fread($handle, 1024 * 1024);
			$response = $response->write($buffer);
		}

		fclose($handle);

		return $response;
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
		$title = $request->getParam('title');
		$paste = $request->getParam('paste');

		$validation = $this->container->validator->validate($request, [
			'title' => v::length(null, 100)->regex('/^[a-zA-Z0-9\-\. ]*$/'),
			'paste' => v::notEmpty(),
		]);

		$filename = pathinfo($title, PATHINFO_FILENAME);
		$ext      = pathinfo($title, PATHINFO_EXTENSION);

		if ($filename === '') {
			$filename = null;
		}

		if ($ext === '') {
			$ext = null;
		}

		if ($validation->failed()) {
			$this->container->flash->addMessage('danger', '<b>Whoops!</b> Looks like we\'re missing something...');
			return $response->withRedirect($this->container->router->pathFor('file.upload.paste'));
		}

		$privStr = $request->getParam('privacy');

		if ($privStr == 'public') {
			$privacy = 0;
		} elseif ($privStr == 'unlisted') {
			$privacy = 1;
		} elseif ($privStr == 'private') {
			$privacy = 2;
		} else {
			$privacy = $user->default_privacy_state;
		}

		$file = File::create([
			'owner_id'      => $this->container->auth->user()->id,
			'filename'      => $filename,
			'ext'           => $ext,
			'privacy_state' => $privacy,
		]);

		file_put_contents($this->container['settings']['site']['upload']['path'] . $file->getPath(), $paste);

		$this->container->flash->addMessage('success', '<b>Woohoo!</b> Your paste was uploaded successfully. <a href="' . $this->container->router->pathFor('file.view', [
			'filename' => $file->id . ($filename !== null ? '-' . $filename : '') . ($file->ext !== null ? '.' . $file->ext : ''),
		]) . '">Click here</a> to view it.');
		return $response->withRedirect($this->container->router->pathFor('file.upload.paste'));
	}

	public function getSharex($request, $response) {
		return $this->container->view->render($response, 'upload/sharex.twig', [
			'site' => [
				'url' => $request->getUri(),
			],
		]);
	}
}
