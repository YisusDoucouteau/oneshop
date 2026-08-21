<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsignacionCostoLote extends Model
{
    protected $table = 'asignaciones_costos_lotes';

    protected $fillable = [
        'costo_lote_id',
        'equipo_id',
        'metodo_asignacion',
        'porcentaje',
        'monto_asignado_bob',
    ];

    protected $casts = [
        'porcentaje' => 'decimal:4',
        'monto_asignado_bob' => 'decimal:2',
    ];

    public function costoLote(): BelongsTo
    {
        return $this->belongsTo(
            CostoLote::class,
            'costo_lote_id'
        );
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }
}