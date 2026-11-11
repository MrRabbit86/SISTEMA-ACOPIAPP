<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas_acopiadoras', function (Blueprint $table) {
            $table->string('nit', 20)->nullable()->change();
            $table->string('razon_social')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('empresas_acopiadoras', function (Blueprint $table) {
            $table->string('nit', 20)->nullable(false)->change();
            $table->string('razon_social')->nullable(false)->change();
        });
    }
};
