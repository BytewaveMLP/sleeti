<?php

namespace Eeti\Models;

use Illuminate\Database\Eloquent\Model;

class File extends Model
{
	protected $table = 'uploaded_files';

	protected $fillable = [
		'owner_id',
		'ext',
	];

	public function user() {
		return $this->belongsTo('Eeti\\Models\\User', 'id', 'owner_id');
	}
}
