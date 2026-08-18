<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Reparacion extends Model
{
    protected $table = 'reparaciones';

    protected $fillable = [
        'equipo_id',
        'diagnostico_id',
        'tecnico_id',
        'autorizado_por_id',
        'estado',
        'fecha_inicio',
        'fecha_fin',
        'trabajo_realizado',
        'resultado',
        'observacion',
    ];

    protected $casts = [
            'fecha_inicio' => 'datetime',
            'fecha_fin' => 'datetime',
        ];

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    public function diagnostico(): BelongsTo
    {
        return $this->belongsTo(Diagnostico::class);
    }

    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'tecnico_id'
        );
    }

    public function autorizadoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'autorizado_por_id'
        );
    }

    public function componentesUtilizados(): BelongsToMany
    {
        return $this->belongsToMany(
            AsignacionComponente::class,
            'reparaciones_componentes',
            'reparacion_id',
            'asignacion_componente_id'
        );
    }
}