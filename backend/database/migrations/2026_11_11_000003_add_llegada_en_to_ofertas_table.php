<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ofertas', function (Blueprint $table) {
            $table->timestamp('llegada_en')->nullable()->after('reservada_hasta');
        });

        DB::table('users')
            ->where('email', 'empresa@reciclaje.bo')
            ->update(['telefono' => '75951188']);

        DB::table('users')
            ->where('email', 'ciudadano@reciclaje.bo')
            ->update(['telefono' => '60709411']);
    }

    public function down(): void
    {
        Schema::table('ofertas', function (Blueprint $table) {
            $table->dropColumn('llegada_en');
        });
    }
};
