<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Moneda extends Model
{
    protected $table = 'monedas';

    protected $fillable = [
        'codigo',
        'nombre',
        'simbolo',
        'activo',
    ];

    protected $casts = [
            'activo' => 'boolean',
        ];

    public function tiposCambioOrigen(): HasMany
    {
        return $this->hasMany(
            TipoCambio::class,
            'moneda_origen_id'
        );
    }

    public function tiposCambioDestino(): HasMany
    {
        return $this->hasMany(
            TipoCambio::class,
            'moneda_destino_id'
        );
    }
}