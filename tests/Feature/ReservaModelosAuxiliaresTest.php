<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\NotificacionReserva;
use App\Models\ProrrogaReserva;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservaModelosAuxiliaresTest extends TestCase
{
    use RefreshDatabase;

    public function test_prorroga_usa_los_nombres_reales_de_columnas_de_la_base(): void
    {
        $usuario = User::factory()->create([
            'activo' => true,
        ]);

        $reserva =
            $this->crearReserva(
                $usuario
            );

        $anterior =
            now()->addDay();

        $nueva =
            now()->addDays(2);

        $prorroga =
            ProrrogaReserva::create([
                'reserva_id' =>
                    $reserva->id,

                'autorizado_por_id' =>
                    $usuario->id,

                'fecha_expiracion_anterior' =>
                    $anterior,

                'nueva_fecha_expiracion' =>
                    $nueva,

                'motivo' =>
                    'Prueba de integridad.',
            ]);

        $this->assertNotNull(
            $prorroga
                ->fecha_expiracion_anterior
        );

        $this->assertNotNull(
            $prorroga
                ->nueva_fecha_expiracion
        );

        $this->assertDatabaseHas(
            'prorrogas_reservas',
            [
                'id' =>
                    $prorroga->id,

                'reserva_id' =>
                    $reserva->id,
            ]
        );
    }

    public function test_notificacion_usa_fecha_notificacion_de_la_base(): void
    {
        $usuario = User::factory()->create([
            'activo' => true,
        ]);

        $reserva =
            $this->crearReserva(
                $usuario
            );

        $notificacion =
            NotificacionReserva::create([
                'reserva_id' =>
                    $reserva->id,

                'usuario_id' =>
                    $usuario->id,

                'tipo' =>
                    'RESERVA_VENCIMIENTO',

                'canal' =>
                    'SISTEMA',

                'destino' =>
                    null,

                'fecha_notificacion' =>
                    now(),

                'resultado' =>
                    'REGISTRADA',

                'descripcion' =>
                    'Prueba de integridad.',
            ]);

        $this->assertNotNull(
            $notificacion
                ->fecha_notificacion
        );

        $this->assertDatabaseHas(
            'notificaciones_reservas',
            [
                'id' =>
                    $notificacion->id,

                'reserva_id' =>
                    $reserva->id,

                'tipo' =>
                    'RESERVA_VENCIMIENTO',
            ]
        );
    }

    private function crearReserva(
        User $usuario
    ): Reserva {
        $cliente =
            Cliente::create([
                'nombre_completo' =>
                    'Cliente auxiliar',

                'telefono' =>
                    '70000123',

                'activo' =>
                    true,
            ]);

        return Reserva::create([
            'numero' =>
                'RES-AUX-' . $usuario->id,

            'cliente_id' =>
                $cliente->id,

            'registrado_por_id' =>
                $usuario->id,

            'estado' =>
                'ACTIVA',

            'fecha_reserva' =>
                now(),

            'fecha_expiracion' =>
                now()->addDay(),

            'fecha_cierre' =>
                null,
        ]);
    }
}
