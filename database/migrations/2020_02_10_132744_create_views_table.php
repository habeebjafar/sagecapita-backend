<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateViewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('views', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('property_code')->index()->charset('ascii');
            $table->bigInteger('customer_id')->unsigned()->nullable();
            $table->dateTime('created_at', 0)->index()->default(gmdate("Y-m-d H:i:s"));

            $table->foreign('property_code')
                ->references('code')->on('properties');
            $table->foreign('customer_id')
                ->references('id')->on('customers');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('views');
    }
}
