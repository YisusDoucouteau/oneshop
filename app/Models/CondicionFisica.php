<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CondicionFisica extends Model
{
    protected $table = 'condiciones_fisicas';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'activo',
    ];

    protected $casts = [
            'activo' => 'boolean',
        ];

    public function equipos(): HasMany
    {
        return $this->hasMany(
            Equipo::class,
            'condicion_fisica_id'
        );
    }
}