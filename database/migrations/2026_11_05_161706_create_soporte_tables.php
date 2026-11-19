<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tipotickets', function (Blueprint $table) {
            $table->integer('TipoID', true);
            $table->string('NombreTipo', 200);
            $table->integer('TiempoEstimadoMinutos')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('subtipo', function (Blueprint $table) {
            $table->integer('SubtipoID', true);
            $table->string('NombreSubtipo', 100);
            $table->integer('TipoID')->nullable()->index('TipoID');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('tertipo', function (Blueprint $table) {
            $table->integer('TertipoID', true);
            $table->string('NombreTertipo', 100);
            $table->integer('SubTipoID')->nullable()->index('FK_SubTipo');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('tickets', function (Blueprint $table) {
            $table->integer('TicketID', true);
            $table->bigInteger('CodeAnyDesk')->nullable();
            $table->text('Descripcion');
            $table->longText('imagen')->nullable();
            $table->bigInteger('Numero')->nullable();
            $table->enum('Prioridad', ['Baja', 'Media', 'Alta'])->nullable();
            $table->enum('Estatus', ['Pendiente', 'En progreso', 'Cerrado'])->nullable()->default('Pendiente');
            $table->text('Resolucion')->nullable();
            $table->string('Clasificacion', 50)->nullable();
            $table->dateTime('FechaInicioProgreso')->nullable();
            $table->dateTime('FechaFinProgreso')->nullable();
            $table->dateTime('fecha_ultima_notificacion_exceso')->nullable();
            $table->integer('ResponsableTI')->nullable()->index('FK_Responsable_TI');
            $table->integer('EmpleadoID')->index('FK_Empleado_Reporta');
            $table->integer('TipoID')->nullable()->index('FK_TipoID');
            $table->integer('SubtipoID')->nullable();
            $table->integer('TertipoID')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent();
            $table->dateTime('deleted_at')->nullable();
            $table->enum('Categoria', ['Problema', 'Servicio'])->nullable();
        });

        Schema::create('ticket_chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('ticket_id');
            $table->longText('mensaje');
            $table->string('remitente');
            $table->string('correo_remitente')->nullable();
            $table->string('nombre_remitente')->nullable();
            $table->text('contenido_correo')->nullable();
            $table->string('message_id')->nullable();
            $table->string('thread_id')->nullable();
            $table->longText('adjuntos')->nullable();
            $table->boolean('es_correo')->default(false);
            $table->boolean('leido')->default(false);
            $table->integer('notificaciones_pendientes')->default(0);
            $table->timestamp('fecha_ultima_respuesta')->nullable();
            $table->timestamps();
        });

        Schema::create('satisfaction_surveys_tables', function (Blueprint $table) {
            $table->bigIncrements('survey_id');
            $table->char('uuid', 36)->unique();
            $table->integer('ticket_id')->unique();
            $table->unsignedTinyInteger('fastness')->nullable();
            $table->unsignedTinyInteger('resolution')->nullable();
            $table->unsignedTinyInteger('attention')->nullable();
            $table->enum('status', ['pending', 'completed', 'not_answered', 'partially_completed'])->default('pending')->index();
            $table->text('user_comment')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
        });

        Schema::create('ticket_tarea_metricas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->bigInteger('creado_por_user_id')->nullable();
            $table->unsignedTinyInteger('dia_compromiso')->default(5);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('ticket_tareas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('titulo', 200);
            $table->text('razon')->nullable();
            $table->integer('asignado_id')->nullable();
            $table->string('asignados_ids', 255)->nullable();
            $table->bigInteger('creado_por_user_id')->nullable();
            $table->date('fecha_compromiso')->nullable();
            $table->string('estatus', 30)->default('pendiente');
            $table->string('tipo', 20)->default('evento');
            $table->unsignedBigInteger('metrica_id')->nullable();
            $table->unsignedTinyInteger('periodo_mes')->nullable();
            $table->unsignedSmallInteger('periodo_anio')->nullable();
            $table->string('prioridad', 20)->default('normal');
            $table->timestamp('completada_at')->nullable();
            $table->timestamp('notificado_critica_at')->nullable();
            $table->timestamps();

            $table->index(['estatus', 'fecha_compromiso']);
            $table->unique(['metrica_id', 'periodo_mes', 'periodo_anio'], 'ticket_tareas_metrica_periodo_unique');
            $table->index(['asignado_id', 'estatus']);
        });

        Schema::create('ticket_tarea_historial', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tarea_id')->index('ticket_tarea_historial_tarea_id_foreign');
            $table->bigInteger('user_id')->nullable();
            $table->string('accion', 40);
            $table->text('motivo')->nullable();
            $table->date('fecha_compromiso_anterior')->nullable();
            $table->date('fecha_compromiso_nueva')->nullable();
            $table->integer('asignado_anterior_id')->nullable();
            $table->integer('asignado_nuevo_id')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
        });

        Schema::create('solicitudes', function (Blueprint $table) {
            $table->integer('SolicitudID', true);
            $table->enum('Motivo', ['Nuevo Ingreso', 'Reemplazo por fallo o descompostura', 'Renovación', 'Equipo Nuevo'])->nullable();
            $table->text('DescripcionMotivo')->nullable();
            $table->text('Requerimientos');
            $table->float('Presupuesto', 10)->nullable();
            $table->string('Estatus', 100);
            $table->integer('ObraID');
            $table->integer('GerenciaID');
            $table->integer('PuestoID');
            $table->integer('EmpleadoID');
            $table->string('Proyecto', 100);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent();
            $table->dateTime('deleted_at')->nullable();
            $table->unsignedBigInteger('cancelado_por')->nullable();
            $table->unsignedBigInteger('cancelado_por_empleado_id')->nullable();
            $table->text('motivo_cancelacion')->nullable();
            $table->timestamp('fecha_cancelacion')->nullable();
        });

        Schema::create('cotizaciones', function (Blueprint $table) {
            $table->bigIncrements('CotizacionID');
            $table->integer('SolicitudID');
            $table->string('Proveedor', 255);
            $table->text('Descripcion');
            $table->decimal('Precio', 10);
            $table->decimal('CostoEnvio', 10)->nullable();
            $table->integer('TiempoEntrega')->nullable()->comment('Días');
            $table->text('Observaciones')->nullable();
            $table->enum('Estatus', ['Pendiente', 'Seleccionada', 'Rechazada'])->default('Pendiente');
            $table->integer('NumeroPropuesta')->comment('1, 2 o 3');
            $table->integer('NumeroProducto')->default(1)->comment('Identificador de producto dentro de la propuesta. Permite múltiples productos por propuesta.');
            $table->string('NombreEquipo', 255)->nullable();
            $table->string('NumeroParte', 255)->nullable();
            $table->unsignedInteger('Cantidad')->default(1)->comment('Cantidad de unidades del producto');
            $table->string('Unidad', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('departamento_requerimientos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->enum('categoria', ['Productos base', 'Programas Especiales', 'Carpetas', 'Escaner', 'Impresora']);
            $table->string('nombre', 150);
            $table->integer('DepartamentoID');
            $table->boolean('seleccionado')->default(false);
            $table->boolean('realizado')->default(false);
            $table->boolean('opcional')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();

            $table->unique(['DepartamentoID', 'nombre'], 'uq_depto_nombre');
            $table->index(['DepartamentoID', 'realizado'], 'idx_depto_realizado');
            $table->index(['DepartamentoID', 'seleccionado'], 'idx_depto_seleccionado');
        });

        Schema::create('solicitud_activos', function (Blueprint $table) {
            $table->bigIncrements('SolicitudActivoID');
            $table->integer('SolicitudID');
            $table->unsignedBigInteger('CotizacionID')->index('solicitud_activos_cotizacionid_foreign');
            $table->integer('NumeroPropuesta')->default(0);
            $table->unsignedInteger('UnidadIndex')->default(1);
            $table->date('FechaEntrega')->nullable();
            $table->integer('EmpleadoID')->nullable()->index('solicitud_activos_empleadoid_foreign');
            $table->integer('DepartamentoID')->nullable()->index('solicitud_activos_departamentoid_foreign');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->softDeletes();
            $table->timestamp('fecha_fin_configuracion')->nullable();

            $table->unique(['SolicitudID', 'CotizacionID', 'UnidadIndex'], 'uq_sol_activo_unit');
        });

        Schema::create('solicitud_activo_checklists', function (Blueprint $table) {
            $table->bigIncrements('SolicitudActivoChecklistID');
            $table->unsignedBigInteger('SolicitudActivoID');
            $table->unsignedBigInteger('DepartamentoRequerimientoID')->index('solicitud_activo_checklists_departamentorequerimientoid_foreign');
            $table->boolean('completado')->default(false);
            $table->string('responsable', 80)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->softDeletes();

            $table->unique(['SolicitudActivoID', 'DepartamentoRequerimientoID'], 'uq_activo_req');
        });

        Schema::create('solicitud_approval_steps', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('solicitud_id');
            $table->unsignedTinyInteger('step_order');
            $table->enum('stage', ['supervisor', 'gerencia', 'administracion']);
            $table->integer('approver_empleado_id');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->dateTime('decided_at')->nullable();
            $table->integer('decided_by_empleado_id')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
        });

        Schema::create('solicitud_cotizacion_tokens', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('solicitud_id');
            $table->char('token', 36);
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('revoked_at')->nullable();
            $table->dateTime('used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('solicitud_public_review_tokens', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('approval_step_id')->index('solicitud_public_review_tokens_approval_step_id_foreign');
            $table->char('token', 36)->unique();
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('revoked_at')->nullable();
            $table->dateTime('used_at')->nullable();
            $table->dateTime('notified_at')->nullable();
            $table->dateTime('last_reminder_at')->nullable();
            $table->unsignedSmallInteger('reminders_sent')->default(0);
            $table->timestamps();

            $table->index(['notified_at', 'last_reminder_at'], 'idx_tokens_recordatorios');
            $table->index(['expires_at', 'revoked_at']);
        });

        // Llaves foraneas
        Schema::table('subtipo', function (Blueprint $table) {
            $table->foreign(['TipoID'], 'subtipo_ibfk_1')->references(['TipoID'])->on('tipotickets');
        });

        Schema::table('tertipo', function (Blueprint $table) {
            $table->foreign(['SubTipoID'], 'FK_SubTipo')->references(['SubtipoID'])->on('subtipo');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->foreign(['EmpleadoID'], 'FK_Empleado_Reporta')->references(['EmpleadoID'])->on('empleados');
            $table->foreign(['TipoID'], 'FK_TipoID')->references(['TipoID'])->on('tipotickets');
            $table->foreign(['ResponsableTI'], 'FK_Responsable_TI')->references(['EmpleadoID'])->on('empleados');
        });

        Schema::table('satisfaction_surveys_tables', function (Blueprint $table) {
            $table->foreign(['ticket_id'])->references(['TicketID'])->on('tickets')->onDelete('CASCADE');
        });

        Schema::table('ticket_tareas', function (Blueprint $table) {
            $table->foreign(['asignado_id'])->references(['EmpleadoID'])->on('empleados')->onDelete('SET NULL');
            $table->foreign(['metrica_id'])->references(['id'])->on('ticket_tarea_metricas')->onDelete('SET NULL');
        });

        Schema::table('ticket_tarea_historial', function (Blueprint $table) {
            $table->foreign(['tarea_id'])->references(['id'])->on('ticket_tareas')->onDelete('CASCADE');
        });

        Schema::table('departamento_requerimientos', function (Blueprint $table) {
            $table->foreign(['DepartamentoID'])->references(['DepartamentoID'])->on('departamentos')->onUpdate('CASCADE')->onDelete('CASCADE');
        });

        Schema::table('solicitud_activos', function (Blueprint $table) {
            $table->foreign(['CotizacionID'])->references(['CotizacionID'])->on('cotizaciones');
            $table->foreign(['EmpleadoID'])->references(['EmpleadoID'])->on('empleados');
            $table->foreign(['DepartamentoID'])->references(['DepartamentoID'])->on('departamentos');
            $table->foreign(['SolicitudID'])->references(['SolicitudID'])->on('solicitudes');
        });

        Schema::table('solicitud_activo_checklists', function (Blueprint $table) {
            $table->foreign(['DepartamentoRequerimientoID'])->references(['id'])->on('departamento_requerimientos');
            $table->foreign(['SolicitudActivoID'])->references(['SolicitudActivoID'])->on('solicitud_activos')->onDelete('CASCADE');
        });

        Schema::table('solicitud_public_review_tokens', function (Blueprint $table) {
            $table->foreign(['approval_step_id'])->references(['id'])->on('solicitud_approval_steps')->onDelete('CASCADE');
        });
    }

    public function down()
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('solicitud_public_review_tokens');
        Schema::dropIfExists('solicitud_cotizacion_tokens');
        Schema::dropIfExists('solicitud_approval_steps');
        Schema::dropIfExists('solicitud_activo_checklists');
        Schema::dropIfExists('solicitud_activos');
        Schema::dropIfExists('departamento_requerimientos');
        Schema::dropIfExists('cotizaciones');
        Schema::dropIfExists('solicitudes');
        Schema::dropIfExists('ticket_tarea_historial');
        Schema::dropIfExists('ticket_tareas');
        Schema::dropIfExists('ticket_tarea_metricas');
        Schema::dropIfExists('satisfaction_surveys_tables');
        Schema::dropIfExists('ticket_chats');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('tertipo');
        Schema::dropIfExists('subtipo');
        Schema::dropIfExists('tipotickets');
        Schema::enableForeignKeyConstraints();
    }
};
