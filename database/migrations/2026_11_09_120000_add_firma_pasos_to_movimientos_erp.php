<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('solicitud_movimiento_pasos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('SolicitudMovimientoID')->index();
            $table->unsignedTinyInteger('step_order');
            $table->string('stage', 30);
            $table->string('stage_label', 80);
            $table->unsignedInteger('approver_empleado_id')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('decided_at')->nullable();
            $table->unsignedInteger('decided_by_empleado_id')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
        });

        Schema::create('solicitud_movimiento_tokens', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('paso_id')->index();
            $table->string('token', 36)->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('solicitud_movimiento_tokens');
        Schema::dropIfExists('solicitud_movimiento_pasos');
    }
};
