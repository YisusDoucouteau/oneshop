<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\CategoriaProducto;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\Producto;
use App\Models\SolicitudAprobacionPrecio;
use App\Models\User;
use App\Services\GestionAprobacionPrecioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

class GestionAprobacionPrecioServiceTest extends TestCase
{
    use RefreshDatabase;


    private function crearEquipo(): Equipo
    {
        $categoria = CategoriaProducto::create([
            'codigo' => 'LAPTOP-' . Str::upper(
                Str::random(6)
            ),
            'nombre' => 'Laptop',
            'descripcion' => null,
            'activo' => true,
        ]);


        $almacen = Almacen::create([
            'codigo' => 'ORU-' . Str::upper(
                Str::random(5)
            ),
            'nombre' => 'Almacén Oruro',
            'ciudad' => 'Oruro',
            'direccion' => null,
            'principal' => true,
            'activo' => true,
        ]);


        $estado = EstadoEquipo::create([
            'codigo' => 'DISP-' . Str::upper(
                Str::random(5)
            ),
            'nombre' => 'Disponible',
            'descripcion' => null,
            'es_final' => true,
            'orden' => 1,
            'activo' => true,
        ]);


        $producto = Producto::create([
            'categoria_producto_id' =>
                $categoria->id,

            'marca_id' =>
                null,

            'codigo' =>
                'PROD-' . Str::uuid(),

            'nombre' =>
                'Laptop prueba',

            'modelo' =>
                'TEST',

            'descripcion' =>
                null,

            'es_serializado' =>
                true,

            'activo' =>
                true,
        ]);


        return Equipo::create([

            'producto_id' =>
                $producto->id,

            'detalle_lote_id' =>
                null,

            'almacen_actual_id' =>
                $almacen->id,

            'estado_actual_id' =>
                $estado->id,

            'condicion_fisica_id' =>
                null,

            'codigo_interno' =>
                'EQ-' . Str::uuid(),

            'serial_fabricante' =>
                null,

            'fecha_registro' =>
                now(),

            'fecha_disponible' =>
                now(),

            'observacion' =>
                null,

            'activo' =>
                true,
        ]);
    }


    private function crearUsuario(): User
    {
        return User::create([

            'name' =>
                'Daniel Prueba',

            'email' =>
                'daniel' . uniqid() . '@oneshop.com',

            'password' =>
                bcrypt('password'),

        ]);
    }


    public function test_crea_solicitud_pendiente(): void
    {
        $equipo =
            $this->crearEquipo();


        $service =
            app(
                GestionAprobacionPrecioService::class
            );


        $solicitud =
            $service->crearSolicitud(
                $equipo->id,
                5000,
                4700,
                900,
                null,
                'Cliente recurrente'
            );


        $this->assertInstanceOf(
            SolicitudAprobacionPrecio::class,
            $solicitud
        );


        $this->assertSame(
            'PENDIENTE',
            $solicitud->estado
        );


        $this->assertDatabaseHas(
            'solicitud_aprobacion_precios',
            [
                'equipo_id' =>
                    $equipo->id,

                'precio_publicado' =>
                    5000,

                'precio_propuesto' =>
                    4700,

                'descuento_solicitado' =>
                    300,
            ]
        );
    }


    public function test_aprueba_solicitud(): void
    {
        $equipo =
            $this->crearEquipo();

        $usuario =
            $this->crearUsuario();


        $service =
            app(
                GestionAprobacionPrecioService::class
            );


        $solicitud =
            $service->crearSolicitud(
                $equipo->id,
                5000,
                4700,
                900
            );


        $resultado =
            $service->aprobar(
                $solicitud->id,
                $usuario->id,
                'Aprobado por margen correcto'
            );


        $this->assertSame(
            'APROBADA',
            $resultado->estado
        );


        $this->assertSame(
            $usuario->id,
            $resultado->usuario_aprobador_id
        );


        $this->assertNotNull(
            $resultado->fecha_aprobacion
        );
    }


    public function test_rechaza_solicitud(): void
    {
        $equipo =
            $this->crearEquipo();

        $usuario =
            $this->crearUsuario();


        $service =
            app(
                GestionAprobacionPrecioService::class
            );


        $solicitud =
            $service->crearSolicitud(
                $equipo->id,
                5000,
                4200,
                300
            );


        $resultado =
            $service->rechazar(
                $solicitud->id,
                $usuario->id,
                'Ganancia insuficiente'
            );


        $this->assertSame(
            'RECHAZADA',
            $resultado->estado
        );


        $this->assertSame(
            $usuario->id,
            $resultado->usuario_aprobador_id
        );
    }


    public function test_no_permite_aprobar_dos_veces(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );


        $equipo =
            $this->crearEquipo();

        $usuario =
            $this->crearUsuario();


        $service =
            app(
                GestionAprobacionPrecioService::class
            );


        $solicitud =
            $service->crearSolicitud(
                $equipo->id,
                5000,
                4700,
                900
            );


        $service->aprobar(
            $solicitud->id,
            $usuario->id
        );


        $service->aprobar(
            $solicitud->id,
            $usuario->id
        );
    }


    public function test_no_permite_precios_negativos(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );


        $service =
            app(
                GestionAprobacionPrecioService::class
            );


        $service->crearSolicitud(
            1,
            -500,
            4000,
            500
        );
    }


    public function test_lista_solicitudes_pendientes(): void
    {
        $equipo =
            $this->crearEquipo();


        $service =
            app(
                GestionAprobacionPrecioService::class
            );


        $service->crearSolicitud(
            $equipo->id,
            5000,
            4700,
            900
        );


        $pendientes =
            $service->pendientes();


        $this->assertCount(
            1,
            $pendientes
        );
    }
}