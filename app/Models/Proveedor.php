<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proveedor extends Model
{
    protected $table = 'proveedores';

    protected $fillable = [
        'nombre',
        'pais',
        'ciudad',
        'telefono',
        'correo',
        'contacto',
        'observacion',
        'activo',
    ];

    protected $casts = [
            'activo' => 'boolean',
        ];

    public function lotes(): HasMany
    {
        return $this->hasMany(Lote::class);
    }
    public function adquisicionesDirectas(): HasMany
{
    return $this->hasMany(
        AdquisicionDirecta::class,
        'proveedor_id'
    );
}
}