<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SeguridadSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Roles
        |--------------------------------------------------------------------------
        */

        $roles = [

            [
                'codigo' => 'ADMINISTRADOR',
                'nombre' => 'Administrador',
                'descripcion' => 'Acceso completo al sistema.',
                'activo' => true,
            ],

            [
                'codigo' => 'ADMIN_OPERATIVO',
                'nombre' => 'Administrador operativo',
                'descripcion' => 'Gestión operativa de inventario, precios y trazabilidad.',
                'activo' => true,
            ],

            [
                'codigo' => 'VENDEDOR',
                'nombre' => 'Vendedor',
                'descripcion' => 'Gestión de clientes, reservas, ventas y pagos.',
                'activo' => true,
            ],

            [
                'codigo' => 'TECNICO',
                'nombre' => 'Técnico',
                'descripcion' => 'Gestión de revisiones, diagnósticos y reparaciones.',
                'activo' => true,
            ],

        ];


        foreach ($roles as $rol) {

            DB::table('roles')->updateOrInsert(

                [
                    'codigo' => $rol['codigo']
                ],

                [
                    'nombre' => $rol['nombre'],
                    'descripcion' => $rol['descripcion'],
                    'activo' => $rol['activo'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]

            );

        }



        /*
        |--------------------------------------------------------------------------
        | Permisos
        |--------------------------------------------------------------------------
        */

        $permisos = [

            /*
            |--------------------------------------------------------------------------
            | Usuarios
            |--------------------------------------------------------------------------
            */

            [
                'codigo' => 'usuarios.ver',
                'nombre' => 'Ver usuarios'
            ],

            [
                'codigo' => 'usuarios.gestionar',
                'nombre' => 'Gestionar usuarios'
            ],


            /*
            |--------------------------------------------------------------------------
            | Clientes
            |--------------------------------------------------------------------------
            */

            [
                'codigo' => 'clientes.ver',
                'nombre' => 'Ver clientes'
            ],

            [
                'codigo' => 'clientes.gestionar',
                'nombre' => 'Gestionar clientes'
            ],


            /*
            |--------------------------------------------------------------------------
            | Inventario
            |--------------------------------------------------------------------------
            */

            [
                'codigo' => 'inventario.ver',
                'nombre' => 'Ver inventario'
            ],

            [
                'codigo' => 'inventario.registrar',
                'nombre' => 'Registrar inventario'
            ],

            [
                'codigo' => 'inventario.modificar',
                'nombre' => 'Modificar inventario'
            ],


            /*
            |--------------------------------------------------------------------------
            | Movimientos de inventario
            |--------------------------------------------------------------------------
            */

            [
                'codigo' => 'movimientos.ver',
                'nombre' => 'Ver movimientos de inventario'
            ],


            /*
            |--------------------------------------------------------------------------
            | Importaciones
            |--------------------------------------------------------------------------
            */

            [
                'codigo' => 'importacion.ver',
                'nombre' => 'Ver importaciones'
            ],

            [
                'codigo' => 'importacion.gestionar',
                'nombre' => 'Gestionar importaciones'
            ],


            /*
            |--------------------------------------------------------------------------
            | Transferencias
            |--------------------------------------------------------------------------
            */

            [
                'codigo' => 'transferencias.gestionar',
                'nombre' => 'Gestionar transferencias'
            ],


            /*
            |--------------------------------------------------------------------------
            | Técnico
            |--------------------------------------------------------------------------
            */

            [
                'codigo' => 'tecnico.ver',
                'nombre' => 'Ver información técnica'
            ],

            [
                'codigo' => 'tecnico.registrar_revision',
                'nombre' => 'Registrar revisiones técnicas'
            ],

            [
                'codigo' => 'tecnico.registrar_reparacion',
                'nombre' => 'Registrar reparaciones'
            ],


            /*
            |--------------------------------------------------------------------------
            | Precios
            |--------------------------------------------------------------------------
            */

            [
                'codigo' => 'precios.ver',
                'nombre' => 'Ver precios'
            ],

            [
                'codigo' => 'precios.modificar',
                'nombre' => 'Modificar precios'
            ],

            [
                'codigo' => 'precios.autorizar_descuento',
                'nombre' => 'Autorizar descuentos'
            ],


            /*
            |--------------------------------------------------------------------------
            | Reservas
            |--------------------------------------------------------------------------
            */

            [
                'codigo' => 'reservas.ver',
                'nombre' => 'Ver reservas'
            ],

            [
                'codigo' => 'reservas.gestionar',
                'nombre' => 'Gestionar reservas'
            ],


            /*
            |--------------------------------------------------------------------------
            | Ventas
            |--------------------------------------------------------------------------
            */

            [
                'codigo' => 'ventas.ver',
                'nombre' => 'Ver ventas'
            ],

            [
                'codigo' => 'ventas.crear',
                'nombre' => 'Registrar ventas'
            ],

            [
                'codigo' => 'ventas.anular',
                'nombre' => 'Anular ventas'
            ],


            /*
            |--------------------------------------------------------------------------
            | Pagos
            |--------------------------------------------------------------------------
            */

            [
                'codigo' => 'pagos.registrar',
                'nombre' => 'Registrar pagos'
            ],

            [
                'codigo' => 'pagos.verificar',
                'nombre' => 'Verificar pagos'
            ],


            /*
            |--------------------------------------------------------------------------
            | Garantías / postventa
            |--------------------------------------------------------------------------
            */

            [
                'codigo' => 'garantias.ver',
                'nombre' => 'Ver garantías'
            ],

            [
                'codigo' => 'garantias.registrar',
                'nombre' => 'Registrar casos de garantía'
            ],

            [
                'codigo' => 'garantias.gestionar',
                'nombre' => 'Gestionar casos de garantía'
            ],

            [
                'codigo' => 'garantias.autorizar_cambio',
                'nombre' => 'Autorizar cambio de equipo'
            ],

            [
                'codigo' => 'garantias.ajustes.registrar',
                'nombre' => 'Registrar ajustes económicos de garantía'
            ],

            [
                'codigo' => 'garantias.ajustes.verificar',
                'nombre' => 'Verificar ajustes económicos de garantía'
            ],


            /*
            |--------------------------------------------------------------------------
            | Finanzas
            |--------------------------------------------------------------------------
            */

            [
                'codigo' => 'finanzas.ver',
                'nombre' => 'Ver información financiera'
            ],

            [
                'codigo' => 'finanzas.gestionar',
                'nombre' => 'Gestionar información financiera'
            ],


            /*
            |--------------------------------------------------------------------------
            | Reportes
            |--------------------------------------------------------------------------
            */

            [
                'codigo' => 'reportes.ver',
                'nombre' => 'Ver reportes'
            ],


            /*
            |--------------------------------------------------------------------------
            | Auditoría
            |--------------------------------------------------------------------------
            */

            [
                'codigo' => 'auditoria.ver',
                'nombre' => 'Ver auditoría'
            ],

        ];


        foreach ($permisos as $permiso) {

            DB::table('permisos')->updateOrInsert(

                [
                    'codigo' => $permiso['codigo']
                ],

                [
                    'nombre' => $permiso['nombre'],
                    'descripcion' => null,
                    'activo' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]

            );

        }



        /*
        |--------------------------------------------------------------------------
        | Asignación de permisos
        |--------------------------------------------------------------------------
        */

        $todosLosPermisos = DB::table('permisos')
            ->pluck('id', 'codigo');


        $rolesIds = DB::table('roles')
            ->pluck('id', 'codigo');



        /*
        |--------------------------------------------------------------------------
        | Administrador
        |--------------------------------------------------------------------------
        |
        | El administrador recibe todos los permisos existentes, incluidos los
        | nuevos permisos económicos de garantía.
        |
        */

        foreach ($todosLosPermisos as $permisoId) {

            DB::table('permiso_rol')->insertOrIgnore([

                'permiso_id' => $permisoId,

                'rol_id' => $rolesIds['ADMINISTRADOR'],

            ]);

        }



        /*
        |--------------------------------------------------------------------------
        | Administrador operativo
        |--------------------------------------------------------------------------
        */

        $permisosOperativos = [

            'usuarios.ver',

            'clientes.ver',

            'inventario.ver',
            'inventario.registrar',
            'inventario.modificar',

            'movimientos.ver',

            'transferencias.gestionar',

            'importacion.ver',
            'importacion.gestionar',

            'tecnico.ver',
            'tecnico.registrar_revision',
            'tecnico.registrar_reparacion',

            'precios.ver',
            'precios.modificar',

            'reservas.ver',

            'ventas.ver',
            'ventas.anular',

            'garantias.ver',
            'garantias.gestionar',

            'reportes.ver',

        ];



        /*
        |--------------------------------------------------------------------------
        | Vendedor
        |--------------------------------------------------------------------------
        */

        $permisosVendedor = [

            'clientes.ver',
            'clientes.gestionar',

            'inventario.ver',

            'precios.ver',

            'reservas.ver',
            'reservas.gestionar',

            'ventas.ver',
            'ventas.crear',

            'pagos.registrar',

            'garantias.ver',
            'garantias.registrar',

        ];



        /*
        |--------------------------------------------------------------------------
        | Técnico
        |--------------------------------------------------------------------------
        */

        $permisosTecnico = [

            'inventario.ver',

            'tecnico.ver',

            'tecnico.registrar_revision',

            'tecnico.registrar_reparacion',

            'garantias.ver',
            'garantias.gestionar',

        ];



        /*
        |--------------------------------------------------------------------------
        | Aplicar permisos por rol
        |--------------------------------------------------------------------------
        */

        $this->asignarPermisos(
            $rolesIds['ADMIN_OPERATIVO'],
            $permisosOperativos,
            $todosLosPermisos
        );


        $this->asignarPermisos(
            $rolesIds['VENDEDOR'],
            $permisosVendedor,
            $todosLosPermisos
        );


        $this->asignarPermisos(
            $rolesIds['TECNICO'],
            $permisosTecnico,
            $todosLosPermisos
        );

    }



    private function asignarPermisos(
        int $rolId,
        array $codigos,
        $permisos
    ): void {

        foreach ($codigos as $codigo) {

            DB::table('permiso_rol')
                ->insertOrIgnore([

                    'permiso_id' => $permisos[$codigo],

                    'rol_id' => $rolId,

                ]);

        }

    }
}
