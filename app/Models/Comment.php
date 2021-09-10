<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;
    function parent(){
        return $this->belongsTo(Comment::class, "parent_id");
    }    
    function post(){
        return $this->belongsTo(Post::class, "post_id");
    }          
    function child(){
        return $this->hasMany(Comment::class, "parent_id");
    }      
    function owner(){
        return $this->belongsTo(User::class, "author_id");
    }    
}
