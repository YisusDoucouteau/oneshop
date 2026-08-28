<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UnidadAdquirida extends Model
{
    
    protected $table = 'unidades_adquiridas';

    public const ESTADO_PENDIENTE_LLEGADA =
        'PENDIENTE_LLEGADA';

    public const ESTADO_RECIBIDA_ORIGEN =
        'RECIBIDA_ORIGEN';

    public const ESTADO_EN_REVISION =
        'EN_REVISION';

    public const ESTADO_EN_PREPARACION =
        'EN_PREPARACION';

    public const ESTADO_LISTA_ENVIO =
        'LISTA_ENVIO';

    public const ESTADO_ENVIADA =
        'ENVIADA';

    public const ESTADO_RECIBIDA_ORURO =
        'RECIBIDA_ORURO';

    public const ESTADO_INCORPORADA =
        'INCORPORADA';

    protected $fillable = [
        'detalle_lote_id',
        'adquisicion_directa_id',
        'producto_id',
        'almacen_actual_id',
        'estado' ,
        'codigo_trazabilidad',
        'fecha_llegada',
        'fecha_revision',
        'fecha_lista_envio',

        'serial_fabricante',

        'procesador',
        'generacion_procesador',
        'ram_gb',
        'almacenamiento_gb',
        'tipo_almacenamiento',
        'tarjeta_grafica',
        'pantalla_pulgadas',
        'resolucion',
        'sistema_operativo',

        'enciende',
        'tiene_sistema_operativo',
        'tiene_cargador',

        'requiere_servicio',
        'servicio_requerido',
        'observacion_revision',

        'registrado_por_id',
        'revisado_por_id',

        'equipo_id',
    ];

    protected $casts = [
        'fecha_llegada' => 'datetime',
        'fecha_revision' => 'datetime',
        'fecha_lista_envio' => 'datetime',

        'ram_gb' => 'integer',
        'almacenamiento_gb' => 'integer',
        'pantalla_pulgadas' => 'decimal:1',

        'enciende' => 'boolean',
        'tiene_sistema_operativo' => 'boolean',
        'tiene_cargador' => 'boolean',
        'requiere_servicio' => 'boolean',
    ];

    public function detalleLote(): BelongsTo
    {
        return $this->belongsTo(
            DetalleLote::class,
            'detalle_lote_id'
        );
    }

    public function adquisicionDirecta(): BelongsTo
    {
        return $this->belongsTo(
            AdquisicionDirecta::class,
            'adquisicion_directa_id'
        );
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(
            Producto::class,
            'producto_id'
        );
    }

    public function almacenActual(): BelongsTo
    {
        return $this->belongsTo(
            Almacen::class,
            'almacen_actual_id'
        );
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'registrado_por_id'
        );
    }

    public function revisadoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'revisado_por_id'
        );
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(
            Equipo::class,
            'equipo_id'
        );
    }

    public function provieneDeLote(): bool
    {
        return $this->detalle_lote_id !== null;
    }

    public function provieneDeAdquisicionDirecta(): bool
    {
        return $this->adquisicion_directa_id !== null;
    }

    public function estaIncorporadaInventario(): bool
    {
        return $this->equipo_id !== null;
    }
    public function intervenciones(): HasMany
{
    return $this->hasMany(
        IntervencionUnidadAdquirida::class,
        'unidad_adquirida_id'
    )->orderBy('fecha_inicio');
}
public function envioImportacionUnidad(): HasOne
{
    return $this->hasOne(
        EnvioImportacionUnidad::class,
        'unidad_adquirida_id'
    );
}
}