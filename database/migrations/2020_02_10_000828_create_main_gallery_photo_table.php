<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMainGalleryPhotoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'main_gallery_photo', function (Blueprint $table) {
                $table->string('property_code')->charset('ascii');
                $table->timestamps();

                $table->foreign('property_code')
                    ->references('code')->on('properties');
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
        Schema::dropIfExists('main_gallery_photo');
    }
}
