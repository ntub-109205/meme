<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{	
    public function meme()
    {
   		return $this->belongsToMany('App\Meme');
    }
}
