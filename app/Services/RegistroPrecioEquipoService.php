<?php

namespace App\Services;

use App\Models\Equipo;
use App\Models\PrecioEquipo;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RegistroPrecioEquipoService
{
    public function __construct(
        private CostoComercialActualService $costoComercialActualService
    ) {
    }

    /**
     * Registra un nuevo precio para un equipo.
     *
     * El snapshot económico se calcula server-side utilizando el costo
     * comercial actual. Si el equipo usa moneda extranjera, también se
     * conserva el tipo de cambio utilizado en ese momento.
     */
    public function registrar(
        int $equipoId,
        float $precioSugerido,
        float $precioPublico,
        ?float $precioMinimoAutorizado = null,
        ?int $tipoCambioId = null,
        ?int $aprobadoPorId = null,
        ?string $observacion = null
    ): PrecioEquipo {
        return DB::transaction(function () use (
            $equipoId,
            $precioSugerido,
            $precioPublico,
            $precioMinimoAutorizado,
            $tipoCambioId,
            $aprobadoPorId,
            $observacion
        ) {

            $equipo = Equipo::query()
                ->lockForUpdate()
                ->find($equipoId);

            if (!$equipo) {
                throw new InvalidArgumentException(
                    'El equipo indicado no existe.'
                );
            }

            $desgloseCosto =
                $this->costoComercialActualService
                    ->calcular($equipo);

            $costoReal =
                (float) (
                    $desgloseCosto['costo_total']
                    ?? 0
                );

            /*
             * Si existe un tipo de cambio comercial calculado por el sistema,
             * tiene prioridad sobre cualquier id suministrado externamente.
             */
            $tipoCambioId =
                $desgloseCosto['tipo_cambio_id']
                ?? $tipoCambioId;

            if ($costoReal < 0) {
                throw new InvalidArgumentException(
                    'El costo calculado no puede ser negativo.'
                );
            }

            if ($precioSugerido < 0) {
                throw new InvalidArgumentException(
                    'El precio sugerido no puede ser negativo.'
                );
            }

            if ($precioPublico <= 0) {
                throw new InvalidArgumentException(
                    'El precio público debe ser mayor que cero.'
                );
            }

            if (
                $precioMinimoAutorizado !== null
                && $precioMinimoAutorizado < 0
            ) {
                throw new InvalidArgumentException(
                    'El precio mínimo autorizado no puede ser negativo.'
                );
            }

            if (
                $precioMinimoAutorizado !== null
                && $precioMinimoAutorizado > $precioPublico
            ) {
                throw new InvalidArgumentException(
                    'El precio mínimo autorizado no puede ser mayor al precio público.'
                );
            }

            $precioAnterior = PrecioEquipo::query()
                ->where('equipo_id', $equipo->id)
                ->where('vigente', true)
                ->lockForUpdate()
                ->latest('vigente_desde')
                ->first();

            if ($precioAnterior) {
                $precioAnterior->update([
                    'vigente' => false,
                    'vigente_hasta' => now(),
                ]);
            }

            $precio = PrecioEquipo::create([
                'equipo_id' =>
                    $equipo->id,

                'tipo_cambio_id' =>
                    $tipoCambioId,

                'costo_total_snapshot' =>
                    round(
                        $costoReal,
                        2
                    ),

                'precio_sugerido' =>
                    round(
                        $precioSugerido,
                        2
                    ),

                'precio_publico' =>
                    round(
                        $precioPublico,
                        2
                    ),

                'precio_minimo_autorizado' =>
                    $precioMinimoAutorizado !== null
                        ? round(
                            $precioMinimoAutorizado,
                            2
                        )
                        : null,

                'vigente_desde' =>
                    now(),

                'vigente_hasta' =>
                    null,

                'vigente' =>
                    true,

                'aprobado_por_id' =>
                    $aprobadoPorId,

                'observacion' =>
                    $observacion,
            ]);

            return $precio->fresh([
                'equipo',
                'tipoCambio',
                'aprobadoPor',
            ]);
        });
    }

    public function obtenerVigente(
        int $equipoId
    ): ?PrecioEquipo {
        return PrecioEquipo::query()
            ->where('equipo_id', $equipoId)
            ->where('vigente', true)
            ->latest('vigente_desde')
            ->first();
    }

    public function cerrarVigente(
        int $equipoId
    ): ?PrecioEquipo {
        return DB::transaction(function () use ($equipoId) {

            $precio = PrecioEquipo::query()
                ->where('equipo_id', $equipoId)
                ->where('vigente', true)
                ->lockForUpdate()
                ->latest('vigente_desde')
                ->first();

            if (!$precio) {
                return null;
            }

            $precio->update([
                'vigente' => false,
                'vigente_hasta' => now(),
            ]);

            return $precio->fresh();
        });
    }
}
