<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\SharedPost;
use App\Models\Follower;
use App\Models\Hashtag;
use App\Models\User;
use App\Models\Reaction;
use App\Helpers\ReactionHelper;
use App\Helpers\PostHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class PostController extends Controller
{
    public function getPostById($id){
        $post = new Post;
        $posts = $post::with([
            "owner" => function($q){
            $q->select(
                "users.id", 
                "users.username", 
                "users.display_name", 
                "users.image_url", 
                "users.bio", 
                "users.is_verified", 
                "users.is_private", 
                DB::raw("users.created_at as joined_at"), 
                "f2.followers_count", 
                "f1.followings_count", 
                DB::raw("IF(f3.is_followed_by_me = 1, 1, 0) as is_followed_by_me")
            )->leftJoinSub(
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
        }])->where("id", $id)->cursorPaginate(12);
        $posts = PostHelper::generatePostCollection($posts);
        return response()->json([
            "status" => "ok",
            "results" => $posts
        ]);
    }
    public function getPostsByUserID($id){
        $post = new Post;
        $shared_posts = DB::table("posts")->select("posts.*", DB::raw("shared_posts.user_id as shared_by"), DB::raw("shared_posts.created_at as at"))->join("shared_posts", function($query) use($id){
            $query
            ->where("shared_posts.user_id", $id)
            ->on("shared_posts.post_id", "=", "posts.id");
        });
        $all_user_posts = DB::query()->select(
            "user_posts.*",
            "logged_in_user_reactions.viewer_reaction_type",
            "logged_in_user_reactions.reacted_by_viewer",
            "logged_in_user_shared_posts.shared_by_viewer",
            "total_reactions.reactions_count",
            "top_reactions.top_reaction_type",
        )->fromSub(function($query) use($id, $shared_posts){
            $query->select("posts.*", DB::raw("null as shared_by"), DB::raw("posts.created_at as at"))
            ->from("posts")
            ->where("author_id", $id)
            ->unionAll($shared_posts);
        }, "user_posts")
        ->leftJoinSub(
            DB::table("reactions")
            ->select("post_id",DB::raw("count(*) as reactions_count"))
            ->groupBy("post_id"),
            "total_reactions", function($query){
                $query->on("total_reactions.post_id", "=", "user_posts.id");
            }
        )->leftJoinSub(
            DB::table("reactions")
            ->select("user_id", "post_id", DB::raw("reaction_type as viewer_reaction_type"), DB::raw("1 as reacted_by_viewer"))
            ->where("user_id", Auth::id()),
            "logged_in_user_reactions",
            function($query){
                $query->on("logged_in_user_reactions.post_id", "=", "user_posts.id");
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
                $query->on("top_reactions.post_id", "=", "user_posts.id");
            }

        )->leftJoinSub(
            DB::table("shared_posts")
            ->select("user_id", "post_id", DB::raw("1 as shared_by_viewer"))
            ->where("user_id", Auth::id()),
            "logged_in_user_shared_posts",
            function($query){
                $query->on("logged_in_user_shared_posts.post_id", "=", "user_posts.id");
            }
        )->orderBy("user_posts.at", "DESC")->cursorPaginate(12);
        
        $user_ids = [];

        foreach($all_user_posts as $post){
            if($post->shared_by != null){
                array_push($user_ids, $post->shared_by);
            }
            array_push($user_ids, $post->author_id);
        }

        $users = User::select(
            "users.*", 
            "followers.followers_count", 
            "followings.followings_count", 
            DB::raw("IF(mutuals.followed_by_viewer = 1, 1, 0) as followed_by_viewer")
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
                DB::raw("1 as followed_by_viewer")
            )->where("followed_by_id", Auth::id()), 
            "mutuals", function($q){
                $q->on("mutuals.user_id", "=", "users.id");
        })->whereIn("id", $user_ids)
        ->get()
        ->makeHidden([
            "email", 
            "email_verified_at"
        ]);
        $users = $users->map(function($item, $key){
            $item->is_followed_by_me = ($item->is_followed_by_me) ? true : false;
            $item->followings_count = ($item->followings_count != null) ? $item->followings_count : 0;
            $item->followers_count = ($item->followers_count != null) ? $item->followers_count : 0;
            $item->is_verified = ($item->is_verified) ? true : false;
            $item->is_private = ($item->is_private) ? true : false;
            return $item;

        });

        foreach($all_user_posts as $item){
            $item->reacted_by_viewer = ($item->reacted_by_viewer) ? true : false;
            $item->shared_by_viewer = ($item->shared_by_viewer) ? true : false;
            $item->reactions_count = ($item->reactions_count != null) ? $item->reactions_count : 0;
            if($item->shared_by != null){
                $item->shared_by = $users->where("id", $item->shared_by)->values();
            }
            $item->owner = $users->where("id", $item->author_id)->values();
            unset($item->at);
        }

        return response()->json([
            "status" => "ok",
            "results" => $all_user_posts
        ]);
    }
    public function addPost(Request $request){
        $validator = Validator::make($request->all(), [
            "post_content" => "required|string:300"
        ]);
        if($validator->fails()){
            return response()->json([
                "status" => "error",
                "msg" => $validator->messages()
            ], 400);
        }
        $post = new Post;
        $post->author_id = Auth::id();
        $post->post_content = $request->post_content;
        $c = $post->save();
        if($c){
            $post_model = DB::table("posts")->select(
                "posts.*",
                "logged_in_user_reactions.viewer_reaction_type",
                "logged_in_user_reactions.reacted_by_viewer",
                "logged_in_user_shared_posts.shared_by_viewer",
                "total_reactions.reactions_count",
                "top_reactions.top_reaction_type",
            )->leftJoinSub(
                DB::table("reactions")
                ->select("post_id",DB::raw("count(*) as reactions_count"))
                ->groupBy("post_id"),
                "total_reactions", function($query){
                    $query->on("total_reactions.post_id", "=", "posts.id");
                }
            )->leftJoinSub(
                DB::table("reactions")
                ->select("user_id", "post_id", DB::raw("reaction_type as viewer_reaction_type"), DB::raw("1 as reacted_by_viewer"))
                ->where("user_id", Auth::id()),
                "logged_in_user_reactions",
                function($query){
                    $query->on("logged_in_user_reactions.post_id", "=", "posts.id");
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
                    $query->on("top_reactions.post_id", "=", "posts.id");
                }
    
            )->leftJoinSub(
                DB::table("shared_posts")
                ->select("user_id", "post_id", DB::raw("1 as shared_by_viewer"))
                ->where("user_id", Auth::id()),
                "logged_in_user_shared_posts",
                function($query){
                    $query->on("logged_in_user_shared_posts.post_id", "=", "posts.id");
                }
            )->where("posts.id", $post->id)->first();
            $user = User::select(
                "users.*", 
                "followers.followers_count", 
                "followings.followings_count", 
                DB::raw("IF(mutuals.followed_by_viewer = 1, 1, 0) as followed_by_viewer")
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
                    DB::raw("1 as followed_by_viewer")
                )->where("followed_by_id", Auth::id()), 
                "mutuals", function($q){
                    $q->on("mutuals.user_id", "=", "users.id");
            })->where("id", Auth::id())->first();
            $post_model->reacted_by_viewer = ($post_model->reacted_by_viewer) ? true : false;
            $post_model->shared_by_viewer = ($post_model->shared_by_viewer) ? true : false;
            $post_model->reactions_count = ($post_model->reactions_count != null) ? $post_model->reactions_count : 0;
            $post_model->owner = $user;
            unset($post_model->at);
            PostHelper::hashtagCheck($post_model);
            return response()->json([
                "status" => "ok",
                "data" => $post_model
            ], 200);
        }
        return response()->json([
            "status" => "error",
            "msg" => "Internal Server Error"
        ], 500);

    }
    public function sharePost($post_id){
        $shared_post = new SharedPost;
        $is_exists = $shared_post::where("user_id", Auth::id())->where("post_id", $post_id)->first();
        if($is_exists){
            return response()->json([
                "status" => "error",
                "msg" => "You already shared this post."
            ], 200);
        }
        $shared_post->post_id = $post_id;
        $shared_post->user_id = Auth::id();
        $commit = $shared_post->save();
        if($commit){
            return response()->json([
                "status" => "ok"
            ], 200);
        }
        return response()->json([
            "status" => "error",
            "msg" => "Internal Server Error"
        ], 500);

    }
    public function unsharePost($post_id){
        $is_exists = SharedPost::where("user_id", Auth::id())->where("post_id", $post_id)->first();
        if(!$is_exists){
            return response()->json([
                "status" => "error",
                "msg" => "You didn't share this post before."
            ], 400);
        }
        $commit = $is_exists->delete();
        if($commit){
            return response()->json([
                "status" => "ok"
            ], 200);
        }
        return response()->json([
            "status" => "error",
            "msg" => "Internal Server Error"
        ], 500);
    }
    public function updatePost($post_id,Request $request){
    }
}
