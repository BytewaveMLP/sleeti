<?php

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
