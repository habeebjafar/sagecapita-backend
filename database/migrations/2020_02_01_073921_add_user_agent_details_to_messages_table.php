<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserAgentDetailsToMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(
            'messages', function (Blueprint $table) {
                $table->ipAddress('ip_address')->charset('ascii')->nullable();
                $table->text('user_agent')->charset('utf8')->nullable();
                $table->text('referrer_page')->charset('utf8')->nullable();
                $table->char('lang', 5)->charset('ascii')->nullable();
                $table->string('os')->charset('ascii')->nullable();
                $table->smallInteger('screen_width')->unsigned()->nullable();
                $table->smallInteger('screen_height')->unsigned()->nullable();
                $table->smallInteger('screen_availWidth')->unsigned()->nullable();
                $table->smallInteger('screen_availHeight')->unsigned()->nullable();
                $table->tinyInteger('color_depth')->unsigned()->nullable();
                $table->tinyInteger('pixel_depth')->unsigned()->nullable();
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('messages', function (Blueprint $table) {
            //
        });
    }
}
