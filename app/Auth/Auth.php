<?php

namespace Eeti\Auth;

use Eeti\Models\User;
use Eeti\Models\UserPermission;

class Auth
{
	public function user() {
		$user = isset($_SESSION['user']) ? User::find($_SESSION['user']) : null;
		return $user;
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

			// Lazy password rehash in case settings or algo changes
			if (password_needs_rehash($user->password, PASSWORD_DEFAULT, $this->container['settings']['password'] ?? ['cost' => 10])) {
				$user->password = password_hash($request->getParam('password'), PASSWORD_DEFAULT, $this->container['settings']['password'] ?? ['cost' => 10]);
				$user->save();
			}

			if ($user->permission === null) { // just in case
				$userPerms = UserPermission::create([
					'user_id' => $user->id,
					'flags'   => '',
				]);

				$userPerms->user()->associate($user);
			}
			
			return true;
		}

		return false;
	}

	public function signout() {
		unset($_SESSION['user']);
	}
}
