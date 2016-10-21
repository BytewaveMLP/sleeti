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
