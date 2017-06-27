<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Sleeti\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * User Settings model, one-to-one with User
 */
class UserSettings extends Model
{
	protected $table = 'user_settings';

	protected $fillable = [
		'user_id',
		'tfa_enabled',
		'tfa_secret',
		'default_privacy_state',
	];

	public function user() {
		return $this->belongsTo('Sleeti\\Models\\User', 'user_id', 'id');
	}
}
