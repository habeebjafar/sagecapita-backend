<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCodeStatusToMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(
            'messages', 
            function (Blueprint $table) {
                $table->uuid('code')->charset('ascii');
                $table->boolean('is_done');

                $table->unique('code');
                $table->index('is_done');
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
        Schema::table(
            'messages', 
            function (Blueprint $table) {
                $table->dropUnique('code');
                $table->dropIndex('is_done');
                $table->dropColumn(['code', 'is_done']);
            }
        );
    }
}
