<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Sleeti\Controllers;

use \Respect\Validation\Validator as v;
use \Sleeti\Models\File;
use \Sleeti\Models\User;
use \Sleeti\Auth\Auth;
use \Sleeti\Exceptions\FailedUploadException;

class FileController extends Controller
{
	/**
	 * Handles file uploads from clients
	 * @param  \Sleeti\Models\User $user    The user to associate with the uploaded file
	 * @param  bool                $flash   Should we tell the user if there was a duplicate file?
	 */
	private function handleFileUpload($request, $user, $flash = false) {
		$files = $request->getUploadedFiles();

		// If file upload fails, explain why
		if (empty($files) || !isset($files['file'])) {
			throw new FailedUploadException("No file provided", 1);
		}

		if ($files['file']->getError() !== UPLOAD_ERR_OK) {
			throw new FailedUploadException("File upload failed", $files['file']->getError() ?? -1);
		}

		$file = $files['file'];

		$clientFilename = $file->getClientFilename();

		if (!$clientFilename) {
			throw new FailedUploadException("Empty filenames are not allowed.", 99);
		}

		// Maintain cross-platform compatability by ensuring all file names are valid in NTFS
		// $validator = v::notEmpty()->noTrailingWhitespace()->length(null, 100)->validFilename(); # Flysystem takes care of invalid filenames.
		// if (!$validator->validate($clientFilename)) {
		// 	throw new FailedUploadException("Invalid filename (" . $clientFilename . ").", 99);
		// }

		$path      = $user->id . '/';
		$filename  = $this->handleDuplicateFilename($path, $clientFilename);

		$privStr = $request->getParam('privacy');

		if ($privStr == 'public') {
			$privacy = File::PRIVACY_PUBLIC;
		} elseif ($privStr == 'unlisted') {
			$privacy = File::PRIVACY_UNLISTED;
		} elseif ($privStr == 'private') {
			$privacy = File::PRIVACY_PRIVATE;
		} else {
			$privacy = $user->settings->default_privacy_state;
		}

		$fileRecord = File::create([
			'owner_id'      => $user->id,
			'filename'      => $filename,
			'privacy_state' => $privacy,
		]);

		try {
			$fileStream = $file->getStream();

			$this->container->fs->writeStream(
				$path . $filename,
				$fileStream->detach()
			);
		} catch (RuntimeException $e) {
			// Remove inconsistent file record
			$fileRecord->delete();
			throw new FailedUploadException("File moving failed", 100);
		}

		if ($flash && $filename != $clientFilename) {
			$this->container->flash->addMessage('info', '<b>Note:</b> Looks like you already had a file named <code>' . $clientFilename . '</code>. Your new file is named <code>' . $filename . '</code> instead.');
		}

		return $filename;
	}

	private function handleDuplicateFilename($path, $filename) {
		$counter     = 1;
		$newFilename = $filename;

		while ($this->container->fs->has($path . $newFilename)) {
			$newFilename = $counter . '-' . $filename;
			$counter++;
		}

		return $newFilename;
	}

	public function getUpload($request, $response) {
		return $this->container->view->render($response, 'upload/file.twig');
	}

	public function postUpload($request, $response) {
		try {
			$owner    = $this->container->auth->user();
			$filename = $this->handleFileUpload($request, $owner, true);
		} catch (FailedUploadException $e) {
			$this->container->flash->addMessage('danger', '<b>Oh no!</b> We couldn\'t upload your file. Either the file name contains invalid characters, your file is too large, or we had trouble in handling. Sorry!');

			$this->container->log->error('upload', $owner->username . ' (' . $owner->id . ")'s file upload failed.\nException: " . $e->getMessage());

			return $response->withRedirect($this->container->router->pathFor('file.upload'));
		}

		$safeFilename = rawurlencode($filename);
		$path = $this->container->router->pathFor('file.view', [
			'owner'    => $owner->id,
			'filename' => $safeFilename,
		]);

		$this->container->flash->addMessage('success', '<b>Woohoo!</b> Your file was uploaded successfully. <a href="' . $path . '">Click here</a> to view it.<br><br><button type="button" role="button" class="btn btn-default btn-sm copy-to-clipboard" data-clipboard-text="' . $request->getUri()->getBaseUrl() . $path . '"><span class="fa fa-clipboard fa-fw"></span> Copy link to clipboard</button>');

		$this->container->log->info('upload', $owner->username . ' (' . $owner->id . ') uploaded ' . $filename . '.');

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
			return $response->withStatus(401)->write("Invalid credentials given.");;
		}

		try {
			$owner = $this->container->auth->user();

			$filename = $this->handleFileUpload($request, $owner);

			$this->container->log->info('upload-sharex', $owner->username . ' (' . $owner->id . ') uploaded ' . $filename . '.');

			return $response->write($request->getUri()->getBaseUrl() . $this->container->router->pathFor('file.view', [
				'owner'    => $owner->id,
				'filename' => rawurlencode($filename),
			]));
		} catch (FailedUploadException $e) {
			$this->container->log->error('upload-sharex', $owner->username . ' (' . $owner->id . ")'s file upload failed.\nException: " . $e->getMessage());

			return $response->withStatus(500)->write('Upload failed - ' . $e->getMessage());
		}
	}

	public function viewFile($request, $response, $args) {
		$filename = $args['filename'];
		$owner    = $args['owner'];

		$files = File::where('owner_id', $owner)->where('filename', $filename);

		if ($files->count() === 0) {
			throw new \Slim\Exception\NotFoundException($request, $response);
		}

		$file = $files->first();

		$filepath = $file->getPath();

		if (!$this->container->fs->has($filepath)) {
			throw new \Slim\Exception\NotFoundException($request, $response);
		}

		$user  = $this->container->auth->user();
		$owner = $file->user;

		// Check privacy state of file, show error if the user doesn't have permission to view
		if ($file->privacy_state == File::PRIVACY_PRIVATE && (!$this->container->auth->check() || ($user->id !== $owner->id && !$user->isModerator()))) {
			if ($this->container->auth->check()) {
				$viewer = $user->username . ' (' . $user->id . ')';
			} else {
				$viewer = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
			}

			$this->container->log->warning('file', $viewer . ' attempted to view ' . $owner->username . ' (' . $owner->id . ')\'s file ' . $filename . '.');

			throw new \Slim\Exception\NotFoundException($request, $response);
		}

		$contentType = $this->container->fs->getMimetype($filepath);

		$response = $response->withHeader('Content-Type', $contentType);

		if ($contentType == 'text/html') {
			$response = $response->withHeader('Content-Disposition', 'attachment; filename*=UTF-8\'\'' . rawurlencode($filename) . '; ');
		}

		return $response->withBody(new \Slim\Http\Stream($this->container->fs->readStream($filepath)));
	}

	public function deleteFile($request, $response, $args) {
		$filename = $args['filename'];
		$owner    = $args['owner'];

		$authedUser = $this->container->auth->user();

		$files = File::where('owner_id', $owner)->where('filename', $filename);

		if ($files->count() === 0) {
			throw new \Slim\Exception\NotFoundException($request, $response);
		}

		$file  = $files->first();
		$owner = $file->user;

		$filepath = $file->getPath();

		if (!$this->container->fs->has($filepath)) {
			$file->delete(); // broken link
			throw new \Slim\Exception\NotFoundException($request, $response);
		}

		if ($authedUser->id != $owner->id && !$authedUser->isModerator()) {
			$this->container->log->warning('file', $authedUser->username . ' (' . $authedUser->id . ') attempted to delete ' . $owner->username . ' (' . $owner->id . ')\'s file ' . $filename . '.');

			// Slap people on the wrist who try to delete files they shoudn't be able to
			return $response->withStatus(403)->withRedirect($this->container->router->pathFor('home'));
		}

		if ($this->container->fs->delete($filepath)) {
			$file->delete();
		}

		$this->container->log->info('file', $authedUser->username . ' (' . $authedUser->id . ') deleted ' . $owner->username . ' (' . $owner->id . ')\'s file' . $filename . '.');

		return $response;
	}

	public function changePrivacy($request, $response, $args) {
		$owner    = $args['owner'];
		$filename = $args['filename'];
		$privacy  = $args['privacy'];
		$user     = $this->container->auth->user();

		if (($owner !== $user->id) && !$user->isModerator()) {
			$this->container->log->warning('file', $user->username . ' (' . $user->id . ') attempted to change the privacy of ' . $owner->username . ' (' . $owner->id . ')\'s file' . $filename . '.');

			return $response->withStatus(403)->write('Permission denied.');
		}

		$file = File::where('owner_id', $owner)->where('filename', $filename)->first();

		if (!$file) {
			throw new \Slim\Exception\NotFoundException($request, $response);
		}

		$owner = $file->user;

		if ($privacy < 0 && $privacy > 2) {
			return $response->withStatus(400)->write('Invalid privacy state: ' . $privacy);
		}

		$oldPrivacy = $file->privacy_state;
		$file->privacy_state = $privacy;
		$file->save();

		$this->container->log->info('file', $user->username . ' (' . $user->id . ') changed the privacy of ' . $owner->username . ' (' . $owner->id . ')\'s file ' . $filename . ' from ' . $oldPrivacy . ' to ' . $privacy . '.');

		return $response;
	}

	public function getPaste($request, $response) {
		return $this->container->view->render($response, 'upload/paste.twig');
	}

	public function postPaste($request, $response) {
		$title = $request->getParam('title');
		$paste = $request->getParam('paste');
		$owner = $this->container->auth->user();

		$validation = $this->container->validator->validate($request, [
			'title' => v::notEmpty()->noTrailingWhitespace()->length(null, 100)->validFilename(),
			'paste' => v::notEmpty(),
		]);

		if ($validation->failed()) {
			$this->container->flash->addMessage('danger', '<b>Whoops!</b> Looks like we\'re missing something...');
			return $response->withRedirect($this->container->router->pathFor('file.upload.paste'));
		}

		$privStr = $request->getParam('privacy');

		if ($privStr == 'public') {
			$privacy = File::PRIVACY_PUBLIC;
		} elseif ($privStr == 'unlisted') {
			$privacy = File::PRIVACY_UNLISTED;
		} elseif ($privStr == 'private') {
			$privacy = File::PRIVACY_PRIVATE;
		} else {
			$privacy = $owner->settings->default_privacy_state;
		}

		$path     = $owner->id . '/';
		$filename = $this->handleDuplicateFilename($path, $title);

		if ($filename != $title) {
			$this->container->flash->addMessage('info', '<b>Note:</b> Looks like you already had a file named <code>' . $title . '</code>. Your new file is named <code>' . $filename . '</code> instead.');
		}

		$file = File::create([
			'owner_id'      => $owner->id,
			'filename'      => $filename,
			'privacy_state' => $privacy,
		]);

		$safeFilename = rawurlencode($filename);

		$this->container->fs->write($file->getPath(), $paste);

		$path = $this->container->router->pathFor('file.view', [
			'owner'    => $owner->id,
			'filename' => $safeFilename,
		]);

		$this->container->flash->addMessage('success', '<b>Woohoo!</b> Your paste was created successfully. <a href="' . $path . '">Click here</a> to view it.<br><br><button type="button" role="button" class="btn btn-default btn-sm copy-to-clipboard" data-clipboard-text="' . $request->getUri()->getBaseUrl() . $path . '"><span class="fa fa-clipboard fa-fw"></span> Copy link to clipboard</button>');

		$this->container->log->info('upload-paste', $owner->username . ' (' . $owner->id . ') created paste ' . $title . '.');

		return $response->withRedirect($this->container->router->pathFor('file.upload.paste'));
	}

	public function getSharex($request, $response) {
		return $this->container->view->render($response, 'upload/sharex.twig', [
			'site' => [
				'url' => $request->getUri(),
			],
		]);
	}

	public function getBashScript($request, $response) {
		return $this->container->view->render($response, 'upload/bash-curl.twig', [
			'site' => [
				'url' => $request->getUri()->getBaseUrl() . $this->container->router->pathFor('file.upload.sharex'),
			],
		]);
	}
}
