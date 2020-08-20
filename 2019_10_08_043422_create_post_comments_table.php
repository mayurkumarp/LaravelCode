<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePostCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('post_comments', function (Blueprint $table) {
            $table->bigIncrements('post_comment_id')->unsigned();
            $table->bigInteger('post_id')->unsigned();
            $table->bigInteger('commented_by')->unsigned(); // user_id
            $table->bigInteger('commented_on')->unsigned(); // post_id
            $table->text('comment');
            $table->text('nested_comments');  // post_comment_id seperated by commas...             
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
        Schema::dropIfExists('post_comments');
    }
}
