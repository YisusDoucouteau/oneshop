<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProrrogaReserva extends Model
{
    protected $table = 'prorrogas_reservas';

    public const UPDATED_AT = null;

    protected $fillable = [
        'reserva_id',
        'autorizado_por_id',
        'fecha_expiracion_anterior',
        'nueva_fecha_expiracion',
        'motivo',
    ];

    protected $casts = [
        'fecha_expiracion_anterior' => 'datetime',
        'nueva_fecha_expiracion' => 'datetime',
    ];

    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class);
    }

    public function autorizadoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'autorizado_por_id'
        );
    }
}
