<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $table = 'posts';
    function owner(){
        return $this->belongsTo(User::class, "author_id");
    }    
    function comments(){
        return $this->hasMany(Comment::class, "post_id");
    } 
    function reactions(){
        return $this->hasMany(Reaction::class, "post_id");
    }  
}
