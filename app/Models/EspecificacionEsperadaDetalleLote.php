<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EspecificacionEsperadaDetalleLote extends Model
{
    protected $table =
        'especificaciones_esperadas_detalles_lotes';

    protected $fillable = [
        'detalle_lote_id',
        'procesador',
        'generacion_procesador',
        'ram_gb',
        'almacenamiento_gb',
        'tipo_almacenamiento',
        'tarjeta_grafica',
        'pantalla_pulgadas',
        'resolucion',
        'sistema_operativo',
        'datos_adicionales',
    ];

    protected $casts = [
        'ram_gb' => 'integer',
        'almacenamiento_gb' => 'integer',
        'pantalla_pulgadas' => 'decimal:1',
        'datos_adicionales' => 'array',
    ];

    public function detalleLote(): BelongsTo
    {
        return $this->belongsTo(
            DetalleLote::class,
            'detalle_lote_id'
        );
    }
}