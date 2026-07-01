<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ofertas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ciudadano_id')->constrained('ciudadanos')->restrictOnDelete();
            $table->foreignId('categoria_id')->constrained('categorias_material')->restrictOnDelete();
            $table->decimal('cantidad_estimada_kg', 10, 2);
            $table->decimal('latitud', 10, 8);
            $table->decimal('longitud', 11, 8);
            $table->enum('estado', ['pendiente', 'en_proceso', 'completada', 'cancelada'])->default('pendiente');
            $table->timestamp('fecha_publicacion')->useCurrent();
            $table->timestamps();

            $table->index(['categoria_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ofertas');
    }
};