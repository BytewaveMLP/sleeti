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
		if (strpbrk($clientFilename, "\\/?%*:|\"<>")) {
			throw new FailedUploadException("Invalid filename (" . $clientFilename . ").", 99);
		}

		$path      = $this->container['settings']['site']['upload']['path'] . $user->id . '/';
		$filename  = $this->handleDuplicateFilename($path, $clientFilename);

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
			'filename'      => $filename,
			'privacy_state' => $privacy,
		]);

		try {
			// Move file to uploaded files path
			if (!is_dir($path)) {
				mkdir($path);
			}
			$file->moveTo($path . $filename);
		} catch (InvalidArgumentException $e) {
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

		while (file_exists($path . $newFilename)) {
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
			'owner'    => $owner->id,
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
				'owner'    => $owner->id,
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

		$files = File::where('filename', $filename);

		if ($files->count() === 0) {
			throw new \Slim\Exception\NotFoundException($request, $response);
		}

		$file = $files->first();

		$filepath  = $this->container['settings']['site']['upload']['path'];
		$filepath .= $file->getPath();

		if (!file_exists($filepath)) {
			throw new \Slim\Exception\NotFoundException($request, $response);
		}

		$user  = $this->container->auth->user();
		$owner = $file->user;

		// Check privacy state of file, show error if the user doesn't have permission to view
		if ($file->privacy_state == 2 && (!$this->container->auth->check() || ($user->id !== $owner->id && !$user->isModerator()))) {
			$this->container->log->log('file', \Monolog\Logger::WARNING, 'User attempted to view a file they don\'t have permission to.', [
				'viewer' => [
					$user->id ?? 'NONE',
					$user->username ?? 'NONE',
				],
				'owner' => [
					$owner->id,
					$owner->username,
				],
				'file' => $filename,
			]);

			throw new \Slim\Exception\NotFoundException($request, $response);
		}

		$contentType = mime_content_type($filepath);

		$response = $response->withHeader('Content-Type', $contentType);

		if ($contentType == 'text/html') {
			$response = $response->withHeader('Content-Disposition', 'attachment; filename*=UTF-8\'\'' . rawurlencode($filename) . '; ');
		}

		return $response->withBody(new \GuzzleHttp\Psr7\LazyOpenStream($filepath, 'r'));
	}

	public function deleteFile($request, $response, $args) {
		$filename = $args['filename'];
		$owner    = $args['owner'];

		$authedUser = $this->container->auth->user();

		$files = File::where('filename', $filename)->where('owner_id', $owner);

		if ($files->count() === 0) {
			throw new \Slim\Exception\NotFoundException($request, $response);
		}

		$file  = $files->first();
		$owner = $file->user;

		$filepath  = $this->container['settings']['site']['upload']['path'];
		$filepath .= $file->getPath();

		if (!file_exists($filepath)) {
			$file->delete(); // broken link
			throw new \Slim\Exception\NotFoundException($request, $response);
		}

		if ($authedUser->id != $file->first()->owner_id && !$authedUser->isModerator()) {
			// Slap people on the wrist who try to delete files they shoudn't be able to
			$this->container->log->log('file', \Monolog\Logger::WARNING, 'User attempted to delete a file they aren\'t allowed to.', [
				'deleter' => [
					$authedUser->id,
					$authedUser->username,
				],
				'owner' => [
					$owner->id,
					$owner->username,
				],
				'file' => $filename,
			]);

			return $response->withStatus(403)->withRedirect($this->container->router->pathFor('home'));
		}

		$this->container->log->log('file', \Monolog\Logger::INFO, 'File deleted.', [
			'deleter' => [
				$authedUser->id,
				$authedUser->username,
			],
			'owner' => [
				$owner->id,
				$owner->username,
			],
			'file' => $filename,
		]);

		if (unlink($filepath)) {
			$file->delete();
		}

		return $response;
	}

	public function changePrivacy($request, $response, $args) {
		$owner   = $args['owner'];
		$file    = $args['filename'];
		$privacy = $args['privacy'];
		$user    = $this->container->auth->user();

		if (($owner !== $user->id) && !$user->isModerator()) {
			$this->container->log->log('file', \Monolog\Logger::WARNING, 'User attempted to change the privacy of a file they aren\'t allowed to.', [
				'user' => [
					$user->id,
					$user->username,
				],
				'file' => $owner . '/' . $file,
			]);

			return $response->withStatus(403)->write('Permission denied.');
		}

		$files = File::where('owner_id', $owner)->where('filename', $file);

		if ($files->count() === 0) {
			throw new \Slim\Exception\NotFoundException($request, $response);
		}

		$file = $files->first();

		if ($privacy < 0 && $privacy > 2) {
			return $response->withStatus(400)->write('Invalid privacy state: ' . $privacy);
		}

		$file->privacy_state = $privacy;
		$file->save();

		$this->container->log->log('file', \Monolog\Logger::WARNING, 'File privacy state changed.', [
			'user' => [
				$user->id,
				$user->username,
			],
			'file' => $owner . '/' . $file,
			'privacy' => $privacy,
		]);

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
			$privacy = 0;
		} elseif ($privStr == 'unlisted') {
			$privacy = 1;
		} elseif ($privStr == 'private') {
			$privacy = 2;
		} else {
			$privacy = $owner->settings->default_privacy_state;
		}

		$path     = $this->container['settings']['site']['upload']['path'] . $owner->id . '/';
		$filename = $this->handleDuplicateFilename($path, $title);

		if ($filename != $title) {
			$this->container->flash->addMessage('info', '<b>Note:</b> Looks like you already had a file named <code>' . $title . '</code>. Your new file is named <code>' . $filename . '</code> instead.');
		}

		$file = File::create([
			'owner_id'      => $owner->id,
			'filename'      => $filename,
			'privacy_state' => $privacy,
		]);

		if (!is_dir($path)) {
			mkdir($path);
		}

		$safeFilename = rawurlencode($filename);

		file_put_contents($this->container['settings']['site']['upload']['path'] . $file->getPath(), $paste);

		$this->container->flash->addMessage('success', '<b>Woohoo!</b> Your paste was uploaded successfully. <a href="' . $this->container->router->pathFor('file.view', [
			'owner'    => $owner->id,
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
