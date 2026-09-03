<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoliticaGarantia extends Model
{
    protected $table = 'politicas_garantias';


    protected $fillable = [

        'codigo',
        'nombre',

        'categoria_producto_id',

        'producto_id',

        'duracion_meses',

        'condiciones',

        'exclusiones',

        'vigente_desde',

        'vigente_hasta',

        'activo',

    ];


    protected $casts = [

        'vigente_desde' => 'date',

        'vigente_hasta' => 'date',

        'activo' => 'boolean',

    ];


    public function categoriaProducto(): BelongsTo
    {
        return $this->belongsTo(
            CategoriaProducto::class
        );
    }


    public function producto(): BelongsTo
    {
        return $this->belongsTo(
            Producto::class
        );
    }


    public function estaVigente(): bool
    {
        $hoy = now()->toDateString();

        return $this->activo
            && $this->vigente_desde <= $hoy
            && (
                $this->vigente_hasta === null
                || $this->vigente_hasta >= $hoy
            );
    }
}