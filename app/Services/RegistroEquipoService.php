<?php

namespace App\Services;

use App\Exceptions\ReglaNegocioException;
use App\Models\Almacen;
use App\Models\CondicionFisica;
use App\Models\DetalleLote;
use App\Models\Equipo;
use App\Models\EspecificacionEquipo;
use App\Models\EstadoEquipo;
use App\Models\HistorialEstadoEquipo;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RegistroEquipoService
{
    public function registrar(
        int $usuarioId,
        array $datos
    ): Equipo {
        return DB::transaction(function () use ($usuarioId, $datos) {

            $usuario = $this->obtenerUsuarioAutorizado($usuarioId);

            $codigoInterno = strtoupper(
                trim((string) ($datos['codigo_interno'] ?? ''))
            );

            $serial = isset($datos['serial_fabricante'])
                ? trim((string) $datos['serial_fabricante'])
                : null;

            $serial = $serial === '' ? null : $serial;

            Validator::make(
                [
                    ...$datos,
                    'codigo_interno' => $codigoInterno,
                    'serial_fabricante' => $serial,
                ],
                [
                    'producto_id' => [
                        'required',
                        'integer',
                    ],

                    'almacen_actual_id' => [
                        'required',
                        'integer',
                    ],

                    'condicion_fisica_id' => [
                        'nullable',
                        'integer',
                    ],

                    'detalle_lote_id' => [
                        'nullable',
                        'integer',
                    ],

                    'codigo_interno' => [
                        'required',
                        'string',
                        'max:50',
                        Rule::unique('equipos', 'codigo_interno'),
                    ],

                    'serial_fabricante' => [
                        'nullable',
                        'string',
                        'max:150',
                    ],

                    'observacion' => [
                        'nullable',
                        'string',
                    ],

                    'procesador' => [
                        'nullable',
                        'string',
                        'max:150',
                    ],

                    'generacion_procesador' => [
                        'nullable',
                        'string',
                        'max:80',
                    ],

                    'ram_gb' => [
                        'nullable',
                        'integer',
                        'min:0',
                        'max:65535',
                    ],

                    'almacenamiento_gb' => [
                        'nullable',
                        'integer',
                        'min:0',
                    ],

                    'tipo_almacenamiento' => [
                        'nullable',
                        'string',
                        'max:50',
                    ],

                    'tarjeta_grafica' => [
                        'nullable',
                        'string',
                        'max:150',
                    ],

                    'pantalla_pulgadas' => [
                        'nullable',
                        'numeric',
                        'min:0',
                        'max:999.9',
                    ],

                    'resolucion' => [
                        'nullable',
                        'string',
                        'max:50',
                    ],

                    'sistema_operativo' => [
                        'nullable',
                        'string',
                        'max:100',
                    ],

                    'bateria_porcentaje' => [
                        'nullable',
                        'integer',
                        'min:0',
                        'max:100',
                    ],

                    'datos_adicionales' => [
                        'nullable',
                        'array',
                    ],
                ]
            )->validate();

            /*
            |--------------------------------------------------------------------------
            | Producto
            |--------------------------------------------------------------------------
            */

            $producto = Producto::query()
                ->where('activo', true)
                ->find($datos['producto_id']);

            if (!$producto) {
                throw new ReglaNegocioException(
                    'El producto no existe o se encuentra inactivo.'
                );
            }

            /*
             * Los equipos representan unidades físicas individualizadas.
             * Los productos no serializados se gestionan mediante existencias.
             */
            if (!$producto->es_serializado) {
                throw new ReglaNegocioException(
                    'El producto seleccionado no admite registro individual por equipo.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Almacén
            |--------------------------------------------------------------------------
            */

            $almacen = Almacen::query()
                ->where('activo', true)
                ->find($datos['almacen_actual_id']);

            if (!$almacen) {
                throw new ReglaNegocioException(
                    'El almacén no existe o se encuentra inactivo.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Condición física
            |--------------------------------------------------------------------------
            */

            $condicionId = $datos['condicion_fisica_id'] ?? null;

            if ($condicionId !== null) {
                $condicion = CondicionFisica::query()
                    ->where('activo', true)
                    ->find($condicionId);

                if (!$condicion) {
                    throw new ReglaNegocioException(
                        'La condición física no existe o se encuentra inactiva.'
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Procedencia por lote
            |--------------------------------------------------------------------------
            */

            $detalleLoteId = $datos['detalle_lote_id'] ?? null;

            if ($detalleLoteId !== null) {
                $detalleLote = DetalleLote::query()
                    ->find($detalleLoteId);

                if (!$detalleLote) {
                    throw new ReglaNegocioException(
                        'El detalle de lote seleccionado no existe.'
                    );
                }

                if ($detalleLote->producto_id !== $producto->id) {
                    throw new ReglaNegocioException(
                        'El producto del equipo no coincide con el producto registrado en el lote.'
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Estado inicial
            |--------------------------------------------------------------------------
            */

            $estadoRecibido = EstadoEquipo::query()
                ->where('codigo', 'RECIBIDO')
                ->where('activo', true)
                ->first();

            if (!$estadoRecibido) {
                throw new ReglaNegocioException(
                    'El estado inicial RECIBIDO no se encuentra disponible.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Equipo
            |--------------------------------------------------------------------------
            */

            $equipo = Equipo::create([
                'producto_id' => $producto->id,
                'detalle_lote_id' => $detalleLoteId,
                'almacen_actual_id' => $almacen->id,
                'estado_actual_id' => $estadoRecibido->id,
                'condicion_fisica_id' => $condicionId,
                'codigo_interno' => $codigoInterno,
                'serial_fabricante' => $serial,
                'fecha_registro' => now(),
                'fecha_disponible' => null,
                'observacion' => $datos['observacion'] ?? null,
                'activo' => true,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Especificaciones
            |--------------------------------------------------------------------------
            */

            EspecificacionEquipo::create([
                'equipo_id' => $equipo->id,
                'procesador' => $datos['procesador'] ?? null,
                'generacion_procesador' =>
                    $datos['generacion_procesador'] ?? null,
                'ram_gb' => $datos['ram_gb'] ?? null,
                'almacenamiento_gb' =>
                    $datos['almacenamiento_gb'] ?? null,
                'tipo_almacenamiento' =>
                    $datos['tipo_almacenamiento'] ?? null,
                'tarjeta_grafica' =>
                    $datos['tarjeta_grafica'] ?? null,
                'pantalla_pulgadas' =>
                    $datos['pantalla_pulgadas'] ?? null,
                'resolucion' => $datos['resolucion'] ?? null,
                'sistema_operativo' =>
                    $datos['sistema_operativo'] ?? null,
                'bateria_porcentaje' =>
                    $datos['bateria_porcentaje'] ?? null,
                'datos_adicionales' =>
                    $datos['datos_adicionales'] ?? null,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Primer evento de trazabilidad
            |--------------------------------------------------------------------------
            */

            HistorialEstadoEquipo::create([
                'equipo_id' => $equipo->id,
                'estado_origen_id' => null,
                'estado_destino_id' => $estadoRecibido->id,
                'usuario_id' => $usuario->id,
                'autorizado_por_id' => null,
                'fecha_cambio' => now(),
                'motivo' => 'Registro inicial del equipo.',
                'observacion' => null,
            ]);

            return $equipo->fresh([
                'producto.marca',
                'producto.categoria',
                'almacenActual',
                'estadoActual',
                'condicionFisica',
                'especificacion',
                'historialEstados.estadoDestino',
            ]);
        }, 3);
    }

    private function obtenerUsuarioAutorizado(
        int $usuarioId
    ): User {
        $usuario = User::query()
            ->where('activo', true)
            ->find($usuarioId);

        if (!$usuario) {
            throw new ReglaNegocioException(
                'El usuario no existe o se encuentra inactivo.'
            );
        }

        if (!$usuario->tienePermiso('inventario.registrar')) {
            throw new ReglaNegocioException(
                'El usuario no cuenta con permiso para registrar equipos.'
            );
        }

        return $usuario;
    }
}