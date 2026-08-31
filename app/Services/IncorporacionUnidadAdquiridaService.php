<?php

namespace App\Services;

use App\Exceptions\ReglaNegocioException;
use App\Models\Almacen;
use App\Models\UnidadAdquirida;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class IncorporacionUnidadAdquiridaService
{
    public function __construct(
        private RegistroEquipoService $registroEquipoService,
        private RegistroCostoUnidadService $registroCostoUnidadService
    ) {
    }

    /**
     * Incorpora una unidad adquirida al inventario formal.
     *
     * La unidad debe encontrarse previamente en RECIBIDA_ORURO.
     *
     * Antes de crear el equipo formal se registra una fotografía
     * del costo real de la unidad. El costo puede encontrarse
     * completo o incompleto; esto no impide la incorporación.
     *
     * El equipo formal se registra mediante RegistroEquipoService
     * para reutilizar las reglas centrales de inventario.
     */
    public function incorporar(
        int $usuarioId,
        int $unidadAdquiridaId,
        string $codigoInterno,
        ?int $condicionFisicaId = null,
        ?string $serialFabricante = null,
        ?string $observacion = null
    ): UnidadAdquirida {
        return DB::transaction(function () use (
            $usuarioId,
            $unidadAdquiridaId,
            $codigoInterno,
            $condicionFisicaId,
            $serialFabricante,
            $observacion
        ) {
            /*
            |--------------------------------------------------------------------------
            | Usuario
            |--------------------------------------------------------------------------
            */

            $usuario = $this->obtenerUsuarioAutorizado(
                $usuarioId
            );

            /*
            |--------------------------------------------------------------------------
            | Unidad adquirida
            |--------------------------------------------------------------------------
            |
            | Bloqueamos la unidad para impedir que dos procesos
            | intenten incorporarla simultáneamente.
            |
            */

            $unidad = UnidadAdquirida::query()
                ->with([
                    'producto',
                    'detalleLote',
                    'almacenActual',
                ])
                ->lockForUpdate()
                ->find($unidadAdquiridaId);

            if (!$unidad) {
                throw new ReglaNegocioException(
                    'La unidad adquirida seleccionada no existe.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Estado de la unidad
            |--------------------------------------------------------------------------
            */

            if (
                $unidad->estado !==
                UnidadAdquirida::ESTADO_RECIBIDA_ORURO
            ) {
                throw new ReglaNegocioException(
                    sprintf(
                        'La unidad debe encontrarse en estado %s para incorporarse al inventario. Estado actual: %s.',
                        UnidadAdquirida::ESTADO_RECIBIDA_ORURO,
                        $unidad->estado
                    )
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Evitar doble incorporación
            |--------------------------------------------------------------------------
            */

            if ($unidad->estaIncorporadaInventario()) {
                throw new ReglaNegocioException(
                    'La unidad adquirida ya se encuentra incorporada al inventario.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Producto
            |--------------------------------------------------------------------------
            */

            if (!$unidad->producto_id) {
                throw new ReglaNegocioException(
                    'La unidad adquirida no tiene un producto asociado.'
                );
            }

            $producto = $unidad->producto;

            if (!$producto || !$producto->activo) {
                throw new ReglaNegocioException(
                    'El producto asociado a la unidad no existe o se encuentra inactivo.'
                );
            }

            if (!$producto->es_serializado) {
                throw new ReglaNegocioException(
                    'La unidad adquirida corresponde a un producto que no admite registro individual por equipo.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Almacén
            |--------------------------------------------------------------------------
            |
            | La unidad debe estar físicamente en Oruro antes
            | de convertirse en un equipo del inventario.
            |
            */

            if (!$unidad->almacen_actual_id) {
                throw new ReglaNegocioException(
                    'La unidad adquirida no tiene un almacén actual.'
                );
            }

            $almacen = Almacen::query()
                ->where('activo', true)
                ->find($unidad->almacen_actual_id);

            if (!$almacen) {
                throw new ReglaNegocioException(
                    'El almacén actual de la unidad no existe o se encuentra inactivo.'
                );
            }

            if ($almacen->codigo !== 'ORURO_PRINCIPAL') {
                throw new ReglaNegocioException(
                    'La unidad adquirida debe encontrarse en el almacén principal de Oruro para incorporarse al inventario.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Datos de identificación
            |--------------------------------------------------------------------------
            */

            $codigoInterno = strtoupper(
                trim($codigoInterno)
            );

            $serialFabricante =
                $serialFabricante !== null
                    ? trim($serialFabricante)
                    : null;

            $serialFabricante =
                $serialFabricante === ''
                    ? null
                    : $serialFabricante;

            Validator::make(
                [
                    'codigo_interno' =>
                        $codigoInterno,

                    'serial_fabricante' =>
                        $serialFabricante,

                    'condicion_fisica_id' =>
                        $condicionFisicaId,
                ],
                [
                    'codigo_interno' => [
                        'required',
                        'string',
                        'max:50',
                        Rule::unique(
                            'equipos',
                            'codigo_interno'
                        ),
                    ],

                    'serial_fabricante' => [
                        'nullable',
                        'string',
                        'max:150',
                    ],

                    'condicion_fisica_id' => [
                        'nullable',
                        'integer',
                    ],
                ]
            )->validate();

            /*
            |--------------------------------------------------------------------------
            | Registro del costo real
            |--------------------------------------------------------------------------
            |
            | Antes de incorporar formalmente la unidad registramos
            | una fotografía del costo real calculado.
            |
            | El historial conserva si el cálculo está completo
            | o incompleto. Un costo incompleto no bloquea la
            | incorporación del equipo.
            |
            */

            $this->registroCostoUnidadService
                ->registrar(
                    $unidad,
                    $usuario->id
                );

            /*
            |--------------------------------------------------------------------------
            | Especificaciones
            |--------------------------------------------------------------------------
            |
            | Copiamos a la ficha formal del equipo la información
            | técnica que ya fue obtenida durante la revisión
            | de la unidad adquirida.
            |
            */

            $datosEquipo = [
                'producto_id' =>
                    $unidad->producto_id,

                'detalle_lote_id' =>
                    $unidad->detalle_lote_id,

                'almacen_actual_id' =>
                    $almacen->id,

                'condicion_fisica_id' =>
                    $condicionFisicaId,

                'codigo_interno' =>
                    $codigoInterno,

                'serial_fabricante' =>
                    $serialFabricante
                    ?? $unidad->serial_fabricante,

                'observacion' =>
                    $observacion
                    ?? $unidad->observacion_revision,

                'procesador' =>
                    $unidad->procesador,

                'generacion_procesador' =>
                    $unidad->generacion_procesador,

                'ram_gb' =>
                    $unidad->ram_gb,

                'almacenamiento_gb' =>
                    $unidad->almacenamiento_gb,

                'tipo_almacenamiento' =>
                    $unidad->tipo_almacenamiento,

                'tarjeta_grafica' =>
                    $unidad->tarjeta_grafica,

                'pantalla_pulgadas' =>
                    $unidad->pantalla_pulgadas,

                'resolucion' =>
                    $unidad->resolucion,

                'sistema_operativo' =>
                    $unidad->sistema_operativo,

                /*
                 * RegistroEquipoService contempla estos campos
                 * aunque actualmente la unidad adquirida no los
                 * almacena directamente.
                 */
                'bateria_porcentaje' =>
                    null,

                'datos_adicionales' =>
                    null,
            ];

            /*
            |--------------------------------------------------------------------------
            | Registro formal del equipo
            |--------------------------------------------------------------------------
            |
            | Delegamos en RegistroEquipoService para conservar
            | las reglas centrales de inventario.
            |
            */

            $equipo =
                $this->registroEquipoService
                    ->registrar(
                        $usuario->id,
                        $datosEquipo
                    );

            /*
            |--------------------------------------------------------------------------
            | Vinculación unidad -> equipo
            |--------------------------------------------------------------------------
            */

            $unidad->equipo_id =
                $equipo->id;

            $unidad->estado =
                UnidadAdquirida::ESTADO_INCORPORADA;

            $unidad->save();

            /*
            |--------------------------------------------------------------------------
            | Resultado
            |--------------------------------------------------------------------------
            */

            return $unidad->fresh([
                'producto.marca',
                'producto.categoria',
                'detalleLote.lote.proveedor',
                'almacenActual',
                'equipo.producto',
                'equipo.almacenActual',
                'equipo.estadoActual',
                'equipo.condicionFisica',
                'equipo.especificacion',
                'equipo.historialEstados.estadoDestino',
                'historialCostos',
            ]);
        }, 3);
    }

    /**
     * Comprueba que el usuario pueda gestionar la incorporación.
     */
    private function obtenerUsuarioAutorizado(
        int $usuarioId
    ): User {
        $usuario = User::query()
            ->where('activo', true)
            ->find($usuarioId);

        if (!$usuario) {
            throw new ReglaNegocioException(
                'El usuario no existe o se encuentra inactivo.'
            );
        }

        /*
         * La incorporación registra formalmente un equipo
         * dentro del inventario.
         *
         * Usamos el mismo permiso que RegistroEquipoService.
         */
        if (!$usuario->tienePermiso(
            'inventario.registrar'
        )) {
            throw new ReglaNegocioException(
                'El usuario no cuenta con permiso para incorporar unidades al inventario.'
            );
        }

        return $usuario;
    }
}