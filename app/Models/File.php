<?php

namespace Eeti\Models;

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
		'ext',
	];

	public function user() {
		return $this->belongsTo('Eeti\\Models\\User', 'owner_id', 'id');
	}

	public function getPath() {
		return $this->user->id . '/' . $this->id . ($this->filename !== null ? '-' . $this->filename : '') . ($this->ext !== null ? '.' . $this->ext : '');
	}
}
