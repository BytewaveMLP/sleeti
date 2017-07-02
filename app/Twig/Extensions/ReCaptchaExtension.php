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
class ReCaptchaExtension extends \Twig_Extension implements \Twig_Extension_GlobalsInterface
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
	 * {@inheritdoc}
	 */
	public function getGlobals() {
		return [
			'recaptcha' => [
				'script' => '<script src="https://www.google.com/recaptcha/api.js" async defer></script>',
				'form'   => '<div class="g-recaptcha" data-sitekey="' . $this->siteKey . '"></div>',
			],
		];
	}
}
