<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncorporacionUnidadAdquirida extends Model
{

    protected $table =
        'incorporaciones_unidades_adquiridas';


    protected $fillable = [

        'unidad_adquirida_id',

        'equipo_id',

        'usuario_id',

        'condicion_fisica_id',

        'fecha_incorporacion',

        'observacion',

    ];



    protected $casts = [

        'fecha_incorporacion'
            => 'datetime',

    ];



    public function unidadAdquirida(): BelongsTo
    {
        return $this->belongsTo(
            UnidadAdquirida::class,
            'unidad_adquirida_id'
        );
    }



    public function equipo(): BelongsTo
    {
        return $this->belongsTo(
            Equipo::class,
            'equipo_id'
        );
    }



    public function usuario(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'usuario_id'
        );
    }



    public function condicionFisica(): BelongsTo
    {
        return $this->belongsTo(
            CondicionFisica::class,
            'condicion_fisica_id'
        );
    }

}