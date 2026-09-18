<?php

namespace App\Services;

use App\Exceptions\ReglaNegocioException;
use App\Models\DetalleLote;
use App\Models\Lote;
use App\Models\Moneda;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LoteService
{
    public function __construct(
        private TipoCambioService $tipoCambioService
    ) {
    }

    public function crearLote(int $usuarioId, array $datos): Lote
    {
        return DB::transaction(function () use ($usuarioId, $datos) {
            $this->obtenerUsuarioAutorizado($usuarioId);

            $datos['codigo'] = strtoupper(trim((string) ($datos['codigo'] ?? '')));

            $validator = Validator::make($datos, [
                'codigo' => ['required', 'string', 'max:50', 'unique:lotes,codigo'],
                'proveedor_id' => ['nullable', 'integer', 'exists:proveedores,id'],
                'referencia_compra' => ['nullable', 'string', 'max:100'],
                'fecha_compra' => ['nullable', 'date'],
                'origen' => ['nullable', 'string', 'max:150'],
                'observacion' => ['nullable', 'string'],
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $validados = $validator->validated();

            if (isset($validados['proveedor_id'])) {
                $proveedor = Proveedor::query()
                    ->where('activo', true)
                    ->find($validados['proveedor_id']);

                if (!$proveedor) {
                    throw new ReglaNegocioException(
                        'El proveedor seleccionado no existe o se encuentra inactivo.'
                    );
                }
            }

            $lote = Lote::create([
                'proveedor_id' => $validados['proveedor_id'] ?? null,
                'codigo' => $validados['codigo'],
                'referencia_compra' => $validados['referencia_compra'] ?? null,
                'fecha_compra' => $validados['fecha_compra'] ?? null,
                'origen' => $validados['origen'] ?? null,
                'estado' => 'ABIERTO',
                'observacion' => $validados['observacion'] ?? null,
            ]);

            return $lote->fresh(['proveedor', 'detalles']);
        }, 3);
    }

    public function agregarDetalle(
    int $usuarioId,
    int $loteId,
    array $datos
): DetalleLote {
    return DB::transaction(function () use (
        $usuarioId,
        $loteId,
        $datos
    ) {
        $usuario = $this->obtenerUsuarioAutorizado(
            $usuarioId
        );

        /*
        |--------------------------------------------------------------------------
        | Lote
        |--------------------------------------------------------------------------
        */

        $lote = Lote::query()
            ->lockForUpdate()
            ->find($loteId);

        if (!$lote) {
            throw new ReglaNegocioException(
                'El lote solicitado no existe.'
            );
        }

        if ($lote->estado !== 'ABIERTO') {
            throw new ReglaNegocioException(
                'Solo se pueden agregar productos a un lote abierto.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Validación
        |--------------------------------------------------------------------------
        */

        $validator = Validator::make(
            $datos,
            [
                'producto_id' => [
                    'required',
                    'integer',
                ],

                'moneda_id' => [
                    'nullable',
                    'integer',
                    'exists:monedas,id',
                ],

                'cantidad_esperada' => [
                    'required',
                    'integer',
                    'min:1',
                ],

                'costo_unitario_origen' => [
                    'nullable',
                    'numeric',
                    'min:0',
                ],

                'tipo_cambio_aplicado' => [
                    'nullable',
                    'numeric',
                    'gt:0',
                ],

                'observacion' => [
                    'nullable',
                    'string',
                ],

                /*
                |--------------------------------------------------------------------------
                | Características esperadas
                |--------------------------------------------------------------------------
                |
                | Información reportada en la compra.
                | Todavía NO representa la verificación física en Oruro.
                |
                */

                'especificacion_esperada' => [
                    'nullable',
                    'array',
                ],

                'especificacion_esperada.procesador' => [
                    'nullable',
                    'string',
                    'max:150',
                ],

                'especificacion_esperada.generacion_procesador' => [
                    'nullable',
                    'string',
                    'max:80',
                ],

                'especificacion_esperada.ram_gb' => [
                    'nullable',
                    'integer',
                    'min:0',
                    'max:65535',
                ],

                'especificacion_esperada.almacenamiento_gb' => [
                    'nullable',
                    'integer',
                    'min:0',
                ],

                'especificacion_esperada.tipo_almacenamiento' => [
                    'nullable',
                    'string',
                    'max:50',
                ],

                'especificacion_esperada.tarjeta_grafica' => [
                    'nullable',
                    'string',
                    'max:150',
                ],

                'especificacion_esperada.pantalla_pulgadas' => [
                    'nullable',
                    'numeric',
                    'min:0',
                    'max:999.9',
                ],

                'especificacion_esperada.resolucion' => [
                    'nullable',
                    'string',
                    'max:50',
                ],

                'especificacion_esperada.sistema_operativo' => [
                    'nullable',
                    'string',
                    'max:100',
                ],

                'especificacion_esperada.datos_adicionales' => [
                    'nullable',
                    'array',
                ],

                /*
                |--------------------------------------------------------------------------
                | Componentes esperados
                |--------------------------------------------------------------------------
                */

                'componentes_esperados' => [
                    'nullable',
                    'array',
                ],

                'componentes_esperados.*.nombre' => [
                    'nullable',
                    'string',
                    'max:120',
                ],

                'componentes_esperados.*.cantidad_por_unidad' => [
                    'nullable',
                    'integer',
                    'min:1',
                ],

                'componentes_esperados.*.incluido_en_compra' => [
                    'nullable',
                    'boolean',
                ],

                'componentes_esperados.*.observacion' => [
                    'nullable',
                    'string',
                ],
            ]
        );

        if ($validator->fails()) {
            throw new ValidationException(
                $validator
            );
        }

        $validados =
            $validator->validated();

        /*
        |--------------------------------------------------------------------------
        | Producto
        |--------------------------------------------------------------------------
        */

        $producto = Producto::query()
            ->where('activo', true)
            ->find(
                $validados['producto_id']
            );

        if (!$producto) {
            throw new ReglaNegocioException(
                'El producto no existe o se encuentra inactivo.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Precio y moneda
        |--------------------------------------------------------------------------
        */

        $precioOrigen = array_key_exists(
            'costo_unitario_origen',
            $validados
        )
            ? $validados['costo_unitario_origen']
            : null;

        $moneda = null;

        if (
            isset(
                $validados['moneda_id']
            )
        ) {
            $moneda = Moneda::query()
                ->where('activo', true)
                ->find(
                    $validados['moneda_id']
                );

            if (!$moneda) {
                throw new ReglaNegocioException(
                    'La moneda seleccionada no se encuentra activa.'
                );
            }
        }

        if (
            $precioOrigen !== null
            && !$moneda
        ) {
            throw ValidationException::withMessages([
                'moneda_id' =>
                    'Debe seleccionar la moneda del precio unitario de compra.',
            ]);
        }

        if (
            $precioOrigen === null
            && isset(
                $validados[
                    'tipo_cambio_aplicado'
                ]
            )
        ) {
            throw ValidationException::withMessages([
                'tipo_cambio_aplicado' =>
                    'No debe registrar un tipo de cambio si no existe un precio de compra.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Conversión económica
        |--------------------------------------------------------------------------
        */

        $tipoCambioCompraId = null;
        $costoUnitarioBob = null;

        if (
            $precioOrigen !== null
            && $moneda
        ) {
            switch ($moneda->codigo) {

                case 'BOB':

                    $costoUnitarioBob = round(
                        (float) $precioOrigen,
                        2
                    );

                    break;


                case 'USD':

                    if (
                        !isset(
                            $validados[
                                'tipo_cambio_aplicado'
                            ]
                        )
                    ) {
                        throw ValidationException::withMessages([
                            'tipo_cambio_aplicado' =>
                                'Debe indicar el tipo de cambio realmente aplicado en la compra en USD.',
                        ]);
                    }

                    $tipoCambio =
                        $this->tipoCambioService
                            ->registrarAplicado(
                                $usuario->id,
                                'USD',
                                (float) $validados[
                                    'tipo_cambio_aplicado'
                                ],
                                "Lote {$lote->codigo} - {$producto->nombre} - compra USD"
                            );

                    $tipoCambioCompraId =
                        $tipoCambio->id;

                    $costoUnitarioBob =
                        $this->tipoCambioService
                            ->convertirABob(
                                (float) $precioOrigen,
                                'USD',
                                $tipoCambio
                            );

                    break;


                case 'USDT':

                    if (
                        !isset(
                            $validados[
                                'tipo_cambio_aplicado'
                            ]
                        )
                    ) {
                        throw ValidationException::withMessages([
                            'tipo_cambio_aplicado' =>
                                'Debe indicar el tipo de cambio realmente aplicado en la compra con USDT.',
                        ]);
                    }

                    $tipoCambio =
                        $this->tipoCambioService
                            ->registrarAplicado(
                                $usuario->id,
                                'USDT',
                                (float) $validados[
                                    'tipo_cambio_aplicado'
                                ],
                                "Lote {$lote->codigo} - {$producto->nombre} - compra USDT"
                            );

                    $tipoCambioCompraId =
                        $tipoCambio->id;

                    $costoUnitarioBob =
                        $this->tipoCambioService
                            ->convertirABob(
                                (float) $precioOrigen,
                                'USDT',
                                $tipoCambio
                            );

                    break;


                default:

                    throw new ReglaNegocioException(
                        'Actualmente las compras están habilitadas para BOB, USD y USDT.'
                    );
            }
        }
        
        /*
        |--------------------------------------------------------------------------
        | Línea de compra
        |--------------------------------------------------------------------------
        */

        $detalle = DetalleLote::create([
            'lote_id' =>
                $lote->id,

            'producto_id' =>
                $producto->id,

            'moneda_id' =>
                $moneda?->id,

            'tipo_cambio_compra_id' =>
                $tipoCambioCompraId,

            'cantidad_esperada' =>
                $validados[
                    'cantidad_esperada'
                ],

            'cantidad_recibida' =>
                0,

            'costo_unitario_origen' =>
                $precioOrigen,

            'costo_unitario_bob' =>
                $costoUnitarioBob,

            'observacion' =>
                $validados['observacion']
                ?? null,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Especificaciones esperadas
        |--------------------------------------------------------------------------
        |
        | Se crea la ficha solamente si Yuyo informó
        | por lo menos una característica.
        |
        */

        $especificacionEsperada =
            $validados[
                'especificacion_esperada'
            ]
            ?? [];

        $tieneEspecificacion = collect(
            $especificacionEsperada
        )->contains(
            fn ($valor) =>
                $valor !== null
                && $valor !== ''
                && $valor !== []
        );

        if ($tieneEspecificacion) {
            $detalle
                ->especificacionEsperada()
                ->create([
                    'procesador' =>
                        $especificacionEsperada[
                            'procesador'
                        ]
                        ?? null,

                    'generacion_procesador' =>
                        $especificacionEsperada[
                            'generacion_procesador'
                        ]
                        ?? null,

                    'ram_gb' =>
                        $especificacionEsperada[
                            'ram_gb'
                        ]
                        ?? null,

                    'almacenamiento_gb' =>
                        $especificacionEsperada[
                            'almacenamiento_gb'
                        ]
                        ?? null,

                    'tipo_almacenamiento' =>
                        $especificacionEsperada[
                            'tipo_almacenamiento'
                        ]
                        ?? null,

                    'tarjeta_grafica' =>
                        $especificacionEsperada[
                            'tarjeta_grafica'
                        ]
                        ?? null,

                    'pantalla_pulgadas' =>
                        $especificacionEsperada[
                            'pantalla_pulgadas'
                        ]
                        ?? null,

                    'resolucion' =>
                        $especificacionEsperada[
                            'resolucion'
                        ]
                        ?? null,

                    'sistema_operativo' =>
                        $especificacionEsperada[
                            'sistema_operativo'
                        ]
                        ?? null,

                    'datos_adicionales' =>
                        $especificacionEsperada[
                            'datos_adicionales'
                        ]
                        ?? null,
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Componentes esperados
        |--------------------------------------------------------------------------
        |
        | Se registran por línea de compra.
        |
        | Ejemplo:
        | cantidad_esperada = 3 laptops
        | Cargador cantidad_por_unidad = 1
        |
        | Total esperado = 3 cargadores.
        |
        */

        $componentes =
            $validados[
                'componentes_esperados'
            ]
            ?? [];

        foreach (
            $componentes
            as $indice => $componente
        ) {
            $nombre = trim(
                (string) (
                    $componente['nombre']
                    ?? ''
                )
            );

            /*
             * Una fila completamente vacía simplemente
             * no genera un componente.
             */
            if ($nombre === '') {
                continue;
            }

            $detalle
                ->componentesEsperados()
                ->create([
                    'nombre' =>
                        $nombre,

                    'cantidad_por_unidad' =>
                        $componente[
                            'cantidad_por_unidad'
                        ]
                        ?? 1,

                    'incluido_en_compra' =>
                        array_key_exists(
                            'incluido_en_compra',
                            $componente
                        )
                            ? (bool) $componente[
                                'incluido_en_compra'
                            ]
                            : true,

                    'observacion' =>
                        $componente[
                            'observacion'
                        ]
                        ?? null,
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Resultado completo
        |--------------------------------------------------------------------------
        */

        return $detalle->fresh([
            'lote.proveedor',
            'producto.marca',
            'producto.categoria',
            'moneda',
            'tipoCambioCompra',
            'especificacionEsperada',
            'componentesEsperados',
        ]);
    }, 3);
}

    private function obtenerUsuarioAutorizado(int $usuarioId): User
    {
        $usuario = User::query()
            ->where('activo', true)
            ->find($usuarioId);

        if (!$usuario) {
            throw new ReglaNegocioException(
                'El usuario no existe o se encuentra inactivo.'
            );
        }

        if (!$usuario->tienePermiso('importacion.gestionar')) {
            throw new ReglaNegocioException(
                'El usuario no cuenta con permiso para gestionar importaciones.'
            );
        }

        return $usuario;
    }
}
