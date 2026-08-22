<?php

namespace App\Services;

use App\Exceptions\ReglaNegocioException;
use App\Models\Moneda;
use App\Models\TipoCambio;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class TipoCambioService
{
    private const FUENTE_REFERENCIA =
        'FRANKFURTER_BCBO';

    private const FUENTE_APLICADA =
        'MANUAL_OPERACION';

    /**
     * Obtiene la referencia oficial USD -> BOB.
     *
     * Si la API no responde, utiliza el último valor
     * almacenado en la base de datos.
     */
    public function obtenerReferenciaUsdBob(
        bool $forzarActualizacion = false
    ): array {
        $cacheKey =
            'tipo_cambio.referencia.USD.BOB.BCBO';

        if ($forzarActualizacion) {
            Cache::forget($cacheKey);
        }

        $minutos = max(
            1,
            (int) config(
                'services.frankfurter.cache_minutes',
                60
            )
        );

        return Cache::remember(
            $cacheKey,
            now()->addMinutes($minutos),
            fn () =>
                $this->consultarReferenciaUsdBob()
        );
    }

    /**
     * Registra el TC que realmente fue utilizado
     * en una operación de OneShop.
     *
     * Este valor no se modifica posteriormente.
     */
    public function registrarAplicadoUsdBob(
        int $usuarioId,
        float $valor,
        ?string $observacion = null
    ): TipoCambio {
        if ($valor <= 0) {
            throw new ReglaNegocioException(
                'El tipo de cambio aplicado debe ser mayor a cero.'
            );
        }

        $usuario = User::query()
            ->where('activo', true)
            ->find($usuarioId);

        if (!$usuario) {
            throw new ReglaNegocioException(
                'El usuario no existe o se encuentra inactivo.'
            );
        }

        [$usd, $bob] =
            $this->obtenerMonedasUsdBob();

        return TipoCambio::create([
            'moneda_origen_id' => $usd->id,
            'moneda_destino_id' => $bob->id,

            'valor' => round(
                $valor,
                6
            ),

            'fecha_vigencia' => now(),

            'fuente' =>
                self::FUENTE_APLICADA,

            'registrado_por_id' =>
                $usuario->id,

            'observacion' =>
                $observacion,
        ]);
    }

    /**
     * Convierte un importe a bolivianos empleando
     * un TC concreto ya almacenado.
     */
    public function convertirABob(
        float $monto,
        string $codigoMoneda,
        ?TipoCambio $tipoCambio = null
    ): float {
        if ($monto < 0) {
            throw new ReglaNegocioException(
                'El monto no puede ser negativo.'
            );
        }

        $codigoMoneda = strtoupper(
            trim($codigoMoneda)
        );

        if ($codigoMoneda === 'BOB') {
            return round($monto, 2);
        }

        if ($codigoMoneda !== 'USD') {
            throw new ReglaNegocioException(
                'La conversión automática solo está habilitada actualmente para USD y BOB.'
            );
        }

        if (!$tipoCambio) {
            throw new ReglaNegocioException(
                'Debe especificarse el tipo de cambio aplicado para convertir USD a BOB.'
            );
        }

        $tipoCambio->loadMissing([
            'monedaOrigen',
            'monedaDestino',
        ]);

        if (
            $tipoCambio->monedaOrigen?->codigo
                !== 'USD'
            ||
            $tipoCambio->monedaDestino?->codigo
                !== 'BOB'
        ) {
            throw new ReglaNegocioException(
                'El tipo de cambio seleccionado no corresponde a USD/BOB.'
            );
        }

        return round(
            $monto
            * (float) $tipoCambio->valor,
            2
        );
    }

    private function consultarReferenciaUsdBob(): array
    {
        [$usd, $bob] =
            $this->obtenerMonedasUsdBob();

        try {
            $baseUrl = rtrim(
                (string) config(
                    'services.frankfurter.base_url'
                ),
                '/'
            );

            $provider = (string) config(
                'services.frankfurter.provider',
                'BCBO'
            );

            $response = Http::acceptJson()
                ->timeout(5)
                ->retry(2, 300)
                ->get(
                    "{$baseUrl}/rate/USD/BOB",
                    [
                        'providers' => $provider,
                    ]
                );

            if (!$response->successful()) {
                throw new \RuntimeException(
                    'Respuesta HTTP no satisfactoria.'
                );
            }

            $valor = $response->json('rate');

            if (
                !is_numeric($valor)
                || (float) $valor <= 0
            ) {
                throw new \RuntimeException(
                    'La respuesta no contiene un tipo de cambio válido.'
                );
            }

            /*
             * Frankfurter trabaja con tasas diarias.
             * Si devuelve fecha la conservamos como
             * fecha oficial de la cotización.
             */
            $fechaApi = $response->json('date');

            $fechaVigencia = $fechaApi
                ? Carbon::parse(
                    $fechaApi,
                    config('app.timezone')
                )->startOfDay()
                : now();

            /*
             * Una sola referencia BCBO por día.
             * Si el proveedor corrige el valor durante
             * el mismo día, actualizamos esa referencia.
             */
            $tipoCambio = TipoCambio::query()
                ->where(
                    'moneda_origen_id',
                    $usd->id
                )
                ->where(
                    'moneda_destino_id',
                    $bob->id
                )
                ->where(
                    'fuente',
                    self::FUENTE_REFERENCIA
                )
                ->whereDate(
                    'fecha_vigencia',
                    $fechaVigencia->toDateString()
                )
                ->first();

            if (!$tipoCambio) {
                $tipoCambio =
                    new TipoCambio();
            }

            $tipoCambio->fill([
                'moneda_origen_id' =>
                    $usd->id,

                'moneda_destino_id' =>
                    $bob->id,

                'valor' => round(
                    (float) $valor,
                    6
                ),

                'fecha_vigencia' =>
                    $fechaVigencia,

                'fuente' =>
                    self::FUENTE_REFERENCIA,

                'registrado_por_id' =>
                    null,

                'observacion' =>
                    'Tipo de cambio de referencia obtenido automáticamente mediante Frankfurter, proveedor BCBO.',
            ]);

            $tipoCambio->save();

            return [
                'tipo_cambio' =>
                    $tipoCambio->fresh([
                        'monedaOrigen',
                        'monedaDestino',
                    ]),

                'origen' => 'API',

                'desactualizado' => false,
            ];

        } catch (Throwable $exception) {

            /*
             * El sistema NO debe dejar de funcionar
             * porque el servicio externo esté caído.
             */
            $ultimo = TipoCambio::query()
                ->where(
                    'moneda_origen_id',
                    $usd->id
                )
                ->where(
                    'moneda_destino_id',
                    $bob->id
                )
                ->where(
                    'fuente',
                    self::FUENTE_REFERENCIA
                )
                ->latest('fecha_vigencia')
                ->first();

            if (!$ultimo) {
                throw new ReglaNegocioException(
                    'No fue posible obtener el tipo de cambio de referencia y todavía no existe un valor almacenado localmente.'
                );
            }

            return [
                'tipo_cambio' =>
                    $ultimo->load([
                        'monedaOrigen',
                        'monedaDestino',
                    ]),

                'origen' => 'BD',

                'desactualizado' => true,
            ];
        }
    }

    private function obtenerMonedasUsdBob(): array
    {
        $usd = Moneda::query()
            ->where('codigo', 'USD')
            ->where('activo', true)
            ->first();

        $bob = Moneda::query()
            ->where('codigo', 'BOB')
            ->where('activo', true)
            ->first();

        if (!$usd || !$bob) {
            throw new ReglaNegocioException(
                'Las monedas USD y BOB deben estar activas en el catálogo.'
            );
        }

        return [$usd, $bob];
    }
}