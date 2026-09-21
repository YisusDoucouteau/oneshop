<?php

namespace App\Services;

use App\Exceptions\ReglaNegocioException;
use App\Models\Almacen;
use App\Models\CondicionFisica;
use App\Models\EnvioImportacion;
use App\Models\EnvioImportacionUnidad;
use App\Models\UnidadAdquirida;
use App\Models\User;
use App\Models\IncorporacionUnidadAdquirida;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;


class IncorporacionUnidadAdquiridaService
{
    public function __construct(
        private RegistroEquipoService $registroEquipoService,
        private RegistroCostoUnidadService $registroCostoUnidadService,
        private GeneradorCodigoInventarioService $generadorCodigoInventarioService
    ) {
    }


    /**
     * Incorpora una unidad adquirida al inventario formal.
     *
     * La unidad debe encontrarse previamente en RECIBIDA_ORURO.
     *
     * El código interno de inventario es generado automáticamente
     * al momento de la incorporación formal en Oruro.
     */
    public function incorporar(
        int $usuarioId,
        int $unidadAdquiridaId,
        ?int $condicionFisicaId = null,
        ?string $serialFabricante = null,
        ?string $observacion = null
    ): UnidadAdquirida {

        return DB::transaction(function () use (
            $usuarioId,
            $unidadAdquiridaId,
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
            */

            $unidad = UnidadAdquirida::query()
                ->with([
                    'producto',
                    'detalleLote',
                    'almacenActual',
                    'envioImportacionUnidad.envioImportacion',
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
            | Estado de unidad
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
            | Cierre logístico previo
            |--------------------------------------------------------------------------
            |
            | Una unidad proveniente de lote solamente puede formalizarse como Equipo
            | después de que su traslado haya sido cerrado en destino.
            |
            | RECIBIDO_PARCIAL también es válido: una unidad que sí llegó físicamente
            | puede incorporarse aunque otra unidad del mismo envío haya quedado
            | faltante o exista otra diferencia logística.
            |
            */

            if ($unidad->provieneDeLote()) {
                $asignacionEnvio =
                    $unidad->envioImportacionUnidad;

                if (
                    !$asignacionEnvio
                    || !$asignacionEnvio->envioImportacion
                ) {
                    throw new ReglaNegocioException(
                        'La unidad importada no tiene un envío logístico cerrado asociado.'
                    );
                }

                $envio =
                    $asignacionEnvio->envioImportacion;

                if (
                    !in_array(
                        $envio->estado,
                        [
                            EnvioImportacion::ESTADO_RECIBIDO,
                            EnvioImportacion::ESTADO_RECIBIDO_PARCIAL,
                        ],
                        true
                    )
                ) {
                    throw new ReglaNegocioException(
                        'La recepción del envío debe estar cerrada antes de incorporar la unidad al inventario.'
                    );
                }

                if (
                    !in_array(
                        $asignacionEnvio->estado_recepcion,
                        [
                            EnvioImportacionUnidad::ESTADO_RECIBIDA,
                            EnvioImportacionUnidad::ESTADO_INCIDENCIA,
                        ],
                        true
                    )
                ) {
                    throw new ReglaNegocioException(
                        'La unidad no figura como recibida físicamente en el cierre logístico.'
                    );
                }
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
            | Almacén Oruro
            |--------------------------------------------------------------------------
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


            if (!$usuario->puedeOperarEnAlmacen($almacen->id)) {

                throw new ReglaNegocioException(
                    'El usuario no puede incorporar equipos en el almacén de destino de esta unidad.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Condición física
            |--------------------------------------------------------------------------
            */

            if ($condicionFisicaId === null) {

                throw new ReglaNegocioException(
                    'Debe seleccionar la condición física con la que el equipo ingresará al inventario.'
                );
            }


            $condicionFisica = CondicionFisica::query()
                ->where('activo', true)
                ->find($condicionFisicaId);


            if (!$condicionFisica) {

                throw new ReglaNegocioException(
                    'La condición física seleccionada no existe o se encuentra inactiva.'
                );
            }



            /*
            |--------------------------------------------------------------------------
            | Generación automática código inventario
            |--------------------------------------------------------------------------
            */

            $codigoInterno =
                $this->generadorCodigoInventarioService
                    ->generar();



            /*
            |--------------------------------------------------------------------------
            | Datos identificación
            |--------------------------------------------------------------------------
            */

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

                        'required',
                        'integer',

                    ],

                ]

            )->validate();




            /*
            |--------------------------------------------------------------------------
            | Registro costo real
            |--------------------------------------------------------------------------
            */

            $this->registroCostoUnidadService
                ->registrar(
                    $unidad,
                    $usuario->id
                );



            /*
            |--------------------------------------------------------------------------
            | Datos equipo formal
            |--------------------------------------------------------------------------
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


                'bateria_porcentaje' =>
                    $unidad->bateria_porcentaje,


                'datos_adicionales' =>
                    [
                        'grado_recibido' =>
                            $unidad->grado_recibido,

                        'grado_final' =>
                            $unidad->grado_final,

                        'resultado_revision' =>
                            $unidad->resultado_revision,

                        'checklist_preparacion' =>
                            $unidad->checklist_tecnico,
                    ],

            ];



            /*
            |--------------------------------------------------------------------------
            | Crear equipo formal
            |--------------------------------------------------------------------------
            */

            $equipo =
                $this->registroEquipoService
                    ->registrar(
                        $usuario->id,
                        $datosEquipo
                    );

                    /*
|--------------------------------------------------------------------------
| Registro histórico de incorporación
|--------------------------------------------------------------------------
*/

IncorporacionUnidadAdquirida::create([

    'unidad_adquirida_id' =>
        $unidad->id,


    'equipo_id' =>
        $equipo->id,


    'usuario_id' =>
        $usuario->id,


    'condicion_fisica_id' =>
        $condicionFisicaId,


    'fecha_incorporacion' =>
        now(),


    'observacion' =>
        $observacion
            ?? $unidad->observacion_revision,

]);

            /*
            |--------------------------------------------------------------------------
            | Vinculación unidad - equipo
            |--------------------------------------------------------------------------
            */

            $unidad->equipo_id =
                $equipo->id;


            $unidad->estado =
                UnidadAdquirida::ESTADO_INCORPORADA;


            $unidad->save();



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