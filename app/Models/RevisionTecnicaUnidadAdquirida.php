<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RevisionTecnicaUnidadAdquirida extends Model
{
    protected $table = 'revisiones_tecnicas_unidades_adquiridas';

    public const RESULTADO_INCOMPLETA = 'INCOMPLETA';
    public const RESULTADO_REQUIERE_PREPARACION = 'REQUIERE_PREPARACION';
    public const RESULTADO_APROBADA = 'APROBADA';

    public const CHECK_OK = 'OK';
    public const CHECK_FALLA = 'FALLA';
    public const CHECK_NO_APLICA = 'NO_APLICA';

    public const CHECKLIST = [
        'carga_bateria' => 'Carga y batería',
        'teclado' => 'Teclado',
        'camara' => 'Cámara',
        'wifi' => 'Wi-Fi',
        'microfono' => 'Micrófono',
        'touchpad' => 'Touchpad / trackpad',
        'pantalla_touch' => 'Pantalla táctil',
        'puertos_usb' => 'Puertos USB',
        'audio' => 'Audio / auriculares',
        'hdmi' => 'Puerto HDMI',
        'rendimiento' => 'Rendimiento general',
        'temperatura' => 'Temperatura',
        'ventilacion' => 'Ventilación / cooler',
        'limpieza' => 'Limpieza',
        'estado_fisico' => 'Estado físico visible',
    ];

    protected $fillable = [
        'unidad_adquirida_id',
        'usuario_id',
        'fecha_revision',
        'grado_final',
        'bateria_porcentaje',
        'enciende',
        'tiene_sistema_operativo',
        'tiene_cargador',
        'requiere_servicio',
        'servicio_requerido',
        'checklist_tecnico',
        'resultado',
        'observacion',
    ];

    protected $casts = [
        'fecha_revision' => 'datetime',
        'bateria_porcentaje' => 'integer',
        'enciende' => 'boolean',
        'tiene_sistema_operativo' => 'boolean',
        'tiene_cargador' => 'boolean',
        'requiere_servicio' => 'boolean',
        'checklist_tecnico' => 'array',
    ];

    public function unidadAdquirida(): BelongsTo
    {
        return $this->belongsTo(
            UnidadAdquirida::class,
            'unidad_adquirida_id'
        );
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'usuario_id'
        );
    }
}
