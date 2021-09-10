<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reaction extends Model
{
    use HasFactory;
    protected $table = 'reactions';
    public function reactor(){
        return $this->belongsTo(User::class, "user_id");
    }
    public function post(){
        return $this->belongsTo(Post::class, "post_id");
    }
}
