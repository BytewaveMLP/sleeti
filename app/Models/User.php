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
 * User model
 *
 * * One-to-many with File
 * * One-to-one with UserPermission
 */
class User extends Model
{
	protected $table = 'users'; // not exactly necessary, but why not

	protected $fillable = [
		'username',
		'email',
		'tfa_secret',
		'name',
		'website',
		'bio',
		'password',
		'default_privacy_state',
	];

	public function permission() {
		return $this->hasOne('Sleeti\\Models\\UserPermission', 'user_id', 'id');
	}

	public function files() {
		return $this->hasMany('Sleeti\\Models\\File', 'owner_id', 'id');
	}

	public function isAdmin() {
		return $this->permission->contains('A');
	}

	public function isModerator() {
		return $this->permission->contains('M') || $this->isAdmin();
	}

	public function addPermission(string $flag) {
		if ($this->permission->contains($flag)) return;
		$this->permission->flags .= $flag;
		$this->permission->save();
	}

	public function removePermission(string $flag) {
		if (!$this->permission->contains($flag)) return;
		$this->permission->flags = str_replace($flag, '', $this->permission->flags);
		$this->permission->save();
	}
}
