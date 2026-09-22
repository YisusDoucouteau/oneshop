<?php

namespace Tests\Feature;

use App\Models\ParametroSistema;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservaWebTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_vendedor_puede_abrir_formulario_dinamico_de_reserva(): void
    {
        $vendedor =
            $this->usuarioConRol(
                'VENDEDOR'
            );

        $respuesta =
            $this
                ->actingAs($vendedor)
                ->get(
                    route(
                        'reservas.create'
                    )
                );

        $respuesta
            ->assertOk()
            ->assertSee(
                'Registrar nueva reserva'
            )
            ->assertSee(
                'Buscar cliente'
            )
            ->assertSee(
                'Equipos'
            )
            ->assertSee(
                'Vigencia'
            )
            ->assertSee(
                'Confirmar reserva'
            );
    }

    public function test_formulario_usa_plazo_maximo_configurado(): void
    {
        $vendedor =
            $this->usuarioConRol(
                'VENDEDOR'
            );

        ParametroSistema::query()
            ->where(
                'codigo',
                'RESERVA_DIAS_MAXIMOS_ESTANDAR'
            )
            ->update([
                'valor' => '5',
            ]);

        $respuesta =
            $this
                ->actingAs($vendedor)
                ->get(
                    route(
                        'reservas.create'
                    )
                );

        $respuesta
            ->assertOk()
            ->assertViewHas(
                'diasMaximosReserva',
                5
            )
            ->assertSee(
                'Plazo máximo estándar:'
            );
    }

    public function test_tecnico_no_puede_crear_reservas(): void
    {
        $tecnico =
            $this->usuarioConRol(
                'TECNICO'
            );

        $this
            ->actingAs($tecnico)
            ->get(
                route(
                    'reservas.create'
                )
            )
            ->assertForbidden();
    }

    public function test_usuario_no_autenticado_es_enviado_al_login(): void
    {
        $this
            ->get(
                route(
                    'reservas.index'
                )
            )
            ->assertRedirect(
                route('login')
            );
    }

    private function usuarioConRol(
        string $codigoRol
    ): User {
        $usuario =
            User::factory()->create([
                'activo' => true,
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
