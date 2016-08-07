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

		if (strpos($clientFilename, '.') !== false) {
			$possibleExts = explode(".", $clientFilename);
			$ext = $possibleExts[count($possibleExts) - 1];
		}

		$fileRecord = File::create([
			'owner_id' => $this->container->auth->user()->id,
			'ext' => $ext,
		]);

		$filename = $fileRecord->id;

		if ($ext !== null) {
			$filename .= '.' . $ext;
			$fileRecord->ext = $ext;
			$fileRecord->save();
		}

		$file->moveTo($this->container['settings']['upload']['path'] . $filename);

		return $response->withRedirect($this->container->router->pathFor('file.view', [
			'filename' => $filename,
		]));
	}

	public function viewFile($request, $response, $args) {
		$filename = $args['filename'];
		$filepath = $this->container['settings']['upload']['path'] . $filename;
		$id       = (int) (strpos($filename, '.') !== false ? explode('.', $filename)[0] : $filename);

		if (!file_exists($filepath) || file_get_contents($filepath) === false || File::where('id', $id)->count() === 0) {
			return $response->withStatus(404)->write('No files found.');
		}

		return $response->withHeader('Content-Type', mime_content_type($filepath))->write(file_get_contents($filepath));
	}
}
