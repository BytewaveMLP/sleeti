<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Sleeti\Twig\Extensions;

/**
 * Provides reCAPTCHA support to Twig views
 */
class ReCaptchaExtension extends \Twig_Extension
{
	/**
	 * reCAPTCHA site key
	 * @var string
	 */
	private $siteKey;

	/**
	 * Creates a new instance of the reCAPTCHA extension
	 * @param string $siteKey The reCAPTCHA site key
	 */
	public function __construct($siteKey) {
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
			'recaptcha' => [
				'script' => $this->reCaptchaScript(),
				'form'   => $this->reCaptchaForm(),
			],
		];
	}
}
