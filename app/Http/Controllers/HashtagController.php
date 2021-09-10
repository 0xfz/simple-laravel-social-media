<?php

namespace App\Http\Controllers;
use App\Models\Hashtag;
use Illuminate\Http\Request;

class HashtagController extends Controller
{
    public function trendingHashtags(){
        $hashtags = DB::table("hashtags")->select('hashtag_name', DB::raw('count(*) as total'))->whereRaw("DATEDIFF(CURRENT_TIMESTAMP,hashtags.created_at) <= 7")->groupBy('hashtag_name')->get();
        $hashtags = array_values($hashtags->toArray());
        return response()->json(
            [
                "data" => $hashtags,
                "errors" => []
            ], 
            200
        );
        
    }
    public function getHashtagPosts($hashtag_name, $sort_by){
        $result_posts = [];
        switch($sort_by){
            case "trending":
                $trending_posts = DB::table("posts")->select("posts.*")->join("hashtags", function($q) use($hashtag_name){
                    $q->where("hashtags.hashtag_name", $hashtag_name)->on("hashtags.post_id", "=", "posts.id");
                })->whereRaw("TIMEDIFF(CURRENT_TIMESTAMP, posts.created_at) <= TIMEDIFF('24:00:00', '0:0:0')")
                ->orderByRaw("(
                    (
                        SELECT count(id) 
                        FROM reactions 
                        WHERE reactions.post_id = posts.id
                    ) + (
                        SELECT count(id) 
                        FROM shared_posts 
                        WHERE shared_posts.post_id = posts.id
                    ) * (
                        HOUR(
                            TIMEDIFF(CURRENT_TIMESTAMP, posts.created_at)
                        )/24
                    )
                ) ASC");
            case "latest":
                $latest_posts = DB::table("posts")
                                ->selectRaw("posts.*")
                                ->join("hashtags", "hashtags.post_id", "=", "posts.id")
                                ->whereRaw("TIMEDIFF(CURRENT_TIMESTAMP, shared_posts.created_at) <= TIMEDIFF('24:00:00', '0:0:0')")
                                ->where("hashtags.hashtag_name", $hashtag_name)
                                ->get();
            break;
        }
    }
}
