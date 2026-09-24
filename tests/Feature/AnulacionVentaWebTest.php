<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\User;
use App\Models\Venta;
use App\Services\AnulacionVentaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AnulacionVentaWebTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_administrador_operativo_puede_anular_venta_desde_web(): void
    {
        $usuario =
            $this->usuarioConRol(
                'ADMIN_OPERATIVO'
            );

        $venta =
            $this->crearVenta(
                $usuario
            );

        $servicio =
            Mockery::mock(
                AnulacionVentaService::class
            );

        $servicio
            ->shouldReceive('anular')
            ->once()
            ->with(
                $venta->id,
                $usuario->id,
                'Venta registrada por error.'
            )
            ->andReturn(
                tap(
                    $venta,
                    function (Venta $venta) use ($usuario) {
                        $venta->estado =
                            'ANULADA';

                        $venta->anulado_por_id =
                            $usuario->id;

                        $venta->fecha_anulacion =
                            now();

                        $venta->motivo_anulacion =
                            'Venta registrada por error.';
                    }
                )
            );

        $this->app->instance(
            AnulacionVentaService::class,
            $servicio
        );

        $this
            ->actingAs($usuario)
            ->post(
                route(
                    'ventas.anular',
                    $venta
                ),
                [
                    'motivo_anulacion' =>
                        'Venta registrada por error.',
                ]
            )
            ->assertRedirect(
                route(
                    'ventas.show',
                    $venta
                )
            )
            ->assertSessionHas(
                'success'
            );
    }

    public function test_vendedor_no_puede_anular_venta(): void
    {
        $vendedor =
            $this->usuarioConRol(
                'VENDEDOR'
            );

        $venta =
            $this->crearVenta(
                $vendedor
            );

        $servicio =
            Mockery::mock(
                AnulacionVentaService::class
            );

        $servicio
            ->shouldNotReceive(
                'anular'
            );

        $this->app->instance(
            AnulacionVentaService::class,
            $servicio
        );

        $this
            ->actingAs($vendedor)
            ->post(
                route(
                    'ventas.anular',
                    $venta
                ),
                [
                    'motivo_anulacion' =>
                        'Intento no autorizado.',
                ]
            )
            ->assertForbidden();
    }

    public function test_motivo_de_anulacion_es_obligatorio(): void
    {
        $usuario =
            $this->usuarioConRol(
                'ADMIN_OPERATIVO'
            );

        $venta =
            $this->crearVenta(
                $usuario
            );

        $servicio =
            Mockery::mock(
                AnulacionVentaService::class
            );

        $servicio
            ->shouldNotReceive(
                'anular'
            );

        $this->app->instance(
            AnulacionVentaService::class,
            $servicio
        );

        $this
            ->actingAs($usuario)
            ->from(
                route(
                    'ventas.show',
                    $venta
                )
            )
            ->post(
                route(
                    'ventas.anular',
                    $venta
                ),
                [
                    'motivo_anulacion' =>
                        '',
                ]
            )
            ->assertRedirect(
                route(
                    'ventas.show',
                    $venta
                )
            )
            ->assertSessionHasErrors(
                'motivo_anulacion'
            );
    }

    private function crearVenta(
        User $vendedor
    ): Venta {
        return Venta::create([
            'numero' =>
                'VEN-WEB-ANU-' .
                uniqid(),

            'cliente_id' =>
                null,

            'cliente_nombre_snapshot' =>
                'Cliente web',

            'cliente_telefono_snapshot' =>
                '70000000',

            'vendedor_id' =>
                $vendedor->id,

            'reserva_id' =>
                null,

            'fecha_venta' =>
                now(),

            'subtotal' =>
                4500,

            'descuento_total' =>
                0,

            'total' =>
                4500,

            'estado' =>
                'REGISTRADA',

            'observacion' =>
                null,
        ]);
    }

    private function usuarioConRol(
        string $codigoRol
    ): User {
        $usuario =
            User::factory()->create([
                'activo' =>
                    true,
            ]);

        $rol =
            Rol::query()
                ->where(
                    'codigo',
                    $codigoRol
                )
                ->firstOrFail();

        $usuario
            ->roles()
            ->attach(
                $rol->id
            );

        return $usuario;
    }
}
