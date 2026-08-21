<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdquisicionDirecta extends Model
{
    protected $table = 'adquisiciones_directas';

    protected $fillable = [
        'equipo_id',
        'proveedor_id',
        'fecha_adquisicion',
        'referencia_compra',
        'origen',
        'registrado_por_id',
        'observacion',
    ];

    protected $casts = [
        'fecha_adquisicion' => 'date',
    ];

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'registrado_por_id'
        );
    }
}