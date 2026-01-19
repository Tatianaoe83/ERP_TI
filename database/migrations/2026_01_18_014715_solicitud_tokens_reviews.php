<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('solicitud_public_review_tokens', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('approval_step_id');

            $table->uuid('token')->unique();

            $table->dateTime('expires_at')->nullable();
            $table->dateTime('revoked_at')->nullable();
            $table->dateTime('used_at')->nullable();

            $table->timestamps();

            $table->foreign('approval_step_id')
                ->references('id')
                ->on('solicitud_approval_steps')
                ->cascadeOnDelete();

            $table->index(['expires_at', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitud_public_review_tokens');
    }
};
