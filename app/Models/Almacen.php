<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class Almacen extends Model
{
    protected $table = 'almacenes';

    protected $fillable = [
        'codigo',
        'nombre',
        'ciudad',
        'direccion',
        'principal',
        'activo',
    ];

    protected $casts = [
            'principal' => 'boolean',
            'activo' => 'boolean',
        ];

    public function equipos(): HasMany
    {
        return $this->hasMany(
            Equipo::class,
            'almacen_actual_id'
        );
    }
    public function productos(): BelongsToMany
{
    return $this->belongsToMany(
        Producto::class,
        'existencias_productos',
        'almacen_id',
        'producto_id'
    )
        ->withPivot([
            'cantidad_disponible',
            'cantidad_reservada',
        ])
        ->withTimestamps();
}

public function movimientosInventario(): HasMany
{
    return $this->hasMany(
        MovimientoInventario::class,
        'almacen_id'
    );
}

public function transferenciasSalientes(): HasMany
{
    return $this->hasMany(
        Transferencia::class,
        'almacen_origen_id'
    );
}

public function transferenciasEntrantes(): HasMany
{
    return $this->hasMany(
        Transferencia::class,
        'almacen_destino_id'
    );
}
}