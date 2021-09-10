<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\UserData;
use App\Models\User;
use App\Models\Follower;
use App\Helpers\FollowerHelper;
use Illuminate\Support\Facades\Auth;
class UserController extends Controller
{
    public function getUserDetailsByUsername($username){

        /**
         * This method used to get user details from username
         * 
         * @return Response
         * 
         */

        $user = new User;
        $userData = $user::where("username", $username)->first(["id","username","image_url","display_name","bio", "created_at"])->makeHidden(["created_at"]);
        # get data from table followers
        $follower = Follower::with("followedBy:id,username")->where("user_id", $userData->id)->orWhere("followed_by_id", Auth::id())->orWhere("followed_by_id", $userData->id)->get(); 
        $current_user_followings = $follower->where("followed_by_id", Auth::id())->map(function($item, $key){return $item->id;});
        if($userData){
            $userData->followed_by = $follower->where("user_id", $userData->id)->whereIn("followed_by_id", $current_user_followings)->map(function($item, $key){
                return $item->followedBy->username;
            })->flatten();
            $userData->joined_at = strftime( "%d %B %Y", strtotime($userData->created_at));
            $userData->total_followers = $follower->where("user_id", $userData->id)->count();
            $userData->total_followings = $follower->where("followed_by_id", $userData->id)->count();
            $userData->is_followed_by_me = ($follower->where("followed_by_id", Auth::id())->where("user_id", $userData->id)->first()) ? true : false;
            return response()->json(
                [
                    "data" => $userData, 
                    "errors" => []
                ], 200
            );
        }
        return response()->json(
            [
                "data" => [],
                "errors" => ["Internal Server Error"]
            ], 500
        );
    }
    public function getUserDetailsByID($user_id){
        $user = new User;
        $userData = $user::where("id", $user_id)->first(["id","username","image_url","display_name","bio", "created_at"])->makeHidden(["created_at"]);
        # get data from table followers
        $follower = Follower::with("followedBy:id,username")->where("user_id", $userData->id)->orWhere("followed_by_id", Auth::id())->orWhere("followed_by_id", $userData->id)->get(); 
        $current_user_followings = $follower->where("followed_by_id", Auth::id())->map(function($item, $key){return $item->id;});
        if($userData){
            $userData->followed_by = $follower->where("user_id", $userData->id)->whereIn("followed_by_id", $current_user_followings)->map(function($item, $key){
                return $item->followedBy->username;
            })->flatten();
            $userData->joined_at = strftime( "%d %B %Y", strtotime($userData->created_at));
            $userData->total_followers = $follower->where("user_id", $userData->id)->count();
            $userData->total_followings = $follower->where("followed_by_id", $userData->id)->count();
            $userData->is_followed_by_me = ($follower->where("followed_by_id", Auth::id())->where("user_id", $userData->id)->first()) ? true : false;
            return response()->json(
                [
                    "data" => $userData, 
                    "errors" => []
                ], 200
            );
        }
        return response()->json(
            [
                "data" => [], 
                "errors" => ["Internal Server Error"]
            ], 500
        );
    }
}
