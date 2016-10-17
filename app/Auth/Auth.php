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

namespace Sleeti\Auth;

use Sleeti\Models\User;
use Sleeti\Models\UserPermission;

/**
 * General auth handler class
 *
 * Handles sleeti user authentication
 */
class Auth
{
	protected $container;

	public function __construct($container) {
		$this->container = $container;
	}

	/**
	 * Gets the currently authenticated user
	 * @return \Sleeti\Models\User The currently authenticated user (null if no user is authenticated)
	 */
	public function user() {
		$user = isset($_SESSION['user']) ? User::find($_SESSION['user']) : null;
		return $user;
	}

	/**
	 * Determines if there is a user ID set in the current session
	 * @return boolean Is a user currently authenticated?
	 */
	public function check() {
		return isset($_SESSION['user']) && !isset($_SESSION['tfa-partial']);
	}

	/**
	 * Attempt user authentication with a given identifier and password
	 * @param  string $identifier The user's identifier (email or username)
	 * @param  string $password   The user's password
	 * @return \Sleeti\Models\User  The User matching the given credentials (false if no user found)
	 */
	public function attempt($identifier, $password) {
		$user = User::where('email', $identifier)->orWhere('username', $identifier)->first();

		// If there's no User with the given email or username, there's nothing to do
		if (!$user) {
			return false;
		}

		if (password_verify($password, $user->password)) {
			$_SESSION['user'] = $user->id;

			// Lazy password rehash in case settings or algo changes
			if (password_needs_rehash($user->password, PASSWORD_DEFAULT, ['cost' => ($this->container['settings']['password']['cost'] ?? 10)])) {
				$user->password = password_hash($password, PASSWORD_DEFAULT, ['cost' => ($this->container['settings']['password']['cost'] ?? 10)]);
				$user->save();
			}

			// Just in case there isn't an associated UserPermission for this User, create one
			if ($user->permission === null) {
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

	/**
	 * Deauthenticate the current user
	 */
	public function signout() {
		unset($_SESSION['user']);
	}
}
