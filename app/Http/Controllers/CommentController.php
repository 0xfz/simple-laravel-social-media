<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CommentController extends Controller
{
    public function addComment($post_id, Request $request){
        $validator = Validator::make($request->all(), [
            "content" => "string|max:300|required",
            "reply_to_comment_id" => "nullable",
        ]);
        if($validator->fails()){
            return response()->json(["error" => true,"msg" => $validator->messages()]);
        }
        $author = Auth::id();
        $content = $request->content;
        $parent_id = $request->reply_to_comment_id;

        $comment = new Comment;
        $comment->comment_content = $content;
        $comment->parent_id = $parent_id;
        $comment->author_id = $author;
        $comment->post_id = $post_id;
        $commit = $comment->save();
        if($commit){
            $feedback_comment = $commit->id;
            return response()->json([
                $comment::find($comment->id)->with("owner:id,username,display_name,image_url")->first(),
                "error" => false
            ]);
        }
        return response()->json(["error" => false]);
        
    }
    public function delete($comment_id){
        # this function is not actually delete the comment but it just change the deleted to 1 (true)
        $comment = new Comment;
        $comment = $comment::join("posts", function($query){
            $query->where("posts.author_id", Auth::id());
        })->first();
        if($comment){
            $comment->deleted = 1; 
            $delete = $comment->save();
            if($delete) return response()->json(["status" => "ok"]);
        }else{
            return response()->json([
                "status" => "error",
                "msg" => "You don't have permission to delete this comment."
            ]);
        }
        return response()->json([
            "status" => "error",
            "msg" => "Internal Server Error"
        ]);
    }
    public function getMainComments($post_id){
        $comments = DB::table("comments")->where("post_id", $post_id)->where("parent_id", null)->cursorPointer(12);
        
    }
    public function getCommentReplies($comment_id){
        
    }
}
