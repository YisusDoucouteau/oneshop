<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntervencionGarantia extends Model
{
    protected $table = 'intervenciones_garantia';


    protected $fillable = [

        'caso_garantia_id',
        'usuario_id',
        'reparacion_id',
        'tipo_intervencion',
        'fecha_intervencion',
        'descripcion',
        'resultado',

    ];


    protected $casts = [

        'fecha_intervencion' => 'datetime',

    ];


    public function casoGarantia(): BelongsTo
    {
        return $this->belongsTo(
            CasoGarantia::class,
            'caso_garantia_id'
        );
    }


    public function usuario(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
    }


    public function reparacion(): BelongsTo
    {
        return $this->belongsTo(
            Reparacion::class
        );
    }
}