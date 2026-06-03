<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCometarioUsuarioToSatisfactionSurveysTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('satisfaction_surveys_tables', function (Blueprint $table) {
            $table->text('user_comment')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('satisfaction_surveys_tables', function (Blueprint $table) {
            $table->dropColumn('user_comment');
        });
    }
}
