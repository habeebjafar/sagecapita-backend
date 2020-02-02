<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveUserAgentDetailsAndChangeLanguageInLeadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(
            'leads', function (Blueprint $table) {
                // $table->char('language', 2)->charset('ascii')->nullable()->change();
                $table->dropColumn(
                    [
                    'ip_address', 
                    'user_agent', 
                    'referrer_page', 
                    'os', 
                    'screen_width', 
                    'screen_height', 
                    'screen_availWidth', 
                    'screen_availHeight', 
                    'color_depth', 
                    'pixel_depth'
                    ]
                );
            }
        );

        \DB::statement(
            "ALTER TABLE `leads` MODIFY `language` CHAR(2) CHARACTER SET ascii"
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('leads', function (Blueprint $table) {
            //
        });
    }
}
