<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
    'name',
    'email',
    'password',
    'activo',
    'ultimo_acceso',
    'almacen_operativo_id',
];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'activo' => 'boolean',
        'ultimo_acceso' => 'datetime',
    ];


    public function almacenOperativo(): BelongsTo
    {
        return $this->belongsTo(
            Almacen::class,
            'almacen_operativo_id'
        );
    }

    public function esAdministradorGlobal(): bool
    {
        return $this->tieneRol('ADMINISTRADOR');
    }

    /**
     * Los administradores globales pueden operar cualquier sede.
     * Los usuarios operativos solo pueden actuar sobre su almacén asignado.
     */
    public function puedeOperarEnAlmacen(?int $almacenId): bool
    {
        if ($almacenId === null) {
            return false;
        }

        if ($this->esAdministradorGlobal()) {
            return true;
        }

        return $this->almacen_operativo_id !== null
            && (int) $this->almacen_operativo_id === (int) $almacenId;
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Rol::class,
            'rol_usuario',
            'usuario_id',
            'rol_id'
        );
    }
    public function tieneRol(string $codigo): bool
{
    return $this->roles()
        ->where('roles.codigo', $codigo)
        ->where('roles.activo', true)
        ->exists();
}

public function tienePermiso(string $codigo): bool
{
    return $this->roles()
        ->where('roles.activo', true)
        ->whereHas('permisos', function ($query) use ($codigo) {
            $query->where('permisos.codigo', $codigo)
                ->where('permisos.activo', true);
        })
        ->exists();
}
}
