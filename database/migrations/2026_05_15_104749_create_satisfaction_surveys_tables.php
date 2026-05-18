<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSatisfactionSurveysTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('satisfaction_surveys_tables', function (Blueprint $table) {
            $table->id('survey_id');
            $table->uuid('uuid')->unique();
            $table->integer('ticket_id');

            $table->unsignedTinyInteger('fastness')->nullable();
            $table->unsignedTinyInteger('resolution')->nullable();
            $table->unsignedTinyInteger('attention')->nullable();

            $table->enum('status', ['pending', 'completed', 'not_answered'])->default('pending');

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('ticket_id')->references('TicketID')->on('tickets')->onDelete('cascade');

            $table->unique('ticket_id');
            $table->index('status');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('satisfaction_surveys_tables');
    }
}
