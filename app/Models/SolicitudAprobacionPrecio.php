<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SolicitudAprobacionPrecio extends Model
{
    use HasFactory;

    protected $table = 'solicitud_aprobacion_precios';


    protected $fillable = [

    'equipo_id',

    'usuario_solicitante_id',

    'precio_publicado',

    'precio_propuesto',

    'descuento_solicitado',

    'ganancia_estimada',

    'motivo',

    'estado',

    'usuario_aprobador_id',

    'fecha_aprobacion',

    'observacion_aprobacion',

    'medio_aprobacion',

];


    protected $casts = [

        'precio_publicado' => 'decimal:2',

        'precio_propuesto' => 'decimal:2',

        'descuento_solicitado' => 'decimal:2',

        'ganancia_estimada' => 'decimal:2',

        'fecha_aprobacion' => 'datetime',

    ];


    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */


    public function equipo()
    {
        return $this->belongsTo(
            Equipo::class
        );
    }


    public function usuarioSolicitante()
    {
        return $this->belongsTo(
            User::class,
            'usuario_solicitante_id'
        );
    }


    public function usuarioAprobador()
    {
        return $this->belongsTo(
            User::class,
            'usuario_aprobador_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Estados
    |--------------------------------------------------------------------------
    */

    public function estaPendiente(): bool
    {
        return $this->estado === 'PENDIENTE';
    }


    public function estaResuelta(): bool
    {
        return in_array(
            $this->estado,
            [
                'APROBADA',
                'RECHAZADA',
                'VENCIDA'
            ]
        );
    }
}