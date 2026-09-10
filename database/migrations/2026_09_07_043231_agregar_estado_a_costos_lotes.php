<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
      Schema::table('costos_lotes', function (Blueprint $table) {

    $table->string('estado')
        ->default('ACTIVO')
        ->after('observacion');

});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
      Schema::table('costos_lotes', function (Blueprint $table) {

    $table->dropColumn('estado');

        });
    }
};
