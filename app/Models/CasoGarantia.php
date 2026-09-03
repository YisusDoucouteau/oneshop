<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
class CasoGarantia extends Model
{
    protected $table = 'casos_garantia';


    protected $fillable = [

        'numero',
        'garantia_id',
        'equipo_afectado_id',
        'recibido_por_id',
        'tipo_caso',
        'estado',
        'fecha_apertura',
        'motivo_cliente',
        'diagnostico_final',
        'resolucion',
        'fecha_cierre',
        'cerrado_por_id',
        'observacion',

    ];


    protected $casts = [

        'fecha_apertura' => 'datetime',
        'fecha_cierre' => 'datetime',

    ];


    public function garantia(): BelongsTo
    {
        return $this->belongsTo(
            Garantia::class
        );
    }


    public function equipoAfectado(): BelongsTo
    {
        return $this->belongsTo(
            Equipo::class,
            'equipo_afectado_id'
        );
    }


    public function recibidoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'recibido_por_id'
        );
    }


    public function cerradoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'cerrado_por_id'
        );
    }


    public function intervenciones(): HasMany
    {
        return $this->hasMany(
            IntervencionGarantia::class,
            'caso_garantia_id'
        );
    }


    public function cambioEquipo(): HasOne
{
    return $this->hasOne(
        CambioEquipo::class,
        'caso_garantia_id'
    );
}
}