<?php

namespace App\Services;

use App\Models\Venta;
use App\Exceptions\ReglaNegocioException;

class ProcesadorVentaService
{
    public function __construct(
        private readonly ValidadorVentaPrecioService $validadorPrecio,
        private readonly VentaService $ventaService
    ) {
    }


    /**
     * Procesa una venta validando reglas comerciales.
     *
     * Antes de registrar:
     * - valida precios
     * - valida descuentos
     * - bloquea ventas fuera de política
     */
    public function procesarVentaDirecta(
        int $vendedorId,
        array $equipos,
        ?int $clienteId = null,
        ?string $observacion = null,
        ?string $clienteNombre = null,
        ?string $clienteTelefono = null
    ): Venta {


        foreach ($equipos as $item) {


            if (
                !isset($item['equipo_id']) ||
                !isset($item['precio'])
            ) {
                throw new ReglaNegocioException(
                    'Información de venta incompleta.'
                );
            }


            $resultado =
                $this->validadorPrecio->validar(
                    equipoId: $item['equipo_id'],
                    precioPropuesto: $item['precio'],
                    clienteId: $clienteId,
                    vendedorId: $vendedorId
                );


            if (
                !$resultado['permitido']
                &&
                $resultado['solicitud'] === null
            ) {

                throw new ReglaNegocioException(
                    'El precio propuesto requiere aprobación.'
                );
            }


            if (
                !$resultado['permitido']
                &&
                $resultado['solicitud'] !== null
            ) {

                throw new ReglaNegocioException(
                    'Existe una solicitud de aprobación pendiente para este precio.'
                );
            }
        }



        $equiposIds =
            collect($equipos)
                ->pluck('equipo_id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

        $preciosAcordados =
    collect($equipos)
        ->mapWithKeys(
            fn ($item) => [
                (int) $item['equipo_id'] =>
                    (float) $item['precio'],
            ]
        )
        ->all();

        $condicionesVenta =
            collect($equipos)
                ->mapWithKeys(
                    fn ($item) => [
                        (int) $item['equipo_id'] =>
                            (string) (
                                $item['condicion']
                                ?? 'USADO'
                            ),
                    ]
                )
                ->all();

       return $this->ventaService
    ->registrarVentaDirecta(
        vendedorId: $vendedorId,
        equiposIds: $equiposIds,
        clienteId: $clienteId,
        observacion: $observacion,
        preciosAcordados: $preciosAcordados,
        clienteNombreSnapshot: $clienteNombre,
        clienteTelefonoSnapshot: $clienteTelefono,
        condicionesVenta: $condicionesVenta
    );
    }
}