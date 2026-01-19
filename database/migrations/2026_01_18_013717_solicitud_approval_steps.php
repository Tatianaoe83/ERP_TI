<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('solicitud_approval_steps', function (Blueprint $table) {
            $table->id();

            $table->integer('solicitud_id');

            $table->unsignedTinyInteger('step_order');
            $table->enum('stage', ['supervisor', 'gerencia', 'administracion']);

            $table->integer('approver_empleado_id');

            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            $table->dateTime('decided_at')->nullable();
            $table->integer('decided_by_empleado_id')->nullable();

            $table->text('comment')->nullable();

            $table->timestamps();

            $table->foreign('solicitud_id')
                ->references('SolicitudID')
                ->on('solicitudes')
                ->cascadeOnDelete();

            $table->foreign('approver_empleado_id')
                ->references('EmpleadoID')
                ->on('empleados')
                ->restrictOnDelete();

            $table->foreign('decided_by_empleado_id')
                ->references('EmpleadoID')
                ->on('empleados')
                ->nullOnDelete();

            $table->unique(['solicitud_id', 'stage']);
            $table->index(['approver_empleado_id', 'status']);
            $table->index(['solicitud_id', 'step_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitud_approval_steps');
    }
};
