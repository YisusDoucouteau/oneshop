<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoliticaDescuento extends Model
{
    protected $table = 'politicas_descuentos';

    protected $fillable = [
        'codigo',
        'nombre',
        'categoria_producto_id',
        'base_antiguedad',
        'dias_desde',
        'dias_hasta',
        'porcentaje_maximo',
        'utilidad_minima_bob',
        'permite_precio_costo',
        'requiere_autorizacion',
        'vigente_desde',
        'vigente_hasta',
        'activo',
    ];

    protected $casts = [
        'dias_desde' => 'integer',
        'dias_hasta' => 'integer',
        'porcentaje_maximo' => 'decimal:2',
        'utilidad_minima_bob' => 'decimal:2',
        'permite_precio_costo' => 'boolean',
        'requiere_autorizacion' => 'boolean',
        'vigente_desde' => 'date',
        'vigente_hasta' => 'date',
        'activo' => 'boolean',
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(
            CategoriaProducto::class,
            'categoria_producto_id'
        );
    }
}