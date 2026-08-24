<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComponenteEsperadoDetalleLote extends Model
{
    protected $table =
        'componentes_esperados_detalles_lotes';

    protected $fillable = [
        'detalle_lote_id',
        'nombre',
        'cantidad_por_unidad',
        'incluido_en_compra',
        'observacion',
    ];

    protected $casts = [
        'cantidad_por_unidad' => 'integer',
        'incluido_en_compra' => 'boolean',
    ];

    public function detalleLote(): BelongsTo
    {
        return $this->belongsTo(
            DetalleLote::class,
            'detalle_lote_id'
        );
    }
}