<?php

namespace App\Services;

use App\Models\PoliticaDescuento;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class GestorPoliticaDescuentoService
{
    /**
     * Crea una nueva política de descuento.
     */
    public function crear(array $datos): PoliticaDescuento
    {
        $datos = $this->validarDatos($datos);

        return DB::transaction(function () use ($datos) {
            return PoliticaDescuento::create($datos);
        });
    }

    /**
     * Actualiza una política existente.
     */
    public function actualizar(
        PoliticaDescuento $politica,
        array $datos
    ): PoliticaDescuento {
        $datos = $this->validarDatos(
            $datos,
            $politica
        );

        return DB::transaction(function () use (
            $politica,
            $datos
        ) {
            $politica->update($datos);

            return $politica->fresh();
        });
    }

    /**
     * Activa una política.
     */
    public function activar(
        PoliticaDescuento $politica
    ): PoliticaDescuento {
        $politica->activo = true;
        $politica->save();

        return $politica->fresh();
    }

    /**
     * Desactiva una política.
     */
    public function desactivar(
        PoliticaDescuento $politica
    ): PoliticaDescuento {
        $politica->activo = false;
        $politica->save();

        return $politica->fresh();
    }

    /**
     * Valida y normaliza los parámetros de una política.
     *
     * No fija valores comerciales como 20 %.
     * Esos valores deben ser configurables por administración.
     */
    private function validarDatos(
        array $datos,
        ?PoliticaDescuento $politica = null
    ): array {
        /*
        |--------------------------------------------------------------------------
        | Campos obligatorios
        |--------------------------------------------------------------------------
        */

        if (
            !array_key_exists('codigo', $datos) ||
            trim((string) $datos['codigo']) === ''
        ) {
            throw new InvalidArgumentException(
                'El código de la política es obligatorio.'
            );
        }

        if (
            !array_key_exists('nombre', $datos) ||
            trim((string) $datos['nombre']) === ''
        ) {
            throw new InvalidArgumentException(
                'El nombre de la política es obligatorio.'
            );
        }

        if (
            !array_key_exists('dias_desde', $datos)
        ) {
            throw new InvalidArgumentException(
                'El día inicial de la política es obligatorio.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Normalización
        |--------------------------------------------------------------------------
        */

        $datos['codigo'] =
            trim((string) $datos['codigo']);

        $datos['nombre'] =
            trim((string) $datos['nombre']);

        $datos['base_antiguedad'] =
            $datos['base_antiguedad']
            ?? 'FECHA_DISPONIBLE';

        $datos['dias_desde'] =
            (int) $datos['dias_desde'];

        if (
            $datos['dias_hasta'] ?? null
            !== null
        ) {
            $datos['dias_hasta'] =
                (int) $datos['dias_hasta'];
        }

        if (
            $datos['porcentaje_maximo'] ?? null
            !== null
        ) {
            $datos['porcentaje_maximo'] =
                (float) $datos['porcentaje_maximo'];
        }

        if (
            $datos['utilidad_minima_bob'] ?? null
            !== null
        ) {
            $datos['utilidad_minima_bob'] =
                (float) $datos['utilidad_minima_bob'];
        }

        $datos['permite_precio_costo'] =
            (bool) (
                $datos['permite_precio_costo']
                ?? false
            );

        $datos['requiere_autorizacion'] =
            (bool) (
                $datos['requiere_autorizacion']
                ?? false
            );

        $datos['activo'] =
            (bool) (
                $datos['activo']
                ?? true
            );

        /*
        |--------------------------------------------------------------------------
        | Fechas
        |--------------------------------------------------------------------------
        */

        if (
            !array_key_exists(
                'vigente_desde',
                $datos
            )
            ||
            empty($datos['vigente_desde'])
        ) {
            throw new InvalidArgumentException(
                'La fecha de inicio de vigencia es obligatoria.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Validación de antigüedad
        |--------------------------------------------------------------------------
        */

        if ($datos['dias_desde'] < 0) {
            throw new InvalidArgumentException(
                'Los días desde no pueden ser negativos.'
            );
        }

        if (
            isset($datos['dias_hasta'])
            &&
            $datos['dias_hasta'] !== null
            &&
            $datos['dias_hasta']
                < $datos['dias_desde']
        ) {
            throw new InvalidArgumentException(
                'Los días hasta no pueden ser menores que los días desde.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Validación del porcentaje
        |--------------------------------------------------------------------------
        */

        if (
            isset($datos['porcentaje_maximo'])
            &&
            $datos['porcentaje_maximo'] !== null
            &&
            (
                $datos['porcentaje_maximo'] < 0
                ||
                $datos['porcentaje_maximo'] > 100
            )
        ) {
            throw new InvalidArgumentException(
                'El porcentaje máximo debe estar entre 0 y 100.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Validación de utilidad mínima
        |--------------------------------------------------------------------------
        */

        if (
            isset($datos['utilidad_minima_bob'])
            &&
            $datos['utilidad_minima_bob'] !== null
            &&
            $datos['utilidad_minima_bob'] < 0
        ) {
            throw new InvalidArgumentException(
                'La utilidad mínima no puede ser negativa.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Vigencia
        |--------------------------------------------------------------------------
        */

        if (
            isset($datos['vigente_hasta'])
            &&
            $datos['vigente_hasta'] !== null
            &&
            $datos['vigente_hasta']
                < $datos['vigente_desde']
        ) {
            throw new InvalidArgumentException(
                'La fecha de finalización no puede ser anterior a la fecha de inicio.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Código único
        |--------------------------------------------------------------------------
        |
        | La base de datos también protege esta regla mediante UNIQUE.
        | Aquí damos un mensaje de negocio más claro.
        |
        */

        $consulta = PoliticaDescuento::query()
            ->where(
                'codigo',
                $datos['codigo']
            );

        if ($politica) {
            $consulta->where(
                'id',
                '!=',
                $politica->id
            );
        }

        if ($consulta->exists()) {
            throw new InvalidArgumentException(
                'Ya existe una política con ese código.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Campos permitidos
        |--------------------------------------------------------------------------
        */

        return array_intersect_key(
            $datos,
            array_flip([
                'codigo',
                'nombre',
                'categoria_producto_id',
                'base_antiguedad',
                'dias_desde',
                'dias_hasta',
                'porcentaje_maximo',
                'utilidad_minima_bob',
                'permite_precio_costo',
                'requiere_autorizacion',
                'vigente_desde',
                'vigente_hasta',
                'activo',
            ])
        );
    }
}