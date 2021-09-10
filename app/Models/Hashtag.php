<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hashtag extends Model
{
    use HasFactory;
    protected $table = 'hashtags';
    function post(){
        return $this->belongsTo(Post::class, "post_id");
    } 
}
