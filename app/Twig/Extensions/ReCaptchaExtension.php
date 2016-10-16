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

class ReCaptchaExtension extends \Twig_Extension
{
	/**
	 * reCAPTCHA site key
	 * @var string
	 */
	private $siteKey;

	public function __construct(string $siteKey) {
		$this->siteKey = $siteKey;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return 'reCAPTCHA';
	}

	/**
	 * Generates and returns the HTML code necessary for reCAPTCHA on a form
	 * @return string The HTML needed for a reCAPTCHA form element
	 */
	public function reCaptchaForm() {
		return "<div class=\"g-recaptcha\" data-sitekey=" . $this->siteKey . "></div>";
	}

	/**
	 * Returns the script necessary for
	 * @return string The HTML script tag needed for reCAPTCHA to function
	 */
	public function reCaptchaScript() {
		return '<script src="https://www.google.com/recaptcha/api.js" async defer></script>';
	}

	/**
	 * {@inheritdoc}
	 */
	public function getGlobals() {
		return [
			'recaptcha_script' => $this->reCaptchaScript(),
			'recaptcha_form'   => $this->reCaptchaForm(),
		];
	}
}
