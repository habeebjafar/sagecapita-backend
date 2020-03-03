<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePropertyGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('property_groups', function (Blueprint $table) {
            // $table->bigIncrements('id');
            $table->increments('id')->unsigned();
            $table->char('hash', 32)->index()->charset('ascii')->default(md5(time()));
            $table->string('name', 25)->charset('utf8');
            $table->string('class', 20)->charset('utf8');
            $table->string('photo', 100)->charset('ascii');
            $table->softDeletes();                
            $table->dateTime('created_at', 0)->default(gmdate("Y-m-d H:i:s"));
            $table->dateTime('updated_at', 0)->nullable();

            $table->unique(['name', 'class']);
        });

        \DB::statement('ALTER TABLE property_groups ADD FULLTEXT (name, class)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('property_groups');
    }
}
