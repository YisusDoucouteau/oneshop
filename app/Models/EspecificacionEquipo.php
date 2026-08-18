<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EspecificacionEquipo extends Model
{
    protected $table = 'especificaciones_equipos';

    protected $fillable = [
        'equipo_id',
        'procesador',
        'generacion_procesador',
        'ram_gb',
        'almacenamiento_gb',
        'tipo_almacenamiento',
        'tarjeta_grafica',
        'pantalla_pulgadas',
        'resolucion',
        'sistema_operativo',
        'bateria_porcentaje',
        'datos_adicionales',
    ];

    protected $casts = [
            'ram_gb' => 'integer',
            'almacenamiento_gb' => 'integer',
            'pantalla_pulgadas' => 'decimal:1',
            'bateria_porcentaje' => 'integer',
            'datos_adicionales' => 'array',
        ];

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }
}