<?php

namespace Eeti\Auth;

use Eeti\Models\User;

class Auth
{
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
}
