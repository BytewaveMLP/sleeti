<?php

namespace Eeti\Controllers;

use Respect\Validation\Validator as v;
use Eeti\Models\File;

class FileController extends Controller
{
	public function getUpload($request, $response) {
		return $this->container->view->render($response, 'file/upload.twig');
	}

	public function postUpload($request, $response) {
		$files = $request->getUploadedFiles();

		if (!isset($files['file']) || $files['file']->getError() !== UPLOAD_ERR_OK) {
			$this->container->flash->addMessage('danger', '<b>Oh no!</b> We couldn\'t upload your file. It\'s likely too big.');
			return $response->withRedirect($this->container->router->pathFor('file.upload'));
		}

		$file = $files['file'];

		$clientFilename = $file->getClientFilename();

		$filename = substr(md5($file->getStream()), 0, 7);

		if (strpos($clientFilename, '.') !== false) {
			$possibleExts = explode(".", $clientFilename);
			$ext = $possibleExts[count($possibleExts) - 1];
			$filename .= '.' . $ext;
		}

		$file = File::create([
			'owner_id' => $this->container->auth->user()->id,
			'filename' => $filename,
		]);

		$files['file']->moveTo(__DIR__ . '/../uploads/' . $filename);

		$this->container->flash->addMessage('success', 'File uploaded successfully!');
		return $response->withRedirect($this->container->router->pathFor('file.view', ['filename', $filename]));
	}

	public function viewFile($request, $response, $args) {
		$filename = $args['filename'];
		$filepath = __DIR__ . '/../uploads/' . $filename;

		if (!file_exists($filepath)) {
			return $response->withStatus(404);
		}

		header('Content-Type: ' . mime_content_type($filepath));
		echo file_get_contents($filepath);
	}
}
