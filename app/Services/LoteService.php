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
                'origen' => $validados['origen'] ?? null,
                'estado' => 'ABIERTO',
                'observacion' => $validados['observacion'] ?? null,
            ]);

            return $lote->fresh(['proveedor', 'detalles']);
        }, 3);
    }

    public function agregarDetalle(int $usuarioId, int $loteId, array $datos): DetalleLote
    {
        return DB::transaction(function () use ($usuarioId, $loteId, $datos) {
            $usuario = $this->obtenerUsuarioAutorizado($usuarioId);

            $lote = Lote::query()
                ->lockForUpdate()
                ->find($loteId);

            if (!$lote) {
                throw new ReglaNegocioException('El lote solicitado no existe.');
            }

            if ($lote->estado !== 'ABIERTO') {
                throw new ReglaNegocioException(
                    'Solo se pueden agregar productos a un lote abierto.'
                );
            }

            $validator = Validator::make($datos, [
                'producto_id' => ['required', 'integer'],
                'moneda_id' => ['nullable', 'integer', 'exists:monedas,id'],
                'cantidad_esperada' => ['required', 'integer', 'min:1'],
                'costo_unitario_origen' => ['nullable', 'numeric', 'min:0'],
                'tipo_cambio_aplicado' => ['nullable', 'numeric', 'gt:0'],
                'observacion' => ['nullable', 'string'],
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $validados = $validator->validated();

            $producto = Producto::query()
                ->where('activo', true)
                ->find($validados['producto_id']);

            if (!$producto) {
                throw new ReglaNegocioException(
                    'El producto no existe o se encuentra inactivo.'
                );
            }

            $precioOrigen = array_key_exists('costo_unitario_origen', $validados)
                ? $validados['costo_unitario_origen']
                : null;

            $moneda = null;

            if (isset($validados['moneda_id'])) {
                $moneda = Moneda::query()
                    ->where('activo', true)
                    ->find($validados['moneda_id']);

                if (!$moneda) {
                    throw new ReglaNegocioException(
                        'La moneda seleccionada no se encuentra activa.'
                    );
                }
            }

            if ($precioOrigen !== null && !$moneda) {
                throw ValidationException::withMessages([
                    'moneda_id' => 'Debe seleccionar la moneda del precio unitario de compra.',
                ]);
            }

            if ($precioOrigen === null && isset($validados['tipo_cambio_aplicado'])) {
                throw ValidationException::withMessages([
                    'tipo_cambio_aplicado' =>
                        'No debe registrar un tipo de cambio si no existe un precio de compra.',
                ]);
            }

            $tipoCambioCompraId = null;
            $costoUnitarioBob = null;

            if ($precioOrigen !== null && $moneda) {
                switch ($moneda->codigo) {
                    case 'BOB':
                        $costoUnitarioBob = round((float) $precioOrigen, 2);
                        break;

                    case 'USD':
                        if (!isset($validados['tipo_cambio_aplicado'])) {
                            throw ValidationException::withMessages([
                                'tipo_cambio_aplicado' =>
                                    'Debe indicar el tipo de cambio realmente aplicado en la compra.',
                            ]);
                        }

                        $tipoCambio = $this->tipoCambioService->registrarAplicadoUsdBob(
                            $usuario->id,
                            (float) $validados['tipo_cambio_aplicado'],
                            "Lote {$lote->codigo} - {$producto->nombre}"
                        );

                        $tipoCambioCompraId = $tipoCambio->id;
                        $costoUnitarioBob = $this->tipoCambioService->convertirABob(
                            (float) $precioOrigen,
                            'USD',
                            $tipoCambio
                        );
                        break;

                    default:
                        throw new ReglaNegocioException(
                            'Actualmente la conversión de compras está habilitada únicamente para BOB y USD.'
                        );
                }
            }

            $detalle = DetalleLote::create([
                'lote_id' => $lote->id,
                'producto_id' => $producto->id,
                'moneda_id' => $moneda?->id,
                'tipo_cambio_compra_id' => $tipoCambioCompraId,
                'cantidad_esperada' => $validados['cantidad_esperada'],
                'cantidad_recibida' => 0,
                'costo_unitario_origen' => $precioOrigen,
                'costo_unitario_bob' => $costoUnitarioBob,
                'observacion' => $validados['observacion'] ?? null,
            ]);

            return $detalle->fresh([
                'lote.proveedor',
                'producto.marca',
                'producto.categoria',
                'moneda',
                'tipoCambioCompra',
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
