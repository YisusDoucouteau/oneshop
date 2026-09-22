<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificacionReserva extends Model
{
    protected $table = 'notificaciones_reservas';

    public const UPDATED_AT = null;

    protected $fillable = [
        'reserva_id',
        'usuario_id',
        'tipo',
        'canal',
        'destino',
        'fecha_notificacion',
        'resultado',
        'descripcion',
    ];

    protected $casts = [
        'fecha_notificacion' => 'datetime',
    ];

    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
