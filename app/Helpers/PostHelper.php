<?php
namespace App\Helpers;
use App\Models\Post;
use App\Models\Reaction;
use App\Models\Hashtag;
use App\Models\Follower;
use App\Models\SharedPost;
use App\Helpers\ReactionHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;
/* 
 *
 * Post Helper
 * 
 */

class PostHelper{
    public static function dateDiff($data){
        $now = Carbon::now();
        $diff = $now->subSeconds($now->diffInSeconds("2021-07-30 15:36:49"))->diffForHumans();
        return $diff;
    }
    public static function generatePost($post){
        $followings_data_object = Follower::with("user:id,username")->where("followed_by_id", Auth::id())->get();
        $followings_ids = $followings_data_object->map(function($item, $key){
            return $item->user_id;
        });
        $reactions_data = Reaction::where("post_id",$post->id)->with("reactor:id,username")->get(["id", "reaction_type", "post_id","user_id"]);
        $reactions_data = $reactions_data->where("post_id", $post->id);
        $post->total_reactions = $reactions_data->count();
        $post->top_reactions = $reactions_data->groupBy("reaction_type")->map(function($item, $key){
            return count($item->map(function($item, $key){return $item->id;}));
        })->sort(function($a, $b){
            return ($a < $b) ? 1 : -1;
        })->flip()->flatten();
        $post->reacted_by_viewer = ($reactions_data->where("user_id", Auth::id())->first()) ? true : false;
        $post->reacted_by_mutual = $reactions_data->whereIn("user_id", $followings_ids)->map(function($item, $key){
            return $item->reactor->username;
        });
        return $post;
    }
    public static function generatePostCollection($posts){
        $post_ids = $posts->map(function($item, $key){
            return $item->id;
        });

        
    }
    public static function hashtagCheck($post_model){
        if(preg_match_all("/#(\w+)/", $post_model->post_content, $hashtags)){
            $hashtags_array = []; 
            $i = 0;
            foreach($hashtags[0] as $hashtag){
                $hashtags_array[$i] = [];
                $hashtags_array[$i]["post_id"] = $post_model->id;
                $hashtags_array[$i]["hashtag_name"] = $hashtag;
                $hashtags_array[$i]["created_at"] = Carbon::now();
                $hashtags_array[$i]["updated_at"] = Carbon::now();
                $i++;
            }
            Hashtag::insert($hashtags_array);
        }
    }
}