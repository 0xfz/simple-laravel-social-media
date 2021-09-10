<?php

namespace App\Http\Controllers;
use App\Models\Follower;
use App\Helpers\FollowerHelper;
use App\Helpers\PostHelper;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
class MutualController extends Controller
{
    public function follow($user_id){
        /**
         *
         * Method for current user to follow a user
         * @return Response
         * 
         */
        if(!User::find($user_id)){
            return response()->json(["errors" => ["User not found"]], 404);
        }
        $follow = new Follower;
        if($user_id != Auth::id()){
            $isExist = $follow::where("followed_by_id", Auth::id())->where("user_id", $user_id)->first();
            if ($isExist){
                return response()->json(["errors" => ["You already followed this user."]], 403);
            }
            $follow->user_id = $user_id;
            $follow->followed_by_id = Auth::id();
            if($follow->save()){
                return response()->json(["errors" => []], 200);
            }
        }
        return response()->json(["errors" => ["Internal Server Error"]], 500);
    }    
    public function unfollow($user_id){
        /**
         *
         * Method for current user to unfollow a user
         * @return Response
         * 
         */
        $follow = new Follower;
        if(!User::find($user_id)){
            return response()->json(["errors" => ["User not found"]], 404);
        }
        if($follow::where("followed_by_id", $user_id)->where("user_id", Auth::id())->delete()){
            return response()->json(["error" => false], 200);
        }
        return response()->json(["error" => true], 400);
    }
    public function getUserAllFollowers($id, Request $request){
        /*
         *
         * Return all followers from spesific user_id
         * 
         */
        if(!User::find($id)){
            return response()->json(["errors" => ["User not found"]], 404);
        }
        $allRequestParam = $request->all();
        $followers_query = User::select(
            "users.*", 
            "followers.created_at", 
            "f2.followers_count", 
            "f1.followings_count", 
            DB::raw("IF(f3.is_followed_by_me = 1, 1, 0) as is_followed_by_me")
        )->join("followers", function($q) use($id){
            $q->where("followers.user_id", $id);
        })->leftJoinSub(
            DB::table("followers")->
            select(
                "followed_by_id", 
                DB::raw("count(id) as followings_count"),
            )->groupBy("followed_by_id"), 
            "f1", function($q){
                $q->on("f1.followed_by_id", "=", "users.id");
        })->leftJoinSub(
            DB::table("followers")->
            select(
                "user_id", 
                DB::raw("count(id) as followers_count")
            )->groupBy("user_id"), 
            "f2", function($q){
                $q->on("f2.user_id", "=", "users.id");
        })->leftJoinSub(
            DB::table("followers")->
            select(
                "user_id", 
                DB::raw("true as is_followed_by_me")
            )->where("followed_by_id", Auth::id()), 
            "f3", function($q){
                $q->on("f3.user_id", "=", "users.id");
        });
        # Add filter request parameter 
        if(isset($allRequestParam["filter"])){
            if(!empty($allRequestParam["filter"])){
                switch($allRequestParam["filter"]){
                    case "all":
                        $followers_query = $followers_query;
                        break;
                    case "mutualsOnly": # returns all current logged in user's followings from another user followers list
                        $followings_query = $followings_query->leftJoinSub(
                            Follower::select("user_id")->where("followed_by_id", Auth::id()), "mutual_user_ids", function($q) {
                                $q->on("mutual_user_ids.user_id", "=", "users.id");
                            }
                        );
                        break;
                }
            }
        }
        # Add sortBy request parameter
        if(isset($allRequestParam["sortBy"])){
            if(!empty($allRequestParam["sortBy"])){
                switch($allRequestParam["sortBy"]){
                    case "oldToNew":
                        $followers_query = $followers_query->orderBy("followers.created_at", "ASC");
                        break;
                    case "newToOld":
                        $followers_query = $followers_query->orderBy("followers.created_at", "DESC");
                        break;
                }
            }
        }
        $users = $followers_query->get();
        return response()->json(
            [
                "data" => $users, 
                "errors" => []
            ], 200
        );
    }
    public function getUserAllFollowings($id, Request $request){
        /*
         *
         * Return all followings from spesific user_id
         * 
         */
        if(!User::find($id)){
            return response()->json([
                "status" => "error",
                "msg" => "User not found"
            ], 404);
        }
        $allRequestParam = $request->all();
        $followings_query = User::select("users.*", "followers.created_at as SU")->join("followers", function($q) use($id){$q->where("followers.followed_by_id", $id)->on("users.id", "=", "followers.user_id");});
        # add "filter" requst parameter
        if(isset($allRequestParam["filter"])){
            if(!empty($allRequestParam["filter"])){
                switch($allRequestParam["filter"]){
                    case "all":
                        $followings_query = $followings_query;
                        break;
                    case "mutualsOnly":
                        # To get all authenticated_user followings that followed user 
                        $followings_query = $followings_query->leftJoinSub(
                            Follower::select("user_id")->where("followed_by_id", Auth::id()), "mutual_user_ids", function($q) {
                                $q->on("mutual_user_ids.user_id", "=", "users.id");
                            }
                        );
                        break;
                }
            }
        }
        # add "sortBy" requst parameter
        if(isset($allRequestParam["sortBy"])){
            if(!empty($allRequestParam["sortBy"])){
                switch($allRequestParam["sortBy"]){
                    case "oldToNew":
                        $followings_query = $followings_query->orderBy("followers.created_at", "ASC");
                        break;
                    case "newToOld":
                        $followings_query = $followings_query->orderBy("followers.created_at", "DESC");
                        break;
                }
            }
        }
        $users = $followings_query;
        return response()->json([
            "status" => "ok",
            "results" => $users->cursorPaginate(10)
        ], 200);
    }
    public function getMutualPosts(){
        /*
         *
         * Return all posts based on mutual interactions
         * 
         * 
         */

        $mutual_posts = DB::query()->select(
            "timeline.*",
            "logged_in_user_reactions.viewer_reaction_type",
            "logged_in_user_reactions.reacted_by_viewer",
            "logged_in_user_shared_posts.shared_by_viewer",
            "total_reactions.reactions_count",
            "top_reactions.top_reaction_type"
        )->fromSub(
            function($q){
                # To get latest posts from followings 
                $q->from("posts")->select(
                    "posts.*", 
                    DB::raw("null AS reacted_by_mutuals_user_id"), 
                    DB::raw("null AS reacted_by_mutuals_reaction_type"), 
                    DB::raw("null AS reacted_by_mutuals_created_at"), 
                    DB::raw("null AS shared_by"), 
                    "posts.created_at AS at"
                )->join("followers", function($q){
                    $q->where("followers.followed_by_id", Auth::id())  
                    ->on("posts.author_id", "=", "followers.user_id")
                    ->whereRaw("TIMEDIFF(CURRENT_TIMESTAMP, posts.created_at) <= TIMEDIFF('24:00:00', '0:0:0')");
                })->unionAll(
                    # To get the first post of user that got followed by current user in last 24 hours
                    DB::table("posts")->select(
                        "posts.*", 
                        DB::raw("null AS reacted_by_mutuals_user_id"), 
                        DB::raw("null AS reacted_by_mutuals_reaction_type"), 
                        DB::raw("null AS reacted_by_mutuals_created_at"), 
                        DB::raw("null AS shared_by"), 
                        "followers.created_at AS at"
                    )->join("followers", function($q){
                        $q->where("followers.followed_by_id", Auth::id())
                        ->on("posts.author_id", "=", "followers.user_id") # 
                        ->whereRaw("TIMEDIFF(CURRENT_TIMESTAMP, followers.created_at) <= TIMEDIFF('24:00:00', '0:0:0')");
                    })->limit(1)
                )->unionAll(
                    # To get all posts that shared by current user's followings in last 24 hours
                    DB::table("posts")->select(
                        "posts.*", 
                        DB::raw("null AS reacted_by_mutuals_user_id"), 
                        DB::raw("null AS reacted_by_mutuals_reaction_type"), 
                        DB::raw("null AS reacted_by_mutuals_created_at"), 
                        DB::raw("shared_posts.user_id AS shared_by"), 
                        "shared_posts.created_at AS at"
                    )->join("followers", function($q){
                        $q->where("followers.followed_by_id", Auth::id());
                    })->join("shared_posts", function($q){
                        $q->on("shared_posts.user_id", "=", "followers.user_id")
                        ->on("posts.id", "=", "shared_posts.post_id")
                        ->whereRaw("TIMEDIFF(CURRENT_TIMESTAMP, shared_posts.created_at) <= TIMEDIFF('24:00:00', '0:0:0')");
                    })
                )->unionAll(
                    # To get all posts that reacted by current user's followings in last 24 hours
                    DB::table("posts")->select(
                        "posts.*", 
                        DB::raw("reactions.user_id AS reacted_by_mutuals_user_id"), 
                        DB::raw("reactions.reaction_type AS reacted_by_mutuals_reaction_type"), 
                        DB::raw("reactions.created_at AS reacted_by_mutuals_created_at"), 
                        DB::raw("null AS shared_by"), 
                        "reactions.created_at AS at"
                    )->join("followers", function($q){
                        $q->where("followers.followed_by_id", Auth::id());
                    })->join("reactions", function($q){
                        $q->on("reactions.user_id", "=", "followers.user_id")
                        ->on("posts.id", "=", "reactions.post_id")
                        ->whereRaw("TIMEDIFF(CURRENT_TIMESTAMP, reactions.created_at) <= TIMEDIFF('24:00:00', '0:0:0')");
                    })
                );
            }, "timeline"
        )->leftJoinSub(
            DB::table("reactions")
            ->select("post_id",DB::raw("count(*) as reactions_count"))
            ->groupBy("post_id"),
            "total_reactions", function($query){
                $query->on("total_reactions.post_id", "=", "timeline.id");
            }
        )->leftJoinSub(
            DB::table("reactions")
            ->select("user_id", "post_id", DB::raw("reaction_type as viewer_reaction_type"), DB::raw("1 as reacted_by_viewer"))
            ->where("user_id", Auth::id()),
            "logged_in_user_reactions",
            function($query){
                $query->on("logged_in_user_reactions.post_id", "=", "timeline.id");
            }
        )->leftJoinSub(
            DB::query()->select("r4.top_reaction_type", "r4.post_id")->fromSub(function($query){
                $query->select(DB::raw("max(total) as total"), "post_id")->fromSub(function($query){
                    $query->from("reactions")
                    ->select("reaction_type", "post_id", DB::raw("count(*) as total"))
                    ->groupBy("reaction_type", "post_id");
                }, "r1")->groupBy("post_id");
            }, "r2")->joinSub(
                DB::table("reactions")
                ->select(DB::raw("reaction_type as top_reaction_type"), "post_id", DB::raw("count(*) as total"))
                ->groupBy("reaction_type", "post_id"), 
            "r4", 
            function($query){
                $query->on("r4.post_id", "=", "r2.post_id")->on("r4.total", "=", "r2.total");
            }),"top_reactions",function($query){
                $query->on("top_reactions.post_id", "=", "timeline.id");
            }

        )->leftJoinSub(
            DB::table("shared_posts")
            ->select("user_id", "post_id", DB::raw("1 as shared_by_viewer"))
            ->where("user_id", Auth::id()),
            "logged_in_user_shared_posts",
            function($query){
                $query->on("logged_in_user_shared_posts.post_id", "=", "timeline.id");
            }
        )->orderBy("at", "DESC")->cursorPaginate(10);
        $user_ids = [];
        foreach($mutual_posts as $post){
            if($post->shared_by != null){
                array_push($user_ids, $post->shared_by);
            }else if($post->reacted_by_mutuals_user_id != null){
                array_push($user_ids, $post->reacted_by_mutuals_user_id);
            }
            array_push($user_ids, $post->author_id);
        }

        # get all users from author_id in post
        $users = User::select(
            "users.*", 
            "followers.followers_count", 
            "followings.followings_count", 
            DB::raw("IF(mutuals.is_followed_by_me = 1, 1, 0) as is_followed_by_me")
        )->leftJoinSub(
            DB::table("followers")->
            select(
                "followed_by_id", 
                DB::raw("count(id) as followings_count"),
            )->groupBy("followed_by_id"), 
            "followings", function($q){
                $q->on("followings.followed_by_id", "=", "users.id");
        })->leftJoinSub(
            DB::table("followers")->
            select(
                "user_id", 
                DB::raw("count(id) as followers_count")
            )->groupBy("user_id"), 
            "followers", function($q){
                $q->on("followers.user_id", "=", "users.id");
        })->leftJoinSub(
            DB::table("followers")->
            select(
                "user_id", 
                DB::raw("1 as is_followed_by_me")
            )->where("followed_by_id", Auth::id()), 
            "mutuals", function($q){
                $q->on("mutuals.user_id", "=", "users.id");
        })->whereIn("id", $user_ids)
        ->get()
        ->makeHidden([
            "email", 
            "email_verified_at"
        ]);

        # Removing reacted_by or shared_by property that has null value
        $users = $users->map(function($item, $key){
            $item->is_followed_by_me = ($item->is_followed_by_me) ? true : false;
            $item->followings_count = ($item->followings_count != null) ? $item->followings_count : 0;
            $item->followers_count = ($item->followers_count != null) ? $item->followers_count : 0;
            $item->is_verified = ($item->is_verified) ? true : false;
            $item->is_private = ($item->is_private) ? true : false;
            return $item;

        });

        foreach($mutual_posts as $post){
            $post->reacted_by_viewer = ($post->reacted_by_viewer) ? true : false;
            $post->shared_by_viewer = ($post->shared_by_viewer) ? true : false;
            $post->reactions_count = ($post->reactions_count != null) ? $post->reactions_count : 0;
            $post->owner = $users->where("id", $post->author_id)->first();
            if($post->shared_by != null){
                $post->shared_by = $users->where("id", $post->shared_by)->first();
            }else{
                unset($post->shared_by);
            }

            if($post->reacted_by_mutuals_user_id != null){
                $post->reacted_by = new Collection();
                $post->reacted_by["user"] = $users->where("id", $post->reacted_by_mutuals_user_id)->first();
                $post->reacted_by["at"] = $post->reacted_by_mutuals_created_at;
                $post->reacted_by["reaction_type"] = $post->reacted_by_mutuals_reaction_type;
            }else{
                $post->reacted_by = false;
            }
            unset($post->reacted_by_mutuals_reaction_type);
            unset($post->reacted_by_mutuals_created_at);
            unset($post->reacted_by_mutuals_user_id);
        }
        return response()->json([
            "status" => "ok",
            "results" => $mutual_posts 
        ]);

    }
}
            