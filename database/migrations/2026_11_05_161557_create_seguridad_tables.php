<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->string('name', 255);
            $table->string('username', 45);
            $table->string('email', 255)->nullable();
            $table->dateTime('email_verified_at')->nullable();
            $table->string('password', 255);
            $table->rememberToken();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->bigInteger('GerenciaID')->nullable();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->string('name', 255);
            $table->string('guard_name', 255);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->unique(['name', 'guard_name']);
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->string('name', 255);
            $table->string('guard_name', 255);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->unique(['name', 'guard_name']);
        });

        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->bigInteger('permission_id');
            $table->string('model_type', 255);
            $table->bigInteger('model_id');

            $table->index(['model_id', 'model_type']);
            $table->primary(['permission_id', 'model_type', 'model_id']);
        });

        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->bigInteger('role_id');
            $table->string('model_type', 255);
            $table->bigInteger('model_id');

            $table->index(['model_id', 'model_type']);
            $table->primary(['role_id', 'model_type', 'model_id']);
        });

        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->bigInteger('permission_id');
            $table->bigInteger('role_id')->index('role_has_permissions_role_id_foreign');

            $table->primary(['permission_id', 'role_id']);
        });
    }

    public function down()
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');
        Schema::enableForeignKeyConstraints();
    }
};
