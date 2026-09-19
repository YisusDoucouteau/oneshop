<?php

namespace App\Http\Controllers;

use App\Models\UnidadAdquirida;
use App\Models\Auditoria;
use App\Models\Lote;
use App\Models\Almacen;
use App\Models\Moneda;
use App\Models\CondicionFisica;
use App\Models\Producto;
use App\Models\RevisionTecnicaUnidadAdquirida;
use Illuminate\Http\JsonResponse;
use App\Services\IncorporacionUnidadAdquiridaService;
use App\Services\UnidadAdquiridaService;
use App\Exceptions\ReglaNegocioException;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UnidadAdquiridaController extends Controller
{
    public function __construct(
    private IncorporacionUnidadAdquiridaService $incorporacionService,
    private UnidadAdquiridaService $unidadAdquiridaService
) {
}
   public function index(Request $request): View
{

    $unidades = UnidadAdquirida::query()

        ->with([
            'producto.marca',
            'detalleLote.lote.proveedor',
            'adquisicionDirecta',
            'almacenActual',
            'equipo',
        ])

        ->when(
            $request->buscar,
            function($query) use ($request){

                $buscar = $request->buscar;


                $query->where(function($q) use ($buscar){

                    $q->where(
                        'codigo_trazabilidad',
                        'like',
                        "%{$buscar}%"
                    )

                    ->orWhere(
                        'serial_fabricante',
                        'like',
                        "%{$buscar}%"
                    )

                    ->orWhere(
                        'nombre_equipo',
                        'like',
                        "%{$buscar}%"
                    );

                });

            }
        )


        ->when(
            $request->estado,
            function($query) use ($request){

                $query->where(
                    'estado',
                    $request->estado
                );

            }
        )


        ->latest()

        ->paginate(15)

        ->withQueryString();



    $estados = [
        UnidadAdquirida::ESTADO_PENDIENTE_LLEGADA,
        UnidadAdquirida::ESTADO_RECIBIDA_ORIGEN,
        UnidadAdquirida::ESTADO_EN_REVISION,
        UnidadAdquirida::ESTADO_EN_PREPARACION,
        UnidadAdquirida::ESTADO_LISTA_ENVIO,
        UnidadAdquirida::ESTADO_ENVIADA,
        UnidadAdquirida::ESTADO_RECIBIDA_ORURO,
        UnidadAdquirida::ESTADO_INCORPORADA,
        UnidadAdquirida::ESTADO_ANULADA,
    ];



    return view(
        'unidades_adquiridas.index',
        compact(
            'unidades',
            'estados'
        )
    );

}



    public function create(): View
    {

        $lotes = Lote::query()
            ->latest()
            ->get();


        $monedas = Moneda::query()
            ->where('activo', true)
            ->orderBy('codigo')
            ->get();



          return view(
            'unidades_adquiridas.create',
            compact(
                'lotes',
                'monedas'
            )
        );

    }





    public function store(
        Request $request
    ): RedirectResponse {


        $datos = $request->validate([


            'detalle_lote_id'=>[
                'required',
                'exists:detalles_lotes,id'
            ],


            'nombre_equipo'=>[
                'required',
                'string',
                'max:150'
            ],


            'modelo_equipo'=>[
                'nullable',
                'string',
                'max:150'
            ],


            'precio_compra'=>[
                'nullable',
                'numeric',
                'min:0'
            ],


            'moneda_id'=>[
                'nullable',
                'exists:monedas,id'
            ],


            'procesador'=>[
                'nullable',
                'string',
                'max:150'
            ],


            'generacion_procesador'=>[
                'nullable',
                'string',
                'max:80'
            ],


            'ram_gb'=>[
                'nullable',
                'integer'
            ],


            'almacenamiento_gb'=>[
                'nullable',
                'integer'
            ],


            'tarjeta_grafica'=>[
                'nullable',
                'string'
            ],


            'sistema_operativo'=>[
                'nullable',
                'string'
            ],


            'observacion_revision'=>[
                'nullable',
                'string'
            ],


        ]);




        $almacenPendiente =
            Almacen::query()
            ->where(
                'codigo',
                'COMPRAS_PENDIENTES'
            )
            ->first();



        if(!$almacenPendiente){

            return back()
                ->withErrors([
                    'almacen'=>
                    'No existe el almacén COMPRAS_PENDIENTES'
                ])
                ->withInput();

        }




        UnidadAdquirida::create([


            'detalle_lote_id'=>
                $datos['detalle_lote_id'],


            'nombre_equipo'=>
                $datos['nombre_equipo'],


            'modelo_equipo'=>
                $datos['modelo_equipo'] ?? null,


            'precio_compra'=>
                $datos['precio_compra'] ?? null,


            'moneda_id'=>
                $datos['moneda_id'] ?? null,


            'almacen_actual_id'=>
                $almacenPendiente->id,


            'estado'=>
                UnidadAdquirida::ESTADO_PENDIENTE_LLEGADA,



            'procesador'=>
                $datos['procesador'] ?? null,


            'generacion_procesador'=>
                $datos['generacion_procesador'] ?? null,


            'ram_gb'=>
                $datos['ram_gb'] ?? null,


            'almacenamiento_gb'=>
                $datos['almacenamiento_gb'] ?? null,


            'tarjeta_grafica'=>
                $datos['tarjeta_grafica'] ?? null,


            'sistema_operativo'=>
                $datos['sistema_operativo'] ?? null,


            'observacion_revision'=>
                $datos['observacion_revision'] ?? null,


            'registrado_por_id'=>
                $request->user()->id,


        ]);




        return redirect()
            ->route(
                'unidades-adquiridas.index'
            )
            ->with(
                'success', 
                'Equipo  registrado correctamente.'
            );

    }
public function show(
    UnidadAdquirida $unidad
): View {

    $unidad->load([

        'producto.marca',
        'producto.categoria',

        'detalleLote.lote.proveedor',
        'detalleLote.producto',
        'adquisicionDirecta',

        'moneda',
        'tipoCambioCompra',

        'almacenActual',
    
        'envioImportacionUnidad.envioImportacion.almacenOrigen',
'envioImportacionUnidad.envioImportacion.almacenDestino',
'envioImportacionUnidad.envioImportacion.preparadoPor',
'envioImportacionUnidad.envioImportacion.despachadoPor',
'envioImportacionUnidad.envioImportacion.recibidoPor',

'envioImportacionUnidad.recibidoPor',
'envioImportacionUnidad.incidencias',
        'intervenciones',
        'intervenciones.producto',
'intervenciones.tipoCosto',
'intervenciones.moneda',
'intervenciones.tipoCambio',
'intervenciones.registradoPor',
'intervenciones.movimientoInventario.tipoMovimiento',
        'revisionesTecnicas.usuario',
        'costosPreparacion.tipoCosto',
        'costosPreparacion.moneda',

        'historialCostos',

        'incorporacionInventario.usuario',
        'incorporacionInventario.condicionFisica',

        'equipo.estadoActual',
        'equipo.almacenActual',
        'equipo.condicionFisica',
    ]);


    $condicionesFisicas =
        CondicionFisica::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();


    $productosComponentes =
        Producto::query()
            ->where('activo', true)
            ->where('es_serializado', false)
            ->orderBy('nombre')
            ->get();


    $monedas =
        Moneda::query()
            ->where('activo', true)
            ->orderBy('codigo')
            ->get();


    $reaperturasPreparacion =
        Auditoria::query()
            ->with('usuario')
            ->where('entidad', 'unidad_adquirida')
            ->where('entidad_id', $unidad->id)
            ->where('accion', 'REABRIR_PREPARACION')
            ->orderBy('fecha_evento')
            ->get();


    return view(
        'unidades_adquiridas.show',
        compact(
            'unidad',
            'condicionesFisicas',
            'productosComponentes',
            'monedas',
            'reaperturasPreparacion'
        )
    );
}
public function incorporar(
    Request $request,
    UnidadAdquirida $unidad
): JsonResponse|RedirectResponse {

    $datos = $request->validate([

        'condicion_fisica_id' => [
            'required',
            'integer',
            'exists:condiciones_fisicas,id',
        ],

        'serial_fabricante' => [
            'nullable',
            'string',
            'max:150',
        ],

        'observacion' => [
            'nullable',
            'string',
            'max:2000',
        ],

    ]);


    $unidadActualizada =
        $this->incorporacionService
            ->incorporar(
                $request->user()->id,
                $unidad->id,
                (int) $datos['condicion_fisica_id'],
                $datos['serial_fabricante'] ?? null,
                $datos['observacion'] ?? null
            );


    $mensaje =
        'La unidad fue incorporada correctamente al inventario.';


    if ($request->expectsJson()) {

        return response()->json([

            'ok' => true,

            'message' => $mensaje,

            'unidad_id' =>
                $unidadActualizada->id,

            'estado' =>
                $unidadActualizada->estado,

            'equipo' => [

                'id' =>
                    $unidadActualizada->equipo?->id,

                'codigo_interno' =>
                    $unidadActualizada
                        ->equipo
                        ?->codigo_interno,

            ],

        ]);
    }


    return redirect()
        ->route(
            'unidades-adquiridas.show',
            $unidadActualizada
        )
        ->with(
            'success',
            $mensaje
        );
}
public function revision(
    Request $request,
    UnidadAdquirida $unidad
): JsonResponse|RedirectResponse {

    $datos = $request->validate([

        'accion' => [
            'nullable',
            'in:BORRADOR,FINALIZAR',
        ],

        'serial_fabricante' => [
            'nullable',
            'string',
            'max:150',
        ],

        'procesador' => [
            'nullable',
            'string',
            'max:150',
        ],

        'generacion_procesador' => [
            'nullable',
            'string',
            'max:80',
        ],

        'ram_gb' => [
            'nullable',
            'integer',
            'min:0',
        ],

        'almacenamiento_gb' => [
            'nullable',
            'integer',
            'min:0',
        ],

        'tipo_almacenamiento' => [
            'nullable',
            'string',
            'max:50',
        ],

        'tarjeta_grafica' => [
            'nullable',
            'string',
            'max:150',
        ],

        'pantalla_pulgadas' => [
            'nullable',
            'numeric',
            'min:0',
        ],

        'resolucion' => [
            'nullable',
            'string',
            'max:50',
        ],

        'sistema_operativo' => [
            'nullable',
            'string',
            'max:100',
        ],

        'grado_final' => [
            'nullable',
            'in:A,B,C',
        ],

        'bateria_porcentaje' => [
            'nullable',
            'integer',
            'min:0',
            'max:100',
        ],

        'checklist_tecnico' => [
            'nullable',
            'array',
        ],

        'checklist_tecnico.*' => [
            'nullable',
            'in:'
                . RevisionTecnicaUnidadAdquirida::CHECK_OK
                . ','
                . RevisionTecnicaUnidadAdquirida::CHECK_FALLA
                . ','
                . RevisionTecnicaUnidadAdquirida::CHECK_NO_APLICA,
        ],

        'enciende' => [
            'nullable',
            'boolean',
        ],

        'tiene_sistema_operativo' => [
            'nullable',
            'boolean',
        ],

        'tiene_cargador' => [
            'nullable',
            'boolean',
        ],

        'requiere_servicio' => [
            'nullable',
            'boolean',
        ],

        'servicio_requerido' => [
            'nullable',
            'string',
            'max:2000',
        ],

        'observacion_revision' => [
            'nullable',
            'string',
            'max:2000',
        ],

    ]);


    try {
        $unidadActualizada =
            $this->unidadAdquiridaService
                ->registrarRevisionPreliminar(
                    $request->user()->id,
                    $unidad->id,
                    $datos,
                    ($datos['accion'] ?? 'FINALIZAR') === 'FINALIZAR'
                );
    } catch (ReglaNegocioException $exception) {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => [
                    'revision' => [
                        $exception->getMessage(),
                    ],
                ],
            ], 422);
        }

        return back()
            ->withErrors([
                'revision' => $exception->getMessage(),
            ])
            ->withInput();
    }


    $mensaje =
        ($datos['accion'] ?? 'FINALIZAR') === 'BORRADOR'
            ? 'El borrador de revisión fue guardado correctamente.'
            : 'La revisión técnica fue finalizada correctamente.';


    if ($request->expectsJson()) {

        return response()->json([

            'ok' => true,

            'message' => $mensaje,

            'unidad' => [
                'id' => $unidadActualizada->id,
                'estado' => $unidadActualizada->estado,
            ],

        ]);
    }


    return redirect()
        ->route(
            'unidades-adquiridas.show',
            $unidadActualizada
        )
        ->with('success', $mensaje);
}

public function reabrirPreparacion(
    Request $request,
    UnidadAdquirida $unidad
): JsonResponse|RedirectResponse {
    $datos = $request->validate([
        'motivo' => [
            'required',
            'string',
            'max:2000',
        ],
    ]);

    try {
        $unidadActualizada =
            $this->unidadAdquiridaService
                ->reabrirPreparacion(
                    $request->user()->id,
                    $unidad->id,
                    $datos['motivo']
                );
    } catch (ReglaNegocioException $exception) {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => [
                    'reapertura' => [
                        $exception->getMessage(),
                    ],
                ],
            ], 422);
        }

        return back()
            ->withErrors([
                'reapertura' => $exception->getMessage(),
            ])
            ->withInput();
    }

    $mensaje = 'La preparación de la unidad fue reabierta correctamente.';

    if ($request->expectsJson()) {
        return response()->json([
            'ok' => true,
            'message' => $mensaje,
            'unidad' => [
                'id' => $unidadActualizada->id,
                'estado' => $unidadActualizada->estado,
            ],
        ]);
    }

    return redirect()
        ->route('unidades-adquiridas.show', $unidadActualizada)
        ->with('success', $mensaje);
}

}