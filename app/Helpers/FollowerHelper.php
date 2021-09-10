<?php
namespace App\Helpers;
use Illuminate\Support\Facades\Auth;
use App\Models\Follower;
class FollowerHelper{
    public static function follower_count($user_id){
        $follow = new Follower;
        return $follow::where("following_id", $user_id)->get()->count();
    }    
    public static function following_count($user_id){
        $follow = new Follower;
        return $follow::where("user_id", $user_id)->get()->count();
    }
    public static function is_followed_by($followed_by, $followed_user){
        return Follower::where("user_id", $followed_by)->where("following_id", $followed_user)->first();
    }
}