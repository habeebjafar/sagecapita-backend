<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('first_name');
            $table->string('last_name');
            // $table->string('full_name')->storedAs("CONCAT(`first_name`, ' ', `last_name`)");
            $table->string('email')->charset('utf8');
            $table->string('phone')->charset('ascii');
            $table->string('country')->charset('utf8');
            $table->ipAddress('ip_address')->charset('ascii')->nullable();
            $table->text('user_agent')->charset('utf8')->nullable();
            $table->text('referrer_page')->charset('utf8')->nullable();
            $table->string('language')->charset('utf8')->nullable();
            $table->string('os')->charset('ascii')->nullable();
            $table->smallInteger('screen_width')->unsigned()->nullable();
            $table->smallInteger('screen_height')->unsigned()->nullable();
            $table->smallInteger('screen_availWidth')->unsigned()->nullable();
            $table->smallInteger('screen_availHeight')->unsigned()->nullable();
            $table->tinyInteger('color_depth')->unsigned()->nullable();
            $table->tinyInteger('pixel_depth')->unsigned()->nullable();
            $table->softDeletes();                
            $table->dateTime('created_at', 0)->default(gmdate("Y-m-d H:i:s"));
            $table->dateTime('updated_at', 0)->nullable();

            $table->unique(['email', 'phone', 'country']);
        });

        // \DB::statement('ALTER TABLE leads ADD FULLTEXT (full_name)');
        \DB::statement('ALTER TABLE leads ADD FULLTEXT (first_name, last_name)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('leads');
    }
}
