<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transacciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('oferta_id')->unique()->constrained('ofertas')->cascadeOnDelete();
            $table->foreignId('empresa_id')->constrained('users')->restrictOnDelete();
            $table->decimal('peso_real_kg', 10, 2);
            $table->decimal('precio_acordado_kg', 10, 2);
            $table->decimal('monto_total', 10, 2);
            $table->enum('estado', ['registrada', 'completada'])->default('completada');
            $table->timestamp('fecha_transaccion')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transacciones');
    }
};