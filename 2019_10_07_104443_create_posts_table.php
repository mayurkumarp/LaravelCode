<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

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
            $table->bigIncrements('post_id')->unsigned();
            $table->bigInteger('user_id')->unsigned();
            $table->string('title', 100);
            $table->integer('status');
            $table->string('image', 250);
            $table->string('file_type', 20);
            $table->string('thumbnail', 250);
            $table->string('type', 15); // here type i.e. world or state chat.
            $table->string('promot_time'); // based on type promot time will be respectively 24 or 12 Hours.
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
