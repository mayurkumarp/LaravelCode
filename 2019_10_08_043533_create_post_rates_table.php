<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePostRatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('post_rates', function (Blueprint $table) {
            $table->bigIncrements('post_rate_id')->unsigned();
            $table->bigInteger('post_id')->unsigned();
            $table->bigInteger('rated_by')->unsigned(); // user_id
            $table->bigInteger('rated_to')->unsigned(); // user_id who created post
            $table->integer('rate');
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
        Schema::dropIfExists('post_rates');
    }
}
