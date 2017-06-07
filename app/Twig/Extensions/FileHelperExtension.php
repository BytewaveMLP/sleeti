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

namespace Sleeti\Twig\Extensions;

/**
 * Provides filters and functions to aid in working with
 * files on disk.
 */
class FileHelperExtension extends \Twig_Extension
{
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
				return filesize($file);
			}),
			new \Twig_SimpleFunction('dirsize', [$this, 'dirsize']),
		];
	}

	function formatBytes($bytes, $precision = 2) {
		$units = array('B', 'KB', 'MB', 'GB', 'TB');
		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count($units) - 1);

		$bytes /= pow(1024, $pow);

		return round($bytes, $precision) . ' ' . $units[$pow];
	}

	/**
	 * Recursively gets the content size of a directory
	 * @param  string $path The directory to iterate over
	 * @return int          The size of the directory's contents (recursive)
	 */
	public static function dirsize($path) {
		if (!is_dir($path)) return 0;
		
		$size = 0;

		foreach (new \DirectoryIterator($path) as $file){
			if ($file->isDot()) continue;
			$size += ($file->isDir()) ? self::dirsize("$path/$file") : $file->getSize();
		}

		return $size;
	}
}
