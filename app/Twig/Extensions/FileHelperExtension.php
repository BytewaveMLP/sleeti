<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Sleeti\Twig\Extensions;

/**
 * Provides filters and functions to aid in working with
 * files on disk.
 */
class FileHelperExtension extends \Twig_Extension
{
	protected static $fs;

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return 'file_helper';
	}

	/**
	 * {@inheritdoc}
	 */
	public function getFilters() {
		return [
			new \Twig_SimpleFilter('format_bytes', [$this, 'formatBytes']),
		];
	}

	public function getFunctions() {
		return [
			new \Twig_SimpleFunction('filesize', function($file) {
				return self::$fs->getSize($file);
			}),
			new \Twig_SimpleFunction('dirsize', [$this, 'dirsize']),
		];
	}

	public function formatBytes($bytes, $precision = 2) {
		$units = array('B', 'KB', 'MB', 'GB', 'TB');
		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count($units) - 1);

		$bytes /= pow(1024, $pow);

		return round($bytes, $precision) . ' ' . $units[$pow];
	}

	public static function setFs($fs) {
		self::$fs = $fs;

		return new static;
	}

	/**
	 * Recursively gets the content size of a directory
	 * @param  string $path The directory to iterate over
	 * @return int          The size of the directory's contents (recursive)
	 */
	public static function dirsize($path) {
		$size = 0;

		foreach (self::$fs->listContents($path, true) as $file) {
			$size += self::$fs->getSize($file['path']);
		}

		return $size;
	}
}
