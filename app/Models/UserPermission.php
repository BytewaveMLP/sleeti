<?php

namespace Eeti\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * User Permissions model, one-to-one with User
 */
class UserPermission extends Model
{
	protected $table = 'user_permissions';

	protected $fillable = [
		'user_id',
		'flags'
	];

	public function user() {
		return $this->belongsTo('Eeti\\Models\\User', 'user_id', 'id');
	}

	public function contains(string $flag) {
		return strpos($this->flags, $flag) !== false;
	}

	public function addPermission(string $flag) {
		if ($this->contains($flag)) return;
		$this->flags .= $flag;
		$this->save();
	}

	public function removePermission(string $flag) {
		if (!$this->contains($flag)) return;
		$this->flags = str_replace($this->flags, '', $flag);
		$this->save();
	}
}
