<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('facturas', 'CotizacionID')) {
            Schema::table('facturas', function (Blueprint $table) {
                $table->unsignedBigInteger('CotizacionID')->nullable()->after('SolicitudID');
            });
        }

        if (Schema::hasColumn('solicitud_activos', 'FacturaPath')) {
            Schema::table('solicitud_activos', function (Blueprint $table) {
                $table->dropColumn('FacturaPath');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('solicitud_activos', 'FacturaPath')) {
            Schema::table('solicitud_activos', function (Blueprint $table) {
                $table->string('FacturaPath', 255)->nullable()->after('UnidadIndex');
            });
        }

        if (Schema::hasColumn('facturas', 'CotizacionID')) {
            Schema::table('facturas', function (Blueprint $table) {
                $table->dropColumn('CotizacionID');
            });
        }
    }
};
