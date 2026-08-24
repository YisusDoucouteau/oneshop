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
     * Esta referencia aplica únicamente a USD.
     * No debe utilizarse como referencia de USDT.
     *
     * Si la API no responde, utiliza el último valor
     * almacenado localmente.
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
     * Registra el tipo de cambio realmente utilizado
     * en una operación.
     *
     * Soporta actualmente:
     *
     * USD  -> BOB
     * USDT -> BOB
     *
     * El valor registrado representa el costo histórico
     * real de la operación y no debe recalcularse después.
     */
    public function registrarAplicado(
        int $usuarioId,
        string $codigoMonedaOrigen,
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

        $codigoMonedaOrigen = strtoupper(
            trim($codigoMonedaOrigen)
        );

        if (!in_array(
            $codigoMonedaOrigen,
            ['USD', 'USDT'],
            true
        )) {
            throw new ReglaNegocioException(
                'Actualmente solo se permite registrar tipos de cambio aplicados para USD o USDT hacia BOB.'
            );
        }

        $monedaOrigen =
            $this->obtenerMonedaActiva(
                $codigoMonedaOrigen
            );

        $bob =
            $this->obtenerMonedaActiva(
                'BOB'
            );

        return TipoCambio::create([
            'moneda_origen_id' =>
                $monedaOrigen->id,

            'moneda_destino_id' =>
                $bob->id,

            'valor' => round(
                $valor,
                6
            ),

            'fecha_vigencia' =>
                now(),

            'fuente' =>
                self::FUENTE_APLICADA,

            'registrado_por_id' =>
                $usuario->id,

            'observacion' =>
                $observacion,
        ]);
    }


    /**
     * Compatibilidad con el código ya existente.
     *
     * Internamente utiliza el nuevo método genérico.
     */
    public function registrarAplicadoUsdBob(
        int $usuarioId,
        float $valor,
        ?string $observacion = null
    ): TipoCambio {
        return $this->registrarAplicado(
            $usuarioId,
            'USD',
            $valor,
            $observacion
        );
    }


    /**
     * Atajo explícito para operaciones realizadas
     * mediante USDT.
     */
    public function registrarAplicadoUsdtBob(
        int $usuarioId,
        float $valor,
        ?string $observacion = null
    ): TipoCambio {
        return $this->registrarAplicado(
            $usuarioId,
            'USDT',
            $valor,
            $observacion
        );
    }


    /**
     * Convierte un monto a bolivianos usando
     * exclusivamente el TC histórico aplicado
     * a esa operación.
     *
     * BOB:
     * no requiere conversión.
     *
     * USD / USDT:
     * requieren un TipoCambio almacenado que
     * corresponda exactamente a la moneda origen.
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

        /*
        |--------------------------------------------------------------------------
        | BOB
        |--------------------------------------------------------------------------
        */

        if ($codigoMoneda === 'BOB') {
            return round(
                $monto,
                2
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Monedas convertibles actualmente
        |--------------------------------------------------------------------------
        */

        if (!in_array(
            $codigoMoneda,
            ['USD', 'USDT'],
            true
        )) {
            throw new ReglaNegocioException(
                'La conversión a bolivianos solo está habilitada actualmente para BOB, USD y USDT.'
            );
        }

        if (!$tipoCambio) {
            throw new ReglaNegocioException(
                "Debe especificarse el tipo de cambio aplicado para convertir {$codigoMoneda} a BOB."
            );
        }

        $tipoCambio->loadMissing([
            'monedaOrigen',
            'monedaDestino',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Validación de par monetario
        |--------------------------------------------------------------------------
        |
        | Esto evita utilizar accidentalmente:
        |
        | USD/BOB para una compra USDT
        | USDT/BOB para una compra USD
        |
        */

        if (
            $tipoCambio->monedaOrigen?->codigo
                !== $codigoMoneda
            ||
            $tipoCambio->monedaDestino?->codigo
                !== 'BOB'
        ) {
            throw new ReglaNegocioException(
                "El tipo de cambio seleccionado no corresponde a {$codigoMoneda}/BOB."
            );
        }

        return round(
            $monto
            * (float) $tipoCambio->valor,
            2
        );
    }


    /**
     * Consulta exclusivamente la referencia
     * USD -> BOB mediante Frankfurter / BCBO.
     */
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

            $valor =
                $response->json('rate');

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
             */
            $fechaApi =
                $response->json('date');

            $fechaVigencia = $fechaApi
                ? Carbon::parse(
                    $fechaApi,
                    config('app.timezone')
                )->startOfDay()
                : now();

            /*
             * Una referencia BCBO por día.
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
                    'Tipo de cambio de referencia USD/BOB obtenido automáticamente mediante Frankfurter, proveedor BCBO.',
            ]);

            $tipoCambio->save();

            return [
                'tipo_cambio' =>
                    $tipoCambio->fresh([
                        'monedaOrigen',
                        'monedaDestino',
                    ]),

                'origen' =>
                    'API',

                'desactualizado' =>
                    false,
            ];

        } catch (Throwable $exception) {

            /*
             * El sistema no debe detenerse porque
             * el servicio externo esté temporalmente caído.
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
                ->latest(
                    'fecha_vigencia'
                )
                ->first();

            if (!$ultimo) {
                throw new ReglaNegocioException(
                    'No fue posible obtener el tipo de cambio USD/BOB de referencia y todavía no existe un valor almacenado localmente.'
                );
            }

            return [
                'tipo_cambio' =>
                    $ultimo->load([
                        'monedaOrigen',
                        'monedaDestino',
                    ]),

                'origen' =>
                    'BD',

                'desactualizado' =>
                    true,
            ];
        }
    }


    /**
     * Obtiene una moneda activa por su código.
     */
    private function obtenerMonedaActiva(
        string $codigo
    ): Moneda {
        $codigo = strtoupper(
            trim($codigo)
        );

        $moneda = Moneda::query()
            ->where(
                'codigo',
                $codigo
            )
            ->where(
                'activo',
                true
            )
            ->first();

        if (!$moneda) {
            throw new ReglaNegocioException(
                "La moneda {$codigo} no existe o se encuentra inactiva."
            );
        }

        return $moneda;
    }


    /**
     * Helper utilizado exclusivamente por
     * la referencia oficial USD -> BOB.
     */
    private function obtenerMonedasUsdBob(): array
    {
        $usd =
            $this->obtenerMonedaActiva(
                'USD'
            );

        $bob =
            $this->obtenerMonedaActiva(
                'BOB'
            );

        return [
            $usd,
            $bob,
        ];
    }
}