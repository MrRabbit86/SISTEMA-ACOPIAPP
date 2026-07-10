<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ofertas', function (Blueprint $table) {
            $table->string('descripcion', 255)->nullable()->after('cantidad_estimada_kg');
            $table->enum('modalidad_entrega', ['recojo_domicilio', 'entrega_punto_verde'])
                ->default('entrega_punto_verde')
                ->after('descripcion');
            $table->string('foto_url', 255)->nullable()->after('modalidad_entrega');
        });
    }

    public function down(): void
    {
        Schema::table('ofertas', function (Blueprint $table) {
            $table->dropColumn(['descripcion', 'modalidad_entrega', 'foto_url']);
        });
    }
};