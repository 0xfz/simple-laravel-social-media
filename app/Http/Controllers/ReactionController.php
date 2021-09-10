<?php

namespace App\Http\Controllers;
use App\Models\Reaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
class ReactionController extends Controller
{
    public static $reaction_types = ["laugh", "sad", "happy", "angry", "love", "poop"];  # reaction_type list
    public static $post_types = ["comment" => "comment_id", "post" => "post_id"]; # post_type list 

    public function reactionList($post_type, $post_id, $reaction_type){
        $reaction = new Reaction;
        if(array_key_exists($post_type, self::$post_types)){ # checking whether the rection type is exist or not
            $id_type = self::$post_types[$post_type];
            if($reaction_type == "all"){
                $data = $reaction::where($id_type, $post_id)->get();
            }else{
                if(!in_array($reaction_type, self::$reaction_types)){
                    return response()->json([
                        "status" => "error",
                        "msg" => "Invalid reaction type"
                    ], 404);
                }else{
                    $data = $reaction::where($id_type, $post_id)->where("reaction_type", $reaction_type)->cursorPaginate(10);
                }
            }
            return response()->json([
                "status" => "ok",
                "results" => $data
            ], 200);
        }else{
            return response()->json([
                "status" => "error",
                "msg" => "Comment or Post not found."
            ], 404);
        }

    }
    public function addReaction($post_type, $post_id, $reaction_type){
        // Add and change the reaction
        if(array_key_exists($post_type, self::$post_types)){ # checking whether the rection type is exist or not
            $id_type = self::$post_types[$post_type];
            if(!in_array($reaction_type, self::$reaction_types)){
                return response()->json([
                    "status" => "error",
                    "msg" => "Invalid reaction type"
                ], 404);
            }
            $reaction = new Reaction;
            $isExist = $reaction::select("user_id","reaction_type")->with(["reactor" => function($query){
                $query->select("id", "username", "display_name");
            }])->where($id_type, $post_id)->where("user_id", Auth::id())->where("reaction_type", $reaction_type)->first();
            if($isExist){
                $reaction = $isExist;
            }
            $reaction->$id_type = $post_id;
            $reaction->reaction_type = $reaction_type;
            $reaction->user_id = Auth::id();
            $commit = $reaction->save();
            if($commit){
                $feedback_data = $reaction->select("user_id","reaction_type")->with(["reactor" => function($query){
                    $query->select("id", "username", "display_name");
                }])->first()->makeHidden("user_id");
                return response()->json([
                    "status" => "ok",
                    "feedback_data" => $feedback_data
                ], 200);
            }
            return response()->json([
                "status" => "error",
                "msg" => "Internal Server Error"
            ], 500);
        }else{
            return response()->json([
                "status" => "error",
                "msg" => "Comment or Post not found."
            ], 404);
        }
    }
    public function deleteReaction($post_type, $post_id){
        if(array_key_exists($post_type, self::$post_types)){ # checking whether the rection type is exist or not
            $id_type = self::$post_types[$post_type];
            $reaction = new Reaction;
            $row = $reaction::where("user_id", Auth::id())->where($id_type, $post_id);
            $commit = $row->delete();
            if($commit){
                return response()->json([
                    "status" => "ok"
                ], 200);
            }
            return response()->json([
                "status" => "error",
                "msg" => "Internal Server Error"
            ], 500);
        }else{
            return response()->json([
                "status" => "error",
                "msg" => "Comment or Post not found."
            ], 404);
        }
    }   
}
