<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class Producto extends Model
{
    protected $table = 'productos';

    protected $fillable = [
        'categoria_producto_id',
        'marca_id',
        'codigo',
        'nombre',
        'modelo',
        'descripcion',
        'es_serializado',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'es_serializado' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(
            CategoriaProducto::class,
            'categoria_producto_id'
        );
    }

    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class);
    }

    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class);
    }

    public function detallesLotes(): HasMany
    {
        return $this->hasMany(
            DetalleLote::class,
            'producto_id'
        );
    }
    public function almacenes(): BelongsToMany
{
    return $this->belongsToMany(
        Almacen::class,
        'existencias_productos',
        'producto_id',
        'almacen_id'
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
        'producto_id'
    );
}

public function asignacionesComponentes(): HasMany
{
    return $this->hasMany(
        AsignacionComponente::class,
        'producto_id'
    );
}
}