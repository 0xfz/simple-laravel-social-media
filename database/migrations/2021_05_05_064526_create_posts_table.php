<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('author_id');
            $table->string("post_content", 300)->nullable();
            $table->unsignedBigInteger("repost_id")->nullable();
            $table->unsignedBigInteger("reply_to_id")->nullable();
            $table->boolean("can_comment")->default(1);
            $table->enum('visibility', ["public", "private"])->default("public");            
            $table->foreign('author_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('reply_to_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('repost_id')->references('id')->on('posts')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('posts');
    }
}
