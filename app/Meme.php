<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Meme extends Model
{
	protected $table = 'meme';

    public function tags()
    {
   		return $this->belongsToMany('App\Tag');
    }

    public function users()
    {
        return $this->belongsTo('App\User');
    }

    public function templates()
    {
        return $this->belongsTo('App\Template');
    }
}
