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
        'fecha_vencimiento_anterior',
        'fecha_vencimiento_nueva',
        'motivo',
    ];

    protected $casts = [
            'fecha_vencimiento_anterior' => 'datetime',
            'fecha_vencimiento_nueva' => 'datetime',
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