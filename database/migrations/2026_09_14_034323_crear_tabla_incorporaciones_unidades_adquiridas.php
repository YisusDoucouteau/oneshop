<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'incorporaciones_unidades_adquiridas',
            function (Blueprint $table) {

                $table->id();


                /*
                |--------------------------------------------------------------------------
                | Unidad previa a inventario
                |--------------------------------------------------------------------------
                */

                $table->foreignId(
                    'unidad_adquirida_id'
                )
                ->constrained(
                    'unidades_adquiridas'
                )
                ->restrictOnDelete();



                /*
                |--------------------------------------------------------------------------
                | Equipo generado en inventario
                |--------------------------------------------------------------------------
                */

                $table->foreignId(
                    'equipo_id'
                )
                ->constrained(
                    'equipos'
                )
                ->restrictOnDelete();



                /*
                |--------------------------------------------------------------------------
                | Usuario que realiza incorporación
                |--------------------------------------------------------------------------
                */

                $table->foreignId(
                    'usuario_id'
                )
                ->constrained(
                    'users'
                )
                ->restrictOnDelete();



                /*
                |--------------------------------------------------------------------------
                | Condición decidida por Daniel
                |--------------------------------------------------------------------------
                */

                $table->foreignId(
                    'condicion_fisica_id'
                )
                ->nullable()
                ->constrained(
                    'condiciones_fisicas'
                )
                ->restrictOnDelete();



                $table->dateTime(
                    'fecha_incorporacion'
                );


                $table->text(
                    'observacion'
                )
                ->nullable();


                $table->timestamps();



                $table->unique(
                    'unidad_adquirida_id',
                    'uq_incorporacion_unidad'
                );


                $table->index(
                    'equipo_id'
                );

            }
        );
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'incorporaciones_unidades_adquiridas'
        );
    }
};