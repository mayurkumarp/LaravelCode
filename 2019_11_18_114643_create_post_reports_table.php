<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePostReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('post_reports', function (Blueprint $table) {
            $table->bigIncrements('post_report_id')->unsigned();
            $table->bigInteger('post_id')->unsigned();
            $table->bigInteger('reported_by')->unsigned(); // user_id
            $table->text('message');
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
        Schema::dropIfExists('post_reports');
    }
}
