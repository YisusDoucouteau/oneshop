<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParametroSistema extends Model
{
    protected $table = 'parametros_sistema';

    protected $fillable = [
        'codigo',
        'nombre',
        'modulo',
        'tipo_dato',
        'valor',
        'descripcion',
        'editable',
        'activo',
        'modificado_por_id',
    ];

    protected function casts(): array
    {
        return [
            'editable' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    public function modificadoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'modificado_por_id'
        );
    }

    public function valorEntero(): int
    {
        return (int) $this->valor;
    }
}