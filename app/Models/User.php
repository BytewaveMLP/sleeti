<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
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

	public function tfaRecoveryTokens() {
		return $this->hasMany('Sleeti\\Models\\UserTfaRecoveryToken', 'user_id', 'id');
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
