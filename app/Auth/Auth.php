<?php

namespace Eeti\Auth;

use Eeti\Models\User;

class Auth
{
	public function user() {
		return isset($_SESSION['user']) ? User::find($_SESSION['user'])->first() : null;
	}

	public function check() {
		return isset($_SESSION['user']);
	}

	public function attempt($identifier, $password) {
		$user = User::where('email', $identifier)->orWhere('username', $identifier)->first();

		if (!$user) {
			return false;
		}

		if (password_verify($password, $user->password)) {
			$_SESSION['user'] = $user->id;
			return true;
		}

		return false;
	}

	public function signout() {
		unset($_SESSION['user']);
	}
}
