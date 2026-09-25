<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetodoPago extends Model
{
    protected $table = 'metodos_pago';

    protected $fillable = [
        'codigo',
        'nombre',
        'requiere_verificacion',
        'activo',
    ];

    protected $casts = [
            'requiere_verificacion' => 'boolean',
            'activo' => 'boolean',
        ];

    public function pagos(): HasMany
    {
        return $this->hasMany(
            Pago::class,
            'metodo_pago_id'
        );
    }
    public function movimientosAjustesGarantia(): HasMany
{
    return $this->hasMany(
        MovimientoAjusteGarantia::class,
        'metodo_pago_id'
    );
}
}