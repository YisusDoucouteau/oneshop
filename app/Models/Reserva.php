<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Reserva extends Model
{
    protected $table = 'reservas';

    protected $fillable = [
        'numero',
        'cliente_id',
        'registrado_por_id',
        'estado',
        'fecha_reserva',
        'fecha_expiracion',
        'fecha_cierre',
        'observacion',
    ];

    protected $casts = [
    'fecha_reserva' => 'datetime',
    'fecha_expiracion' => 'datetime',
    'fecha_cierre' => 'datetime',
];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'registrado_por_id'
        );
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(
            DetalleReserva::class,
            'reserva_id'
        );
    }

    public function prorrogas(): HasMany
    {
        return $this->hasMany(
            ProrrogaReserva::class,
            'reserva_id'
        );
    }

    public function notificaciones(): HasMany
    {
        return $this->hasMany(
            NotificacionReserva::class,
            'reserva_id'
        );
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(
            Pago::class,
            'reserva_id'
        );
    }

    public function venta(): HasOne
    {
        return $this->hasOne(
            Venta::class,
            'reserva_id'
        );
    }
}