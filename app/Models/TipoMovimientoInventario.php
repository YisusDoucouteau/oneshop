<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoMovimientoInventario extends Model
{
    protected $table = 'tipos_movimientos_inventario';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(
            MovimientoInventario::class,
            'tipo_movimiento_id'
        );
    }
}