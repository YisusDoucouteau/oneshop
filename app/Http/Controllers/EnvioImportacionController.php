<?php

namespace App\Http\Controllers;

use App\Models\Almacen;
use App\Models\EnvioImportacion;
use App\Models\UnidadAdquirida;
use App\Models\User;
use App\Services\EnvioImportacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EnvioImportacionController extends Controller
{
    public function __construct(
        private readonly EnvioImportacionService $envioService
    ) {
    }


    /*
    |--------------------------------------------------------------------------
    | Listado
    |--------------------------------------------------------------------------
    */

    public function index(Request $request): View
    {
        $envios =
            EnvioImportacion::query()
                ->with([
                    'almacenOrigen',
                    'almacenDestino',
                    'preparadoPor',
                    'despachadoPor',
                    'recibidoPor',
                ])
                ->withCount(
                    'unidadesEnvio'
                )
                ->latest()
                ->paginate(15);


        $origenCochabamba = Almacen::query()
            ->where('codigo', 'COCHABAMBA')
            ->where('activo', true)
            ->first();

        $usuarioAutenticado = $request->user();
        $usuario = $usuarioAutenticado
            ? User::query()->find($usuarioAutenticado->getAuthIdentifier())
            : null;

        $puedeCrearEnvio = $origenCochabamba
            && ($usuario?->puedeOperarEnAlmacen($origenCochabamba->id) ?? false);

        $unidadesDisponibles = $puedeCrearEnvio
            ? UnidadAdquirida::query()
                ->with([
                    'producto.marca',
                    'almacenActual',
                ])
                ->where(
                    'estado',
                    UnidadAdquirida::ESTADO_LISTA_ENVIO
                )
                ->where(
                    'almacen_actual_id',
                    $origenCochabamba->id
                )
                ->whereDoesntHave(
                    'envioImportacionUnidad'
                )
                ->latest()
                ->get()
            : collect();


        return view(
            'envios_importacion.index',
            compact(
                'envios',
                'unidadesDisponibles',
                'puedeCrearEnvio'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Crear borrador
    |--------------------------------------------------------------------------
    */

    public function store(
        Request $request
    ): JsonResponse|RedirectResponse {

        $envio =
            $this->envioService
                ->crearBorrador(
                    $request->user()->id,
                    $request->only([
                        'transportista',
                        'numero_guia',
                        'cantidad_bultos',
                        'cantidad_cargadores',
                        'cantidad_accesorios',
                        'detalle_accesorios',
                        'observacion',
                    ])
                );


        $mensaje =
            'El envío fue creado correctamente.';


        if ($request->expectsJson()) {

            return response()->json([
                'ok' => true,

                'message' =>
                    $mensaje,

                'envio' => [
                    'id' =>
                        $envio->id,

                    'codigo' =>
                        $envio->codigo,

                    'estado' =>
                        $envio->estado,
                ],

                'redirect' =>
                    route(
                        'envios-importacion.show',
                        $envio
                    ),
            ]);
        }


        return redirect()
            ->route(
                'envios-importacion.show',
                $envio
            )
            ->with(
                'success',
                $mensaje
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Detalle
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        EnvioImportacion $envio
    ): View {

        $envio->load([
            'almacenOrigen',
            'almacenDestino',

            'preparadoPor',
            'despachadoPor',
            'recibidoPor',

            'unidadesEnvio.unidadAdquirida.producto.marca',
            'unidadesEnvio.unidadAdquirida.almacenActual',

            'unidadesEnvio.recibidoPor',
            'unidadesEnvio.incidencias',
            'auditorias.usuario',
        ]);


        /*
         * Recargamos el usuario desde BD para que las decisiones de sede
         * siempre usen el valor persistido de almacen_operativo_id. Esto
         * también evita inconsistencias en tests con actingAs() y en sesiones
         * abiertas antes de asignar/cambiar la sede operativa.
         */
        $usuarioAutenticado = $request->user();

        $usuario = $usuarioAutenticado
            ? User::query()->find($usuarioAutenticado->getAuthIdentifier())
            : null;

        $puedeOperarOrigen = $usuario?->puedeOperarEnAlmacen(
            (int) $envio->almacen_origen_id
        ) ?? false;

        $puedeOperarDestino = $usuario?->puedeOperarEnAlmacen(
            (int) $envio->almacen_destino_id
        ) ?? false;

        $unidadesDisponibles = $puedeOperarOrigen
            ? UnidadAdquirida::query()
                ->with([
                    'producto.marca',
                    'almacenActual',
                ])
                ->where(
                    'estado',
                    UnidadAdquirida::ESTADO_LISTA_ENVIO
                )
                ->where(
                    'almacen_actual_id',
                    $envio->almacen_origen_id
                )
                ->whereDoesntHave(
                    'envioImportacionUnidad'
                )
                ->orderBy(
                    'codigo_trazabilidad'
                )
                ->get()
            : collect();


        return view(
            'envios_importacion.show',
            compact(
                'envio',
                'unidadesDisponibles',
                'puedeOperarOrigen',
                'puedeOperarDestino'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Agregar unidad
    |--------------------------------------------------------------------------
    */

    public function agregarUnidad(
        Request $request,
        EnvioImportacion $envio,
        UnidadAdquirida $unidad
    ): JsonResponse|RedirectResponse {

        $detalle =
            $this->envioService
                ->agregarUnidad(
                    $request->user()->id,
                    $envio->id,
                    $unidad->id
                );


        $mensaje =
            'La unidad fue agregada al envío.';


        if ($request->expectsJson()) {

            return response()->json([
                'ok' => true,

                'message' =>
                    $mensaje,

                'detalle' => [
                    'id' =>
                        $detalle->id,

                    'unidad_adquirida_id' =>
                        $detalle->unidad_adquirida_id,

                    'estado_recepcion' =>
                        $detalle->estado_recepcion,
                ],
            ]);
        }


        return redirect()
            ->route(
                'envios-importacion.show',
                $envio
            )
            ->with(
                'success',
                $mensaje
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Quitar unidad
    |--------------------------------------------------------------------------
    */

    public function quitarUnidad(
        Request $request,
        EnvioImportacion $envio,
        UnidadAdquirida $unidad
    ): JsonResponse|RedirectResponse {

        $this->envioService
            ->quitarUnidad(
                $request->user()->id,
                $envio->id,
                $unidad->id
            );


        $mensaje =
            'La unidad fue retirada del envío.';


        if ($request->expectsJson()) {

            return response()->json([
                'ok' => true,
                'message' => $mensaje,
            ]);
        }


        return redirect()
            ->route(
                'envios-importacion.show',
                $envio
            )
            ->with(
                'success',
                $mensaje
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Cargador asociado a una unidad del envío
    |--------------------------------------------------------------------------
    */

    public function actualizarCargadorUnidad(
        Request $request,
        EnvioImportacion $envio,
        UnidadAdquirida $unidad
    ): JsonResponse|RedirectResponse {
        $datos = $request->validate([
            'incluye_cargador' => [
                'required',
                'boolean',
            ],
        ]);

        $detalle = $this->envioService
            ->actualizarCargadorUnidad(
                $request->user()->id,
                $envio->id,
                $unidad->id,
                (bool) $datos['incluye_cargador']
            );

        $mensaje = $detalle->incluye_cargador
            ? 'La unidad viajará con cargador.'
            : 'La unidad viajará sin cargador.';

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => $mensaje,
                'incluye_cargador' => $detalle->incluye_cargador,
            ]);
        }

        return redirect()
            ->route('envios-importacion.show', $envio)
            ->with('success', $mensaje);
    }


    /*
    |--------------------------------------------------------------------------
    | Preparar envío
    |--------------------------------------------------------------------------
    */

    public function preparar(
        Request $request,
        EnvioImportacion $envio
    ): JsonResponse|RedirectResponse {

        $envioActualizado =
            $this->envioService
                ->marcarPreparado(
                    $request->user()->id,
                    $envio->id
                );


        $mensaje =
            'El envío fue marcado como preparado.';


        if ($request->expectsJson()) {

            return response()->json([
                'ok' => true,

                'message' =>
                    $mensaje,

                'estado' =>
                    $envioActualizado->estado,
            ]);
        }


        return redirect()
            ->route(
                'envios-importacion.show',
                $envio
            )
            ->with(
                'success',
                $mensaje
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Reabrir envío preparado
    |--------------------------------------------------------------------------
    */

    public function reabrir(
        Request $request,
        EnvioImportacion $envio
    ): JsonResponse|RedirectResponse {

        $datos = $request->validate([
            'motivo' => [
                'required',
                'string',
                'max:1000',
            ],
        ]);

        $envioActualizado =
            $this->envioService
                ->reabrirPreparado(
                    $request->user()->id,
                    $envio->id,
                    $datos['motivo']
                );

        $mensaje =
            'El envío volvió a borrador para realizar correcciones.';

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => $mensaje,
                'estado' => $envioActualizado->estado,
            ]);
        }

        return redirect()
            ->route('envios-importacion.show', $envio)
            ->with('success', $mensaje);
    }


    /*
    |--------------------------------------------------------------------------
    | Cancelar envío antes del despacho
    |--------------------------------------------------------------------------
    */

    public function cancelar(
        Request $request,
        EnvioImportacion $envio
    ): JsonResponse|RedirectResponse {

        $datos = $request->validate([
            'motivo' => [
                'required',
                'string',
                'max:1000',
            ],
        ]);

        $envioActualizado =
            $this->envioService
                ->cancelarEnvio(
                    $request->user()->id,
                    $envio->id,
                    $datos['motivo']
                );

        $mensaje =
            'El envío fue cancelado y sus unidades quedaron disponibles nuevamente.';

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => $mensaje,
                'estado' => $envioActualizado->estado,
            ]);
        }

        return redirect()
            ->route('envios-importacion.show', $envio)
            ->with('success', $mensaje);
    }


    /*
    |--------------------------------------------------------------------------
    | Despachar Cochabamba -> Oruro
    |--------------------------------------------------------------------------
    */

    public function despachar(
        Request $request,
        EnvioImportacion $envio
    ): JsonResponse|RedirectResponse {

        $envioActualizado =
            $this->envioService
                ->marcarDespachado(
                    $request->user()->id,
                    $envio->id,
                    $request->only([
                        'transportista',
                        'numero_guia',
                    ])
                );


        $mensaje =
            'El envío fue despachado hacia Oruro.';


        if ($request->expectsJson()) {

            return response()->json([
                'ok' => true,

                'message' =>
                    $mensaje,

                'estado' =>
                    $envioActualizado->estado,
            ]);
        }


        return redirect()
            ->route(
                'envios-importacion.show',
                $envio
            )
            ->with(
                'success',
                $mensaje
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Recibir unidad en Oruro
    |--------------------------------------------------------------------------
    */

    public function recibirUnidad(
        Request $request,
        EnvioImportacion $envio,
        UnidadAdquirida $unidad
    ): JsonResponse|RedirectResponse {

        $detalle =
            $this->envioService
                ->recibirUnidad(
                    $request->user()->id,
                    $envio->id,
                    $unidad->id,
                    $request->input(
                        'observacion'
                    )
                );


        $mensaje =
            'La unidad fue recibida físicamente en Oruro.';


        if ($request->expectsJson()) {

            return response()->json([
                'ok' => true,

                'message' =>
                    $mensaje,

                'detalle' => [
                    'id' =>
                        $detalle->id,

                    'estado_recepcion' =>
                        $detalle->estado_recepcion,

                    'fecha_recepcion' =>
                        optional(
                            $detalle->fecha_recepcion
                        )?->toISOString(),
                ],
            ]);
        }


        return redirect()
            ->route(
                'envios-importacion.show',
                $envio
            )
            ->with(
                'success',
                $mensaje
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Marcar faltante
    |--------------------------------------------------------------------------
    */

    public function marcarFaltante(
        Request $request,
        EnvioImportacion $envio,
        UnidadAdquirida $unidad
    ): JsonResponse|RedirectResponse {

        $datos =
            $request->validate([
                'observacion' => [
                    'required',
                    'string',
                    'max:1000',
                ],
            ]);


        $detalle =
            $this->envioService
                ->marcarUnidadFaltante(
                    $request->user()->id,
                    $envio->id,
                    $unidad->id,
                    $datos['observacion']
                );


        $mensaje =
            'La unidad fue registrada como faltante.';


        if ($request->expectsJson()) {

            return response()->json([
                'ok' => true,

                'message' =>
                    $mensaje,

                'estado_recepcion' =>
                    $detalle->estado_recepcion,
            ]);
        }


        return redirect()
            ->route(
                'envios-importacion.show',
                $envio
            )
            ->with(
                'success',
                $mensaje
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Incidencia en recepción
    |--------------------------------------------------------------------------
    */

    public function registrarIncidencia(
        Request $request,
        EnvioImportacion $envio,
        UnidadAdquirida $unidad
    ): JsonResponse|RedirectResponse {

        $datos =
            $request->validate([
                'observacion' => [
                    'required',
                    'string',
                    'max:1000',
                ],
            ]);


        $detalle =
            $this->envioService
                ->registrarIncidenciaRecepcion(
                    $request->user()->id,
                    $envio->id,
                    $unidad->id,
                    $datos['observacion']
                );


        $mensaje =
            'La incidencia de recepción fue registrada.';


        if ($request->expectsJson()) {

            return response()->json([
                'ok' => true,

                'message' =>
                    $mensaje,

                'estado_recepcion' =>
                    $detalle->estado_recepcion,
            ]);
        }


        return redirect()
            ->route(
                'envios-importacion.show',
                $envio
            )
            ->with(
                'success',
                $mensaje
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Cerrar recepción
    |--------------------------------------------------------------------------
    */

    public function cerrarRecepcion(
        Request $request,
        EnvioImportacion $envio
    ): JsonResponse|RedirectResponse {

        $envioActualizado =
            $this->envioService
                ->cerrarRecepcion(
                    $request->user()->id,
                    $envio->id
                );


        $mensaje =
            $envioActualizado->estado ===
            EnvioImportacion::ESTADO_RECIBIDO

                ? 'La recepción del envío fue cerrada completamente.'

                : 'La recepción fue cerrada de forma parcial.';


        if ($request->expectsJson()) {

            return response()->json([
                'ok' => true,

                'message' =>
                    $mensaje,

                'estado' =>
                    $envioActualizado->estado,
            ]);
        }


        return redirect()
            ->route(
                'envios-importacion.show',
                $envio
            )
            ->with(
                'success',
                $mensaje
            );
    }
}