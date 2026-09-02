<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificacions', function (Blueprint $table) {

            $table->id();


            /*
            |--------------------------------------------------------------------------
            | Usuario que recibe la notificación
            |--------------------------------------------------------------------------
            */

            $table->foreignId('usuario_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();



            /*
            |--------------------------------------------------------------------------
            | Clasificación de la notificación
            |--------------------------------------------------------------------------
            |
            | Ejemplos:
            | SOLICITUD_APROBACION_PRECIO
            | APROBACION_PRECIO
            | RECHAZO_PRECIO
            | RESERVA_VENCIMIENTO
            |
            */

            $table->string(
                'tipo',
                50
            );



            /*
            |--------------------------------------------------------------------------
            | Contenido visible
            |--------------------------------------------------------------------------
            */

            $table->string(
                'titulo',
                150
            );


            $table->text(
                'mensaje'
            );



            /*
            |--------------------------------------------------------------------------
            | Referencia al proceso origen
            |--------------------------------------------------------------------------
            |
            | Permite relacionar con:
            | SolicitudAprobacionPrecio
            | Reserva
            | Venta
            |
            */

            $table->string(
                'referencia_tipo',
                80
            )
            ->nullable();


            $table->unsignedBigInteger(
                'referencia_id'
            )
            ->nullable();



            /*
            |--------------------------------------------------------------------------
            | Canal de envío
            |--------------------------------------------------------------------------
            |
            | Actualmente:
            | INTERNO
            |
            | Futuro:
            | WHATSAPP
            | EMAIL
            |
            */

            $table->string(
                'canal',
                30
            )
            ->default('INTERNO');



            /*
            |--------------------------------------------------------------------------
            | Estado lectura
            |--------------------------------------------------------------------------
            */

            $table->boolean(
                'leido'
            )
            ->default(false);


            $table->dateTime(
                'fecha_lectura'
            )
            ->nullable();



            /*
            |--------------------------------------------------------------------------
            | Resultado del envío
            |--------------------------------------------------------------------------
            */

            $table->string(
                'resultado',
                50
            )
            ->nullable();



            $table->timestamps();



            $table->index([
                'usuario_id',
                'leido'
            ]);


            $table->index([
                'referencia_tipo',
                'referencia_id'
            ]);

        });
    }


    public function down(): void
    {
        Schema::dropIfExists('notificacions');
    }
};