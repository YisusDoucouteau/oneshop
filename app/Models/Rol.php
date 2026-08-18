<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Rol extends Model
{
    protected $table = 'roles';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'activo',
    ];

    protected $casts = [
            'activo' => 'boolean',
        ];

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'rol_usuario',
            'rol_id',
            'usuario_id'
        );
    }

    public function permisos(): BelongsToMany
    {
        return $this->belongsToMany(
            Permiso::class,
            'permiso_rol',
            'rol_id',
            'permiso_id'
        );
    }
}