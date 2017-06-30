<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Sleeti\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * File model, many-to-one with User
 */
class File extends Model
{
	const PRIVACY_PUBLIC = 0;
	const PRIVACY_UNLISTED = 1;
	const PRIVACY_PRIVATE = 2;

	protected $table = 'uploaded_files';

	protected $fillable = [
		'owner_id',
		'filename',
		'ext', // At some point, there was likely a reason for me storing this. Dunno what it is now.
		'privacy_state',
	];

	public function user() {
		return $this->belongsTo('Sleeti\\Models\\User', 'owner_id', 'id');
	}

	public function getPath() {
		return $this->user->id . '/' . $this->filename;
	}
}
