<?php

/**
 * This file is part of sleeti.
 * Copyright (C) 2016  Eliot Partridge
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

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
			$privacy = $user->settings->default_privacy_state;
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

		$path = $this->container['settings']['site']['upload']['path'] . $fileRecord->user->id;

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
			$owner    = $this->container->auth->user();
			$filename = $this->handleFileUpload($request, $owner);
		} catch (FailedUploadException $e) {
			$this->container->flash->addMessage('danger', '<b>Oh no!</b> We couldn\'t upload your file. Either the file name contains invalid characters, your file is too large, or we had trouble in handling. Sorry!');

			$this->container->log->log('upload', \Monolog\Logger::ERROR, 'File upload failed.', [
				'uploader' => [
					$owner->id,
					$owner->username,
				],
				$e->getMessage(),
			]);

			return $response->withRedirect($this->container->router->pathFor('file.upload'));
		}

		$safeFilename = rawurlencode($filename);

		$this->container->flash->addMessage('success', '<b>Woohoo!</b> Your file was uploaded successfully. <a href="' . $this->container->router->pathFor('file.view', [
			'filename' => $safeFilename,
		]) . '">Click here</a> to view it.');

		$this->container->log->log('upload', \Monolog\Logger::INFO, 'File uploaded.', [
			'owner' => [
				$owner->id,
				$owner->username,
			],
			'filename' => $safeFilename,
		]);

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

			$safeFilename = rawurlencode($this->handleFileUpload($request, $owner));

			$this->container->log->log('upload-sharex', \Monolog\Logger::INFO, 'File uploaded.', [
				'owner' => [
					$owner->id,
					$owner->username,
				],
				'filename' => $safeFilename,
			]);

			return $response->write($request->getUri()->getBaseUrl() . $this->container->router->pathFor('file.view', [
				'filename' => $safeFilename,
			]));
		} catch (FailedUploadException $e) {
			$this->container->log->log('upload-sharex', \Monolog\Logger::ERROR, 'File upload failed.', [
				'uploader' => [
					$owner->id,
					$owner->username,
				],
				$e->getMessage(),
			]);

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

		$filepath  = $this->container['settings']['site']['upload']['path'];
		$filepath .= $file->getPath();

		if (!file_exists($filepath) || file_get_contents($filepath) === false) {
			throw new \Slim\Exception\NotFoundException($request, $response);
		}

		// Check privacy state of file, show error if the user isn't authenticated when they need to be
		if ($file->privacy_state == 2 && !$this->container->auth->check()) {
			$this->container->flash->addMessage('danger', '<b>Whoops!</b> You need to sign in before you can view this file.');
			return $response->withStatus(403)->withRedirect($this->container->router->pathFor('auth.signin') . '?redirect=' . $this->container->router->pathFor('file.view', ['filename' => $filename]));
		}

		// Output file with file's MIME content type
		return $response->withHeader('Content-Type', mime_content_type($filepath))->withBody(new \GuzzleHttp\Psr7\LazyOpenStream($filepath, 'r'));
	}

	public function deleteFile($request, $response, $args) {
		$filename = $args['filename'];
		$id       = strpos($filename, '.') !== false ? explode('.', $filename)[0] : $filename;

		$authedUser = $this->container->auth->user();

		$files = File::where('id', $id);

		if ($files->count() === 0) {
			throw new \Slim\Exception\NotFoundException($request, $response);
		}

		$file  = $files->first();
		$owner = $file->user;

		$filepath  = $this->container['settings']['site']['upload']['path'];
		$filepath .= $file->getPath();

		if (!file_exists($filepath) || file_get_contents($filepath) === false) {
			$file->delete(); // broken link
			throw new \Slim\Exception\NotFoundException($request, $response);
		}

		if ($authedUser->id != File::where('id', $id)->first()->owner_id && !$authedUser->isModerator()) {
			// Slap people on the wrist who try to delete files they shoudn't be able to
			return $response->withStatus(403)->redirect($this->container->router->pathFor('home'));
		}

		$safeFilename = rawurlencode($file->id . ($filename !== null ? '-' . $filename : '') . ($file->ext !== null ? '.' . $file->ext : ''));

		$this->container->log->log('file', \Monolog\Logger::INFO, 'File deleted.', [
			'deleter' => [
				$authedUser->id,
				$authedUser->username,
			],
			'owner' => [
				$owner->id,
				$owner->username,
			],
			'file' => $safeFilename,
		]);

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
		$owner = $this->container->auth->user();

		$validation = $this->container->validator->validate($request, [
			'title' => v::length(null, 100)->validFilename(),
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
			$privacy = $user->settings->default_privacy_state;
		}

		$file = File::create([
			'owner_id'      => $owner->id,
			'filename'      => $filename,
			'ext'           => $ext,
			'privacy_state' => $privacy,
		]);

		$path = $this->container['settings']['site']['upload']['path'] . $file->user->id;

		if (!is_dir($path)) {
			mkdir($path);
		}

		$safeFilename = rawurlencode($file->id . ($filename !== null ? '-' . $filename : '') . ($file->ext !== null ? '.' . $file->ext : ''));

		file_put_contents($this->container['settings']['site']['upload']['path'] . $file->getPath(), $paste);

		$this->container->flash->addMessage('success', '<b>Woohoo!</b> Your paste was uploaded successfully. <a href="' . $this->container->router->pathFor('file.view', [
			'filename' => $safeFilename,
		]) . '">Click here</a> to view it.');

		$this->container->log->log('upload-paste', \Monolog\Logger::INFO, 'Paste created.', [
			'owner' => [
				$owner->id,
				$owner->username,
			],
			'filename' => $safeFilename,
		]);

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
