<?php

namespace Eeti\Models;

use Illuminate\Database\Eloquent\Model;

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

	public function permission() {
		return $this->hasOne('Eeti\\Models\\UserPermission', 'user_id', 'id');
	}

	public function files() {
		return $this->hasMany('Eeti\\Models\\File', 'owner_id', 'id');
	}

	public function isAdmin() {
		return $this->permission->contains('A');
	}

	public function isModerator() {
		return $this->permission->contains('M') || $this->isAdmin();
	}

	public function isTester() {
		return $this->permission->contains('T') || $this->isAdmin();
	}
}
