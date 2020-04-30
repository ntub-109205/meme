<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    public function meme()
    {
        return $this->hasMany('App\Meme');
    }

    public function users()
    {
        return $this->belongsTo('App\User');
    }

    public function category()
    {
        return $this->belongsTo('App\Category', 'category_id');
    }
}
