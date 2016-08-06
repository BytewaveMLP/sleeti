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
}
