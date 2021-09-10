<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Follower extends Model
{
    use HasFactory;

    protected $table = 'followers';
    public function user(){
        return $this->belongsTo(User::class, "user_id");
    }    
    public function followedBy(){
        return $this->belongsTo(User::class, "followed_by_id");
    }
    public function post(){
        return $this->hasMany(Post::class, "author_id");
    }    
}
