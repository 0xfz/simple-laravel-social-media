<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix("post")->group(function(){
    Route::group(["middleware" => "auth:api"], function(){
        Route::post("create", [PostController::class, 'addPost']);
        Route::post("{id}/delete", [PostController::class, "addPost"]);
        Route::post("{id}/update", [PostController::class, "addPost"]);
        Route::get("{id}/share", [PostController::class, "sharePost"]);
        Route::get("{id}/unshare", [PostController::class, "unsharePost"]);
    });
    Route::get("{id}", [PostController::class, "getPostById"]);
});  
Route::prefix("{post_type}")->group(function(){
    Route::group(["middleware" => "auth:api"], function(){
        Route::get("{id}/reaction/delete", [ReactionController::class, "deleteReaction"]);
        Route::get("{id}/reaction/list/{reaction_type}", [ReactionController::class, "reactionList"]);
        Route::get("{id}/reaction/add/{reaction_type}", [ReactionController::class, "addReaction"]);
    });
});
Route::prefix("user")->group(function(){
    Route::get("{username}", [UserController::class, "getUserDetailsByUsername"]);
    Route::get("{user_id}", [UserController::class, "getUserDetailsByID"]);
    Route::get("{user_id}/posts", [PostController::class, "getPostsByUserID"]);
    Route::get("{user_id}/followings", [MutualController::class, "getUserAllFollowings"]);
    Route::get("{user_id}/followers", [MutualController::class, "getUserAllFollowers"]);
    Route::get("{user_id}/followed_by_mutuals", [MutualController::class, "getUser"]);
});
Route::prefix("comment")->group(function(){
    Route::group(["middleware" => "auth:api"], function(){
        Route::post("{post_id}/add", [CommentController::class, "add"]);
        Route::get("delete/{comment_id}", [CommentController::class, "delete"]);
    });
    Route::get("{comment_id}", [CommentController::class, "allComments"]);
});
Route::prefix("page")->group(function(){
    Route::get("mutual", [MutualController::class, "getMutualPosts"])->middleware("auth:api");
    Route::get("trending", [PostController::class, "trendingHashtags"]);
});
Route::prefix("mutual")->group(function(){
    Route::group(["middleware" => "auth:api"], function(){
        Route::get("{user_id}/follow", [MutualController::class, "follow"]);
        Route::get("{user_id}/unfollow", [MutualController::class, "unfollow"]);
    });
});
Route::prefix("auth")->group(function(){
    Route::post("register", [AuthController::class, 'register']);
    Route::post("login", [AuthController::class, 'login']);
});

