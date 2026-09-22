<?php

namespace App\Services;

use App\Exceptions\ReglaNegocioException;
use App\Models\Moneda;
use App\Models\TipoCambio;
use App\Models\User;

class TipoCambioComercialService
{
    public const FUENTE = 'COMERCIAL_PRECIO';

    public function vigentePara(Moneda $monedaOrigen): ?TipoCambio
    {
        if ($monedaOrigen->codigo === 'BOB') {
            return null;
        }

        $bob = Moneda::query()
            ->where('codigo', 'BOB')
            ->where('activo', true)
            ->firstOrFail();

        return TipoCambio::query()
            ->where('moneda_origen_id', $monedaOrigen->id)
            ->where('moneda_destino_id', $bob->id)
            ->where('fuente', self::FUENTE)
            ->where('fecha_vigencia', '<=', now())
            ->orderByDesc('fecha_vigencia')
            ->orderByDesc('id')
            ->first();
    }

    public function registrar(
        int $usuarioId,
        int $monedaOrigenId,
        float $valor
    ): TipoCambio {
        if ($valor <= 0) {
            throw new ReglaNegocioException(
                'El tipo de cambio debe ser mayor que cero.'
            );
        }

        $usuario = User::query()
            ->where('activo', true)
            ->find($usuarioId);

        if (!$usuario || !$usuario->tienePermiso('precios.modificar')) {
            throw new ReglaNegocioException(
                'El usuario no está autorizado para modificar el tipo de cambio comercial.'
            );
        }

        $origen = Moneda::query()
            ->where('activo', true)
            ->find($monedaOrigenId);

        if (!$origen || $origen->codigo === 'BOB') {
            throw new ReglaNegocioException(
                'La moneda de origen no es válida para tipo de cambio comercial.'
            );
        }

        $bob = Moneda::query()
            ->where('codigo', 'BOB')
            ->where('activo', true)
            ->firstOrFail();

        return TipoCambio::query()->create([
            'moneda_origen_id' => $origen->id,
            'moneda_destino_id' => $bob->id,
            'valor' => round($valor, 6),
            'fecha_vigencia' => now(),
            'fuente' => self::FUENTE,
            'registrado_por_id' => $usuario->id,
        ]);
    }
}
