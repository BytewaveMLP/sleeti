<?php

namespace Sleeti\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * File model, many-to-one with User
 */
class File extends Model
{
	protected $table = 'uploaded_files';

	protected $fillable = [
		'owner_id',
		'filename',
		'ext', // At some point, there was likely a reason for me storing this. Dunno what it is now.
		'privacy_state',
	];

	public function user() {
		return $this->belongsTo('Sleeti\\Models\\User', 'owner_id', 'id');
	}

	public function getPath() {
		return $this->user->id . '/' . $this->id . ($this->filename !== null ? '-' . $this->filename : '') . ($this->ext !== null ? '.' . $this->ext : '');
	}
}
