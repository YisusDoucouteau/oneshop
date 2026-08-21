<?php

namespace App\Services;

use App\Exceptions\ReglaNegocioException;
use App\Models\DetalleLote;
use App\Models\Lote;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class LoteService
{
    public function crearLote(
        int $usuarioId,
        array $datos
    ): Lote {
        return DB::transaction(function () use (
            $usuarioId,
            $datos
        ) {
            $this->obtenerUsuarioAutorizado(
                $usuarioId
            );

            $codigo = strtoupper(
                trim((string) ($datos['codigo'] ?? ''))
            );

            Validator::make(
                [
                    ...$datos,
                    'codigo' => $codigo,
                ],
                [
                    'codigo' => [
                        'required',
                        'string',
                        'max:50',
                        Rule::unique('lotes', 'codigo'),
                    ],

                    'proveedor_id' => [
                        'nullable',
                        'integer',
                    ],

                    'referencia_compra' => [
                        'nullable',
                        'string',
                        'max:100',
                    ],

                    'origen' => [
                        'nullable',
                        'string',
                        'max:150',
                    ],

                    'observacion' => [
                        'nullable',
                        'string',
                    ],
                ]
            )->validate();

            $proveedorId = $datos['proveedor_id']
                ?? null;

            if ($proveedorId !== null) {
                $proveedor = Proveedor::query()
                    ->where('activo', true)
                    ->find($proveedorId);

                if (!$proveedor) {
                    throw new ReglaNegocioException(
                        'El proveedor no existe o se encuentra inactivo.'
                    );
                }
            }

            $lote = Lote::create([
                'proveedor_id' => $proveedorId,
                'codigo' => $codigo,
                'referencia_compra' =>
                    $datos['referencia_compra'] ?? null,
                'origen' => $datos['origen'] ?? null,
                'estado' => 'ABIERTO',
                'observacion' =>
                    $datos['observacion'] ?? null,
            ]);

            return $lote->fresh([
                'proveedor',
                'detalles',
            ]);
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
            $this->obtenerUsuarioAutorizado(
                $usuarioId
            );

            /*
             * Bloqueamos el lote para impedir que dos procesos
             * modifiquen simultáneamente su composición.
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

            Validator::make(
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

                    'tipo_cambio_compra_id' => [
                        'nullable',
                        'integer',
                        'exists:tipos_cambio,id',
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

                    'costo_unitario_bob' => [
                        'nullable',
                        'numeric',
                        'min:0',
                    ],

                    'observacion' => [
                        'nullable',
                        'string',
                    ],
                ]
            )->validate();

            $producto = Producto::query()
                ->where('activo', true)
                ->find($datos['producto_id']);

            if (!$producto) {
                throw new ReglaNegocioException(
                    'El producto no existe o se encuentra inactivo.'
                );
            }

            $yaExiste = DetalleLote::query()
                ->where('lote_id', $lote->id)
                ->where('producto_id', $producto->id)
                ->exists();

            if ($yaExiste) {
                throw new ReglaNegocioException(
                    'El producto ya se encuentra registrado en este lote.'
                );
            }

            $detalle = DetalleLote::create([
                'lote_id' => $lote->id,
                'producto_id' => $producto->id,

                'moneda_id' =>
                    $datos['moneda_id'] ?? null,

                'tipo_cambio_compra_id' =>
                    $datos['tipo_cambio_compra_id']
                    ?? null,

                'cantidad_esperada' =>
                    $datos['cantidad_esperada'],

                /*
                 * Al crear la composición del lote todavía
                 * no declaramos unidades físicamente recibidas.
                 */
                'cantidad_recibida' => 0,

                'costo_unitario_origen' =>
                    $datos['costo_unitario_origen']
                    ?? null,

                'costo_unitario_bob' =>
                    $datos['costo_unitario_bob']
                    ?? null,

                'observacion' =>
                    $datos['observacion'] ?? null,
            ]);

            return $detalle->fresh([
                'lote.proveedor',
                'producto.marca',
                'producto.categoria',
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

        if (
            !$usuario->tienePermiso(
                'importacion.gestionar'
            )
        ) {
            throw new ReglaNegocioException(
                'El usuario no cuenta con permiso para gestionar importaciones.'
            );
        }

        return $usuario;
    }
}