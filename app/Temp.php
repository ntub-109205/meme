<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Temp extends Model
{	
	protected $table = 'temp';

    public function users()
    {
        return $this->belongsTo('App\User');
    }
}
