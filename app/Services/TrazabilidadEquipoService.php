<?php

namespace App\Services;

use App\Models\Equipo;
use Illuminate\Support\Collection;

class TrazabilidadEquipoService
{
    public function obtener(Equipo $equipo): Collection
    {
        $equipo->loadMissing([
            'detalleLote.lote.proveedor',

            'incorporacionUnidad.usuario',
            'incorporacionUnidad.unidadAdquirida.registradoPor',
            'incorporacionUnidad.unidadAdquirida.revisadoPor',
            'incorporacionUnidad.unidadAdquirida.detalleLote.lote.proveedor',
            'incorporacionUnidad.unidadAdquirida.revisionesTecnicas.usuario',
            'incorporacionUnidad.unidadAdquirida.intervenciones.registradoPor',
            'incorporacionUnidad.unidadAdquirida.intervenciones.producto',
            'incorporacionUnidad.unidadAdquirida.enviosImportacionUnidades.recibidoPor',
            'incorporacionUnidad.unidadAdquirida.enviosImportacionUnidades.envioImportacion.almacenOrigen',
            'incorporacionUnidad.unidadAdquirida.enviosImportacionUnidades.envioImportacion.almacenDestino',
            'incorporacionUnidad.unidadAdquirida.enviosImportacionUnidades.envioImportacion.preparadoPor',
            'incorporacionUnidad.unidadAdquirida.enviosImportacionUnidades.envioImportacion.despachadoPor',
            'incorporacionUnidad.unidadAdquirida.enviosImportacionUnidades.envioImportacion.recibidoPor',

            'historialEstados.estadoOrigen',
            'historialEstados.estadoDestino',
            'historialEstados.usuario',

            'transferencias.almacenOrigen',
            'transferencias.almacenDestino',
            'transferencias.solicitadoPor',
            'transferencias.despachadoPor',
            'transferencias.recibidoPor',

            'detallesVentas.venta.cliente',
            'detallesVentas.venta.vendedor',

            'detallesVentas.garantia.casosGarantia.recibidoPor',
            'detallesVentas.garantia.casosGarantia.intervenciones.usuario',

            'casosGarantia.recibidoPor',
            'casosGarantia.intervenciones.usuario',
        ]);

        $eventos = collect();

        $incorporacion =
            $equipo->incorporacionUnidad;

        $unidad =
            $incorporacion?->unidadAdquirida;

        /*
        |--------------------------------------------------------------------------
        | Ciclo previo al inventario formal
        |--------------------------------------------------------------------------
        */

        if ($unidad) {
            /*
            |--------------------------------------------------------------------------
            | Compra / procedencia
            |--------------------------------------------------------------------------
            */

            if ($unidad->fecha_compra) {
                $lote =
                    $unidad->detalleLote?->lote;

                $proveedor =
                    $unidad->proveedor_compra
                    ?: $lote?->proveedor?->nombre
                    ?: 'No registrado';

                $detalleCompra = collect([
                    $unidad->codigo_trazabilidad
                        ? 'Trazabilidad: ' . $unidad->codigo_trazabilidad
                        : null,

                    $lote?->codigo
                        ? 'Lote: ' . $lote->codigo
                        : null,

                    'Proveedor: ' . $proveedor,

                    $unidad->referencia_compra
                        ? 'Referencia: ' . $unidad->referencia_compra
                        : null,
                ])
                    ->filter()
                    ->implode(' | ');

                $eventos->push([
                    'fecha' => $unidad->fecha_compra,
                    'tipo' => 'importacion',
                    'orden' => 10,
                    'titulo' => 'Unidad adquirida',
                    'detalle' => $detalleCompra,
                    'usuario' => null,
                    'observacion' => $lote?->observacion,
                ]);
            } elseif ($unidad->detalleLote?->lote) {
                $lote =
                    $unidad->detalleLote->lote;

                $eventos->push([
                    'fecha' => $lote->created_at,
                    'tipo' => 'importacion',
                    'orden' => 10,
                    'titulo' => 'Unidad asociada a lote de importación',
                    'detalle' =>
                        'Lote: '
                        . $lote->codigo
                        . ' | Proveedor: '
                        . (
                            $lote->proveedor?->nombre
                            ?? 'No registrado'
                        )
                        . (
                            $unidad->codigo_trazabilidad
                                ? ' | Trazabilidad: '
                                    . $unidad->codigo_trazabilidad
                                : ''
                        ),
                    'usuario' => null,
                    'observacion' => $lote->observacion,
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Recepción física en Cochabamba
            |--------------------------------------------------------------------------
            */

            if ($unidad->fecha_llegada) {
                $detalleLlegada = collect([
                    $unidad->codigo_trazabilidad
                        ? 'Trazabilidad: ' . $unidad->codigo_trazabilidad
                        : null,

                    $unidad->grado_recibido
                        ? 'Grado recibido: ' . $unidad->grado_recibido
                        : null,
                ])
                    ->filter()
                    ->implode(' | ');

                $eventos->push([
                    'fecha' => $unidad->fecha_llegada,
                    'tipo' => 'recepcion',
                    'orden' => 20,
                    'titulo' => 'Unidad recibida en Cochabamba',
                    'detalle' =>
                        $detalleLlegada !== ''
                            ? $detalleLlegada
                            : 'Recepción física registrada en origen.',
                    'usuario' => $unidad->registradoPor?->name,
                    'observacion' => null,
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Revisiones técnicas
            |--------------------------------------------------------------------------
            */

            foreach ($unidad->revisionesTecnicas as $revision) {
                $detalleRevision = collect([
                    $revision->resultado
                        ? 'Resultado: ' . $revision->resultado
                        : null,

                    $revision->grado_final
                        ? 'Grado final: ' . $revision->grado_final
                        : null,

                    $revision->requiere_servicio
                        ? 'Requiere preparación'
                        : null,
                ])
                    ->filter()
                    ->implode(' | ');

                $eventos->push([
                    'fecha' => $revision->fecha_revision,
                    'tipo' => 'revision',
                    'orden' => 30,
                    'titulo' => 'Revisión técnica de la unidad',
                    'detalle' =>
                        $detalleRevision !== ''
                            ? $detalleRevision
                            : 'Revisión técnica registrada.',
                    'usuario' => $revision->usuario?->name,
                    'observacion' => $revision->observacion,
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Intervenciones de preparación
            |--------------------------------------------------------------------------
            */

            foreach ($unidad->intervenciones as $intervencion) {
                $detalleIntervencion = collect([
                    $intervencion->tipo
                        ? 'Tipo: ' . $intervencion->tipo
                        : null,

                    $intervencion->producto?->nombre
                        ? 'Componente: ' . $intervencion->producto->nombre
                        : null,

                    $intervencion->descripcion
                        ?: null,
                ])
                    ->filter()
                    ->implode(' | ');

                $eventos->push([
                    'fecha' => $intervencion->fecha_inicio,
                    'tipo' => 'preparacion',
                    'orden' => 40,
                    'titulo' => 'Intervención de preparación',
                    'detalle' =>
                        $detalleIntervencion !== ''
                            ? $detalleIntervencion
                            : 'Intervención registrada durante la preparación.',
                    'usuario' => $intervencion->registradoPor?->name,
                    'observacion' =>
                        $intervencion->resultado
                        ?: $intervencion->observacion,
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Lista para envío
            |--------------------------------------------------------------------------
            */

            if ($unidad->fecha_lista_envio) {
                $eventos->push([
                    'fecha' => $unidad->fecha_lista_envio,
                    'tipo' => 'preparacion',
                    'orden' => 50,
                    'titulo' => 'Unidad lista para envío',
                    'detalle' =>
                        $unidad->codigo_trazabilidad
                            ? 'Trazabilidad: '
                                . $unidad->codigo_trazabilidad
                            : 'Preparación técnica finalizada.',
                    'usuario' => $unidad->revisadoPor?->name,
                    'observacion' => $unidad->observacion_revision,
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Traslados de importación
            |--------------------------------------------------------------------------
            */

            foreach (
                $unidad->enviosImportacionUnidades
                as $participacion
            ) {
                $envio =
                    $participacion->envioImportacion;

                if (!$envio) {
                    continue;
                }

                $ruta = collect([
                    $envio->almacenOrigen?->nombre,
                    $envio->almacenDestino?->nombre,
                ])
                    ->filter()
                    ->implode(' → ');

                if ($envio->fecha_preparacion) {
                    $eventos->push([
                        'fecha' => $envio->fecha_preparacion,
                        'tipo' => 'envio',
                        'orden' => 60,
                        'titulo' => 'Envío preparado',
                        'detalle' =>
                            collect([
                                $envio->codigo
                                    ? 'Envío: ' . $envio->codigo
                                    : null,

                                $ruta !== ''
                                    ? 'Ruta: ' . $ruta
                                    : null,
                            ])
                                ->filter()
                                ->implode(' | '),
                        'usuario' => $envio->preparadoPor?->name,
                        'observacion' => $envio->observacion,
                    ]);
                }

                if ($envio->fecha_despacho) {
                    $eventos->push([
                        'fecha' => $envio->fecha_despacho,
                        'tipo' => 'envio',
                        'orden' => 70,
                        'titulo' => 'Unidad despachada',
                        'detalle' =>
                            collect([
                                $envio->codigo
                                    ? 'Envío: ' . $envio->codigo
                                    : null,

                                $ruta !== ''
                                    ? 'Ruta: ' . $ruta
                                    : null,

                                $envio->numero_guia
                                    ? 'Guía: ' . $envio->numero_guia
                                    : null,

                                $envio->transportista
                                    ? 'Transportista: ' . $envio->transportista
                                    : null,
                            ])
                                ->filter()
                                ->implode(' | '),
                        'usuario' => $envio->despachadoPor?->name,
                        'observacion' => $envio->observacion,
                    ]);
                }

                if ($participacion->fecha_recepcion) {
                    $eventos->push([
                        'fecha' => $participacion->fecha_recepcion,
                        'tipo' => 'recepcion',
                        'orden' => 80,
                        'titulo' => 'Unidad recibida en Oruro',
                        'detalle' =>
                            collect([
                                $envio->codigo
                                    ? 'Envío: ' . $envio->codigo
                                    : null,

                                'Estado de recepción: '
                                    . $participacion->estado_recepcion,

                                $participacion->incluye_cargador
                                    ? (
                                        $participacion->cargador_recibido
                                            ? 'Cargador recibido'
                                            : 'Cargador pendiente/no recibido'
                                    )
                                    : null,
                            ])
                                ->filter()
                                ->implode(' | '),
                        'usuario' => $participacion->recibidoPor?->name,
                        'observacion' => $participacion->observacion_recepcion,
                    ]);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Incorporación al inventario formal
            |--------------------------------------------------------------------------
            */

            if ($incorporacion?->fecha_incorporacion) {
                $eventos->push([
                    'fecha' => $incorporacion->fecha_incorporacion,
                    'tipo' => 'registro',
                    'orden' => 90,
                    'titulo' => 'Unidad incorporada al inventario',
                    'detalle' =>
                        collect([
                            $unidad->codigo_trazabilidad
                                ? 'Trazabilidad: '
                                    . $unidad->codigo_trazabilidad
                                : null,

                            $equipo->codigo_interno
                                ? 'Código interno: '
                                    . $equipo->codigo_interno
                                : null,
                        ])
                            ->filter()
                            ->implode(' | '),
                    'usuario' => $incorporacion->usuario?->name,
                    'observacion' => $incorporacion->observacion,
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Registro inicial
        |--------------------------------------------------------------------------
        */

        if (
            !$incorporacion
            && $equipo->fecha_registro
        ) {
            $eventos->push([
                'fecha' => $equipo->fecha_registro,
                'tipo' => 'registro',
                'orden' => 90,
                'titulo' => 'Equipo registrado',
                'detalle' =>
                    'El equipo fue incorporado al inventario de OneShop.',
                'usuario' => null,
                'observacion' => null,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Importación heredada / fallback
        |--------------------------------------------------------------------------
        */

        if (
            !$unidad
            && $equipo->detalleLote?->lote
        ) {
            $lote =
                $equipo->detalleLote->lote;

            $eventos->push([
                'fecha' => $lote->created_at,
                'tipo' => 'importacion',
                'orden' => 10,
                'titulo' => 'Equipo asociado a lote de importación',
                'detalle' =>
                    'Lote: '
                    . $lote->codigo
                    . ' | Proveedor: '
                    . (
                        $lote->proveedor?->nombre
                        ?? 'No registrado'
                    ),
                'usuario' => null,
                'observacion' => $lote->observacion,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Historial de estados
        |--------------------------------------------------------------------------
        */

        foreach ($equipo->historialEstados as $historial) {
            $eventos->push([
                'fecha' => $historial->fecha_cambio,
                'tipo' => 'estado',
                'orden' => 100,
                'titulo' =>
                    $historial->estadoDestino?->nombre
                    ?? 'Cambio de estado',
                'detalle' =>
                    (
                        $historial->estadoOrigen?->nombre
                        ?? ''
                    )
                    .
                    ' → '
                    .
                    (
                        $historial->estadoDestino?->nombre
                        ?? ''
                    ),
                'usuario' => $historial->usuario?->name,
                'observacion' => $historial->observacion,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Transferencias
        |--------------------------------------------------------------------------
        */

        foreach ($equipo->transferencias as $transferencia) {
            if ($transferencia->fecha_solicitud) {
                $eventos->push([
                    'fecha' => $transferencia->fecha_solicitud,
                    'tipo' => 'transferencia',
                    'orden' => 110,
                    'titulo' => 'Transferencia solicitada',
                    'detalle' =>
                        (
                            $transferencia->almacenOrigen?->nombre
                            ?? 'Origen'
                        )
                        .
                        ' → '
                        .
                        (
                            $transferencia->almacenDestino?->nombre
                            ?? 'Destino'
                        ),
                    'usuario' => $transferencia->solicitadoPor?->name,
                    'observacion' => null,
                ]);
            }

            if ($transferencia->fecha_recepcion) {
                $eventos->push([
                    'fecha' => $transferencia->fecha_recepcion,
                    'tipo' => 'transferencia',
                    'orden' => 120,
                    'titulo' => 'Equipo recibido',
                    'detalle' =>
                        'Recepción completada en almacén destino.',
                    'usuario' => $transferencia->recibidoPor?->name,
                    'observacion' => null,
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Ventas
        |--------------------------------------------------------------------------
        */

        foreach ($equipo->detallesVentas as $detalleVenta) {
            $venta =
                $detalleVenta->venta;

            if ($venta) {
                $eventos->push([
                    'fecha' => $venta->created_at,
                    'tipo' => 'venta',
                    'orden' => 130,
                    'titulo' => 'Equipo vendido',
                    'detalle' =>
                        'Cliente: '
                        .
                        (
                            $venta->cliente?->nombre_completo
                            ?? 'No registrado'
                        ),
                    'usuario' => $venta->vendedor?->name,
                    'observacion' => $venta->observacion,
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Garantías asociadas a venta
        |--------------------------------------------------------------------------
        */

        foreach ($equipo->detallesVentas as $detalleVenta) {
            $garantia =
                $detalleVenta->garantia;

            if (!$garantia) {
                continue;
            }

            $eventos->push([
                'fecha' => $garantia->fecha_inicio,
                'tipo' => 'garantia',
                'orden' => 140,
                'titulo' => 'Garantía registrada',
                'detalle' =>
                    'Cobertura vigente hasta '
                    .
                    (
                        $garantia->fecha_fin
                            ? $garantia->fecha_fin->format('d/m/Y')
                            : 'Sin fecha'
                    ),
                'usuario' => null,
                'observacion' => $garantia->condiciones_snapshot,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Casos de garantía
        |--------------------------------------------------------------------------
        */

        foreach ($equipo->casosGarantia as $caso) {
            $eventos->push([
                'fecha' => $caso->fecha_apertura,
                'tipo' => 'garantia',
                'orden' => 150,
                'titulo' => 'Caso de garantía abierto',
                'detalle' =>
                    'Motivo: '
                    .
                    $caso->motivo_cliente
                    .
                    ' | Estado: '
                    .
                    $caso->estado,
                'usuario' => $caso->recibidoPor?->name,
                'observacion' => $caso->observacion,
            ]);

            foreach ($caso->intervenciones as $intervencion) {
                $eventos->push([
                    'fecha' => $intervencion->fecha_intervencion,
                    'tipo' => 'garantia',
                    'orden' => 160,
                    'titulo' => 'Intervención de garantía',
                    'detalle' => $intervencion->descripcion,
                    'usuario' => $intervencion->usuario?->name,
                    'observacion' => $intervencion->resultado,
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Iconos timeline
        |--------------------------------------------------------------------------
        */

        $iconos = [
            'registro' => 'package',
            'importacion' => 'truck',
            'recepcion' => 'package',
            'revision' => 'settings',
            'preparacion' => 'settings',
            'envio' => 'truck',
            'estado' => 'settings',
            'transferencia' => 'truck',
            'venta' => 'chart',
            'garantia' => 'shield',
        ];

        /*
        |--------------------------------------------------------------------------
        | Orden cronológico real
        |--------------------------------------------------------------------------
        |
        | La fecha manda. "orden" solo resuelve empates en el mismo instante.
        |
        */

        $eventos =
            $eventos
                ->filter(
                    fn ($evento) =>
                        $evento['fecha'] !== null
                )
                ->sortBy(function ($evento) {
                    return [
                        $evento['fecha']->timestamp,
                        $evento['orden'] ?? 999,
                    ];
                })
                ->values();

        $ultimoIndice =
            $eventos->count() - 1;

        return $eventos
            ->map(
                function (
                    $evento,
                    $index
                ) use (
                    $iconos,
                    $ultimoIndice
                ) {
                    $evento['icono'] =
                        $iconos[$evento['tipo']]
                        ?? 'package';

                    $evento['activo'] =
                        $index === $ultimoIndice;

                    return $evento;
                }
            )
            ->values();
    }
}
