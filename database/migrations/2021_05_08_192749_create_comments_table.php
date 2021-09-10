<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("author_id");
            $table->unsignedBigInteger("post_id");
            $table->unsignedBigInteger("parent_id")->nullable();
            $table->text("comment_content");
            $table->boolean("deleted")->default(0);
            $table->foreign('author_id')->references('id')->on('users')->onDelete('cascade');;
            $table->foreign('parent_id')->references('id')->on('comments')->onDelete('cascade');;
            $table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');;
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
        Schema::dropIfExists('comments');
    }
}
