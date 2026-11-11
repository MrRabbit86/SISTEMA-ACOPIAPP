<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ofertas', function (Blueprint $table) {
            $table->foreignId('empresa_reservada_id')->nullable()->after('estado')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('reservada_hasta')->nullable()->after('empresa_reservada_id');
            $table->string('codigo_confirmacion', 4)->nullable()->after('reservada_hasta');
            $table->string('motivo_cierre', 255)->nullable()->after('codigo_confirmacion');
        });
    }

    public function down(): void
    {
        Schema::table('ofertas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('empresa_reservada_id');
            $table->dropColumn(['reservada_hasta', 'codigo_confirmacion', 'motivo_cierre']);
        });
    }
};
