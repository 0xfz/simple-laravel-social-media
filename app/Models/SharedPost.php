<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SharedPost extends Model
{
    use HasFactory;
    protected $table = "shared_posts";
    function shared_by(){
        return $this->belongsTo(User::class, "user_id");
    }    
    function post(){
        return $this->belongsTo(Post::class, "post_id");
    }
}
