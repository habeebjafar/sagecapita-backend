<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFavoritesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned();
            $table->string('property_code')->charset('ascii');
            $table->bigInteger('customer_id')->unsigned();
            $table->softDeletes();                
            $table->dateTime('created_at', 0)->default(gmdate("Y-m-d H:i:s"));
            $table->dateTime('updated_at', 0)->nullable();

            $table->unique(['property_code', 'customer_id']);

            $table->foreign('property_code')->references('code')->on('properties');
            $table->foreign('customer_id')->references('id')->on('customers');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('favorites');
    }
}
