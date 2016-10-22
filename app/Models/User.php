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
		'name',
		'website',
		'bio',
		'password',
	];

	public function permissions() {
		return $this->hasOne('Sleeti\\Models\\UserPermissions', 'user_id', 'id');
	}

	public function settings() {
		return $this->hasOne('Sleeti\\Models\\UserSettings', 'user_id', 'id');
	}

	public function files() {
		return $this->hasMany('Sleeti\\Models\\File', 'owner_id', 'id');
	}

	public function rememberTokens() {
		return $this->hasMany('Sleeti\\Models\\UserRememberToken', 'user_id', 'id');
	}

	public function isAdmin() {
		return $this->permissions->contains('A');
	}

	public function isModerator() {
		return $this->permissions->contains('M') || $this->isAdmin();
	}

	public function addPermission(string $flag) {
		if ($this->permissions->contains($flag)) return;
		$this->permissions->flags .= $flag;
		$this->permissions->save();
	}

	public function removePermission(string $flag) {
		if (!$this->permissions->contains($flag)) return;
		$this->permissions->flags = str_replace($flag, '', $this->permission->flags);
		$this->permissions->save();
	}
}
