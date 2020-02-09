<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned();
            $table->char('hash', 32)->index()->charset('ascii')->default(md5(time()));
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique()->charset('utf8');
            $table->string('phone')->unique()->charset('ascii');
            $table->string('address')->charset('utf8')->nullable();
            $table->string('country')->charset('utf8')->nullable();
            $table->string('password')->charset('ascii');
            $table->integer('number_of_logins')->unsigned()->default(0);
            $table->ipAddress('ip_address')->charset('ascii')->nullable();
            $table->text('user_agent')->charset('ascii')->nullable();
            $table->text('referrer_page')->charset('ascii')->nullable();
            $table->char('lang', 5)->charset('ascii')->nullable();
            $table->string('os')->charset('ascii')->nullable();
            $table->smallInteger('screen_width')->unsigned()->nullable();
            $table->smallInteger('screen_height')->unsigned()->nullable();
            $table->smallInteger('screen_availWidth')->unsigned()->nullable();
            $table->smallInteger('screen_availHeight')->unsigned()->nullable();
            $table->tinyInteger('color_depth')->unsigned()->nullable();
            $table->tinyInteger('pixel_depth')->unsigned()->nullable();
            $table->smallInteger('secs_to_submit')->unsigned()->nullable();
            $table->boolean('suspended')->nullable();
            $table->softDeletes();                
            $table->dateTime('created_at', 0)->default(gmdate("Y-m-d H:i:s"));
            $table->dateTime('updated_at', 0)->nullable();
            $table->dateTime('last_login', 0)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customers');
    }
}
