<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePropertiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned();
            // $table->increments('id')->unsigned();
            $table->char('hash', 32)->index()->charset('ascii')->default(md5(time()));
            $table->uuid('code')->unique();
            //$table->string('code')->storedAs("CONCAT(CASE WHEN country = 'NG' THEN 'SC1010' ELSE 'SC5010' END, `id`)")->index();
            $table->string('country', 2)->charset('ascii');
            $table->string('photo', 100)->charset('ascii');
            $table->json('photos');// must not give charset type to json type
            $table->string('video', 100)->charset('ascii')->nullable();
            $table->string('brochure', 100)->charset('ascii')->nullable();
            $table->string('main_title', 80)->charset('utf8');
            $table->string('side_title', 80)->charset('utf8');
            $table->string('heading_title', 80)->charset('utf8');
            $table->string('description_text', 1000);
            $table->string('state', 25)->index()->charset('utf8');
            $table->string('city', 35)->index()->charset('utf8');
            $table->string('suburb', 45)->index()->charset('utf8');
            $table->string('type', 25)->charset('utf8');
            $table->integer('interior_surface')->unsigned();
            $table->integer('exterior_surface')->unsigned();
            $table->json('features');
            $table->boolean('is_exclusive')->index()->nullable();
            $table->integer('price')->unsigned()->index()->nullable();
            $table->integer('price_lower_range')->index()->unsigned()->nullable();
            $table->integer('price_upper_range')->index()->unsigned()->nullable();
            $table->softDeletes();                
            $table->dateTime('created_at', 0)->default(gmdate("Y-m-d H:i:s"));
            $table->dateTime('updated_at', 0)->nullable();
            $table->dateTime('viewed_at', 0)->index()->nullable()->default(gmdate("Y-m-d H:i:s"));
            $table->dateTime('year_built', 0)->nullable()->default(gmdate("Y"));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('properties');
    }
}