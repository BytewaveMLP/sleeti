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
		'ext',
	];

	public function user() {
		return $this->belongsTo('Eeti\\Models\\User', 'owner_id', 'id');
	}
}
