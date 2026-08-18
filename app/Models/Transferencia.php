<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Transferencia extends Model
{
    protected $table = 'transferencias';

    protected $fillable = [
        'codigo',
        'almacen_origen_id',
        'almacen_destino_id',
        'solicitado_por_id',
        'despachado_por_id',
        'recibido_por_id',
        'estado',
        'fecha_solicitud',
        'fecha_despacho',
        'fecha_recepcion',
        'observacion',
    ];

    protected $casts = [
            'fecha_solicitud' => 'datetime',
            'fecha_despacho' => 'datetime',
            'fecha_recepcion' => 'datetime',
        ];

    public function almacenOrigen(): BelongsTo
    {
        return $this->belongsTo(
            Almacen::class,
            'almacen_origen_id'
        );
    }

    public function almacenDestino(): BelongsTo
    {
        return $this->belongsTo(
            Almacen::class,
            'almacen_destino_id'
        );
    }

    public function solicitadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitado_por_id');
    }

    public function despachadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'despachado_por_id');
    }

    public function recibidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recibido_por_id');
    }

    public function equipos(): BelongsToMany
    {
        return $this->belongsToMany(
            Equipo::class,
            'transferencias_equipos',
            'transferencia_id',
            'equipo_id'
        );
    }

    public function productos(): BelongsToMany
    {
        return $this->belongsToMany(
            Producto::class,
            'transferencias_productos',
            'transferencia_id',
            'producto_id'
        )
            ->withPivot([
                'cantidad_enviada',
                'cantidad_recibida',
            ]);
    }
}