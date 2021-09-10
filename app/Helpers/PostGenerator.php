<?php
namespace App\Helpers;
use App\Models\Post;
use Illuminate\Support\Facades\Auth;
/* 
 *
 * Generate a post data from the current user view
 * 
 */

class PostGenerator{
    public static function generatePostByID($post_id){
        $post = new Post;
        $post_data_raw = $post::find($post_id);
        $post_data = [
            "post" => $post_data_raw,
            "owner" => UserData::getUserbyID($post_data_raw->author_id)
        ];
        return $post_data;
    }
}