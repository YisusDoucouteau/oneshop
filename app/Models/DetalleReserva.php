<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleReserva extends Model
{
    protected $table = 'detalles_reservas';

    protected $fillable = [
        'reserva_id',
        'equipo_id',
        'precio_acordado',
        'descuento_acordado',
        'observacion',
    ];

    protected function casts(): array
    {
        return [
            'precio_acordado' => 'decimal:2',
            'descuento_acordado' => 'decimal:2',
        ];
    }

    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class);
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }
}