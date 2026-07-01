<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ciudadanos', function (Blueprint $table) {
            $table->unsignedInteger('puntos')->default(0)->after('zona');
        });

        Schema::table('transacciones', function (Blueprint $table) {
            $table->unsignedInteger('puntos_otorgados')->nullable()->after('monto_total');
        });
    }

    public function down(): void
    {
        Schema::table('transacciones', function (Blueprint $table) {
            $table->dropColumn('puntos_otorgados');
        });

        Schema::table('ciudadanos', function (Blueprint $table) {
            $table->dropColumn('puntos');
        });
    }
};