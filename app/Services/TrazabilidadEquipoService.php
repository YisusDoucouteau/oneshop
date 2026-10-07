<?php

namespace App\Services;

use App\Models\CambioEquipo;
use App\Models\Equipo;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

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

            'detallesReservas.reserva.cliente',
            'detallesReservas.reserva.registradoPor',
            'detallesReservas.reserva.prorrogas.autorizadoPor',

            'detallesVentas.venta.cliente',
            'detallesVentas.venta.vendedor',
            'detallesVentas.venta.anuladoPor',
            'detallesVentas.venta.pagos.metodoPago',
            'detallesVentas.venta.pagos.registradoPor',
            'detallesVentas.venta.pagos.verificadoPor',

            'precios.aprobadoPor',

            'detallesVentas.garantia.casosGarantia.recibidoPor',
            'detallesVentas.garantia.casosGarantia.intervenciones.usuario',

            'casosGarantia.recibidoPor',
            'casosGarantia.cerradoPor',
            'casosGarantia.intervenciones.usuario',
            'casosGarantia.cambioEquipo',
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
                        ? 'Tipo: '
                            . $this->humanizarCodigo(
                                $intervencion->tipo
                            )
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
                                    . $this->humanizarCodigo(
                                        $participacion->estado_recepcion
                                    ),

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
        | Historial comercial de precios
        |--------------------------------------------------------------------------
        */

        $precios =
            $equipo->precios
                ->sortBy(
                    fn ($precio) =>
                        $precio->vigente_desde
                        ?? $precio->created_at
                )
                ->values();

        foreach ($precios as $indice => $precio) {
            $fechaPrecio =
                $precio->vigente_desde
                ?? $precio->created_at;

            if (!$fechaPrecio) {
                continue;
            }

            $eventos->push([
                'fecha' => $fechaPrecio,
                'tipo' => 'precio',
                'orden' => 122,
                'titulo' =>
                    $indice === 0
                        ? 'Precio comercial definido'
                        : 'Precio comercial actualizado',
                'detalle' =>
                    collect([
                        $precio->precio_publico !== null
                            ? 'Precio publicado: Bs '
                                . $this->formatearMonto(
                                    $precio->precio_publico
                                )
                            : null,

                        $precio->vigente
                            ? 'Vigencia: Actual'
                            : 'Vigencia: Histórica',
                    ])
                        ->filter()
                        ->implode(' | '),
                'usuario' => $precio->aprobadoPor?->name,
                'observacion' => $precio->observacion,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Reservas
        |--------------------------------------------------------------------------
        */

        foreach ($equipo->detallesReservas as $detalleReserva) {
            $reserva =
                $detalleReserva->reserva;

            if (!$reserva) {
                continue;
            }

            if ($reserva->fecha_reserva) {
                $eventos->push([
                    'fecha' => $reserva->fecha_reserva,
                    'tipo' => 'reserva',
                    'orden' => 125,
                    'titulo' => 'Equipo reservado',
                    'detalle' =>
                        collect([
                            $reserva->numero
                                ? 'Reserva: ' . $reserva->numero
                                : null,

                            $reserva->cliente?->nombre_completo
                                ? 'Cliente: ' . $reserva->cliente->nombre_completo
                                : null,

                            $detalleReserva->precio_acordado !== null
                                ? 'Precio acordado: Bs '
                                    . number_format(
                                        (float) $detalleReserva->precio_acordado,
                                        2,
                                        '.',
                                        ''
                                    )
                                : null,
                        ])
                            ->filter()
                            ->implode(' | '),
                    'usuario' => $reserva->registradoPor?->name,
                    'observacion' =>
                        $detalleReserva->observacion
                        ?: $reserva->observacion,
                ]);
            }

            foreach ($reserva->prorrogas as $prorroga) {
                if (!$prorroga->created_at) {
                    continue;
                }

                $eventos->push([
                    'fecha' => $prorroga->created_at,
                    'tipo' => 'reserva',
                    'orden' => 126,
                    'titulo' => 'Reserva prorrogada',
                    'detalle' =>
                        collect([
                            $reserva->numero
                                ? 'Reserva: ' . $reserva->numero
                                : null,

                            $prorroga->fecha_expiracion_anterior
                                ? 'Vencimiento anterior: '
                                    . $prorroga->fecha_expiracion_anterior->format('d/m/Y H:i')
                                : null,

                            $prorroga->nueva_fecha_expiracion
                                ? 'Nuevo vencimiento: '
                                    . $prorroga->nueva_fecha_expiracion->format('d/m/Y H:i')
                                : null,
                        ])
                            ->filter()
                            ->implode(' | '),
                    'usuario' => $prorroga->autorizadoPor?->name,
                    'observacion' => $prorroga->motivo,
                ]);
            }

            if ($reserva->fecha_cierre) {
                $tituloCierre = match ($reserva->estado) {
                    'LIBERADA' => 'Reserva liberada',
                    'CONVERTIDA' => 'Reserva convertida en venta',
                    default => 'Reserva cerrada',
                };

                $eventos->push([
                    'fecha' => $reserva->fecha_cierre,
                    'tipo' => 'reserva',
                    'orden' => 127,
                    'titulo' => $tituloCierre,
                    'detalle' =>
                        collect([
                            $reserva->numero
                                ? 'Reserva: ' . $reserva->numero
                                : null,

                            $reserva->estado
                                ? 'Estado final: '
                                    . $this->humanizarCodigo(
                                        $reserva->estado
                                    )
                                : null,
                        ])
                            ->filter()
                            ->implode(' | '),
                    'usuario' => null,
                    'observacion' => $reserva->observacion,
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
                    'fecha' =>
                        $venta->fecha_venta
                        ?? $venta->created_at,
                    'tipo' => 'venta',
                    'orden' => 130,
                    'titulo' => 'Equipo vendido',
                    'detalle' =>
                        collect([
                            $venta->numero
                                ? 'Venta: ' . $venta->numero
                                : null,

                            'Cliente: '
                                . (
                                    $venta->cliente?->nombre_completo
                                    ?? 'No registrado'
                                ),
                        ])
                            ->filter()
                            ->implode(' | '),
                    'usuario' => $venta->vendedor?->name,
                    'observacion' => $venta->observacion,
                ]);

                foreach ($venta->pagos as $pago) {
                    $requiereVerificacion =
                        (bool) (
                            $pago
                                ->metodoPago
                                ?->requiere_verificacion
                            ?? false
                        );

                    $estadoInicial =
                        $requiereVerificacion
                            ? 'PENDIENTE'
                            : 'VERIFICADO';

                    $eventos->push([
                        'fecha' =>
                            $pago->fecha_pago
                            ?? $pago->created_at,

                        'tipo' => 'pago',
                        'orden' => 132,
                        'titulo' => 'Pago de venta registrado',
                        'detalle' =>
                            collect([
                                $venta->numero
                                    ? 'Venta: '
                                        . $venta->numero
                                    : null,

                                'Monto: Bs '
                                    . $this->formatearMonto(
                                        $pago->monto
                                    ),

                                'Método: '
                                    . (
                                        $pago
                                            ->metodoPago
                                            ?->nombre
                                        ?? 'No registrado'
                                    ),

                                'Estado inicial: '
                                    . $this->humanizarEstadoPago(
                                        $estadoInicial
                                    ),

                                $pago->referencia
                                    ? 'Referencia: '
                                        . $pago->referencia
                                    : null,
                            ])
                                ->filter()
                                ->implode(' | '),
                        'usuario' =>
                            $pago
                                ->registradoPor
                                ?->name,
                        'observacion' => $pago->observacion,
                    ]);

                    if (
                        $requiereVerificacion
                        && $pago->fecha_verificacion
                        && in_array(
                            $pago->estado,
                            [
                                'VERIFICADO',
                                'RECHAZADO',
                            ],
                            true
                        )
                    ) {
                        $rechazado =
                            $pago->estado
                            ===
                            'RECHAZADO';

                        $eventos->push([
                            'fecha' =>
                                $pago->fecha_verificacion,

                            'tipo' => 'pago',
                            'orden' => 133,

                            'titulo' =>
                                $rechazado
                                    ? 'Pago de venta rechazado'
                                    : 'Pago de venta verificado',

                            'detalle' =>
                                collect([
                                    $venta->numero
                                        ? 'Venta: '
                                            . $venta->numero
                                        : null,

                                    'Monto: Bs '
                                        . $this->formatearMonto(
                                            $pago->monto
                                        ),

                                    'Estado: '
                                        . $this->humanizarEstadoPago(
                                            $pago->estado
                                        ),

                                    $pago->referencia
                                        ? 'Referencia: '
                                            . $pago->referencia
                                        : null,

                                    $rechazado
                                    && $pago->motivo_rechazo
                                        ? 'Motivo: '
                                            . $pago->motivo_rechazo
                                        : null,
                                ])
                                    ->filter()
                                    ->implode(' | '),
                            'usuario' =>
                                $pago
                                    ->verificadoPor
                                    ?->name,
                            'observacion' => null,
                        ]);
                    }
                }

                if ($venta->fecha_anulacion) {
                    $eventos->push([
                        'fecha' => $venta->fecha_anulacion,
                        'tipo' => 'venta',
                        'orden' => 135,
                        'titulo' => 'Venta anulada',
                        'detalle' =>
                            collect([
                                $venta->numero
                                    ? 'Venta: ' . $venta->numero
                                    : null,

                                $venta->motivo_anulacion
                                    ? 'Motivo: ' . $venta->motivo_anulacion
                                    : null,
                            ])
                                ->filter()
                                ->implode(' | '),
                        'usuario' => $venta->anuladoPor?->name,
                        'observacion' => $venta->observacion,
                    ]);
                }
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
                    $this->humanizarCodigo(
                        $caso->estado
                    ),
                'usuario' => $caso->recibidoPor?->name,
                'observacion' => $caso->observacion,
            ]);

            if ($caso->diagnostico_final) {
                $primeraIntervencion =
                    $caso->intervenciones
                        ->sortBy('fecha_intervencion')
                        ->first();

                $fechaDiagnostico =
                    $primeraIntervencion?->fecha_intervencion
                    ?? $caso->cambioEquipo?->fecha_cambio
                    ?? $caso->fecha_cierre
                    ?? $caso->updated_at
                    ?? $caso->fecha_apertura;

                $eventos->push([
                    'fecha' => $fechaDiagnostico,
                    'tipo' => 'garantia',
                    'orden' => 155,
                    'titulo' => 'Diagnóstico de garantía registrado',
                    'detalle' => $caso->diagnostico_final,
                    'usuario' => null,
                    'observacion' => null,
                ]);
            }

            foreach ($caso->intervenciones as $intervencion) {
                $eventos->push([
                    'fecha' => $intervencion->fecha_intervencion,
                    'tipo' => 'garantia',
                    'orden' => 160,
                    'titulo' => 'Intervención de garantía',
                    'detalle' =>
                        collect([
                            $intervencion->tipo_intervencion
                                ? 'Tipo: '
                                    . $this->humanizarCodigo(
                                        $intervencion->tipo_intervencion
                                    )
                                : null,

                            $intervencion->descripcion,
                        ])
                            ->filter()
                            ->implode(' | '),
                    'usuario' => $intervencion->usuario?->name,
                    'observacion' => $intervencion->resultado,
                ]);
            }

            if ($caso->fecha_cierre) {
                $eventos->push([
                    'fecha' => $caso->fecha_cierre,
                    'tipo' => 'garantia',
                    'orden' => 180,
                    'titulo' => 'Caso de garantía cerrado',
                    'detalle' =>
                        collect([
                            $caso->numero
                                ? 'Caso: ' . $caso->numero
                                : null,

                            $caso->resolucion
                                ? 'Resolución: '
                                    . $this->humanizarValor(
                                        $caso->resolucion
                                    )
                                : null,

                            'Estado final: Cerrado',
                        ])
                            ->filter()
                            ->implode(' | '),
                    'usuario' => $caso->cerradoPor?->name,
                    'observacion' => $caso->observacion,
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Cambios de equipo por garantía
        |--------------------------------------------------------------------------
        |
        | Se consulta por ambos extremos del cambio porque el equipo entrante
        | no es el equipo_afectado_id original del CasoGarantia.
        |
        */

        $cambiosGarantia =
            CambioEquipo::query()
                ->with([
                    'casoGarantia',
                    'autorizadoPor',
                    'equipoSaliente.producto',
                    'equipoEntrante.producto',
                    'movimientosAjuste.metodoPago',
                    'movimientosAjuste.registradoPor',
                    'movimientosAjuste.verificadoPor',
                ])
                ->where(function ($query) use ($equipo) {
                    $query
                        ->where(
                            'equipo_saliente_id',
                            $equipo->id
                        )
                        ->orWhere(
                            'equipo_entrante_id',
                            $equipo->id
                        );
                })
                ->orderBy('fecha_cambio')
                ->get();

        foreach ($cambiosGarantia as $cambio) {
            $esSaliente =
                (int) $cambio->equipo_saliente_id
                ===
                (int) $equipo->id;

            $equipoRelacionado =
                $esSaliente
                    ? $cambio->equipoEntrante
                    : $cambio->equipoSaliente;

            $titulo =
                $esSaliente
                    ? 'Equipo sustituido por garantía'
                    : 'Equipo entregado como reemplazo';

            $etiquetaRelacionado =
                $esSaliente
                    ? 'Reemplazo'
                    : 'Equipo sustituido';

            $detalleRelacionado =
                $equipoRelacionado
                    ? collect([
                        $equipoRelacionado->codigo_interno,
                        $equipoRelacionado->producto?->nombre,
                        $equipoRelacionado->producto?->modelo,
                    ])
                        ->filter()
                        ->implode(' · ')
                    : 'No registrado';

            $eventos->push([
                'fecha' => $cambio->fecha_cambio,
                'tipo' => 'garantia',
                'orden' => 165,
                'titulo' => $titulo,
                'detalle' =>
                    collect([
                        $cambio->casoGarantia?->numero
                            ? 'Caso: '
                                . $cambio->casoGarantia->numero
                            : null,

                        $etiquetaRelacionado
                            . ': '
                            . $detalleRelacionado,

                        $cambio->motivo
                            ? 'Motivo: ' . $cambio->motivo
                            : null,
                    ])
                        ->filter()
                        ->implode(' | '),
                'usuario' => $cambio->autorizadoPor?->name,
                'observacion' => $cambio->observacion,
            ]);

            foreach ($cambio->movimientosAjuste as $movimiento) {
                $esDevolucion =
                    $movimiento->tipo_movimiento
                    ===
                    'DEVOLUCION';
                $requiereVerificacion =
                    (bool) (
                        $movimiento
                            ->metodoPago
                            ?->requiere_verificacion
                        ?? false
                    );

                $estadoInicial =
                    $requiereVerificacion
                        ? 'PENDIENTE'
                        : 'VERIFICADO';

                $eventos->push([
                    'fecha' =>
                        $movimiento->fecha_movimiento,

                    'tipo' =>
                        'garantia',

                    'orden' =>
                        170,

                    'titulo' =>
                        $esDevolucion
                            ? 'Devolución de ajuste de garantía registrada'
                            : 'Cobro de ajuste de garantía registrado',

                    'detalle' =>
                        collect([
                            $cambio->casoGarantia?->numero
                                ? 'Caso: '
                                    . $cambio->casoGarantia->numero
                                : null,

                            'Monto: '
                                . (
                                    $cambio->moneda_ajuste
                                    ?? 'BOB'
                                )
                                . ' '
                                . $this->formatearMonto(
                                    $movimiento->monto
                                ),

                            'Método: '
                                . (
                                    $movimiento
                                        ->metodoPago
                                        ?->nombre
                                    ?? 'No registrado'
                                ),

                            'Estado inicial: '
                                . $this->humanizarEstadoPago(
                                    $estadoInicial
                                ),

                            $movimiento->referencia
                                ? 'Referencia: '
                                    . $movimiento->referencia
                                : null,
                        ])
                            ->filter()
                            ->implode(' | '),

                    'usuario' =>
                        $movimiento
                            ->registradoPor
                            ?->name,

                    'observacion' =>
                        $movimiento->observacion,
                ]);

                if (
                    $requiereVerificacion
                    && $movimiento->fecha_verificacion
                    && in_array(
                        $movimiento->estado,
                        [
                            'VERIFICADO',
                            'RECHAZADO',
                        ],
                        true
                    )
                ) {
                    $esRechazado =
                        $movimiento->estado
                        ===
                        'RECHAZADO';

                    $eventos->push([
                        'fecha' =>
                            $movimiento->fecha_verificacion,

                        'tipo' =>
                            'garantia',

                        'orden' =>
                            175,

                        'titulo' =>
                            match (true) {
                                $esRechazado
                                    && $esDevolucion =>
                                        'Devolución de ajuste rechazada',

                                $esRechazado =>
                                    'Cobro de ajuste rechazado',

                                $esDevolucion =>
                                    'Devolución de ajuste verificada',

                                default =>
                                    'Cobro de ajuste verificado',
                            },

                        'detalle' =>
                            collect([
                                $cambio->casoGarantia?->numero
                                    ? 'Caso: '
                                        . $cambio->casoGarantia->numero
                                    : null,

                                'Monto: '
                                    . (
                                        $cambio->moneda_ajuste
                                        ?? 'BOB'
                                    )
                                    . ' '
                                    . $this->formatearMonto(
                                        $movimiento->monto
                                    ),

                                'Estado: '
                                    . $this->humanizarEstadoPago(
                                        $movimiento->estado
                                    ),

                                $movimiento->referencia
                                    ? 'Referencia: '
                                        . $movimiento->referencia
                                    : null,

                                $esRechazado
                                    && $movimiento->motivo_rechazo
                                        ? 'Motivo: '
                                            . $movimiento->motivo_rechazo
                                        : null,
                            ])
                                ->filter()
                                ->implode(' | '),

                        'usuario' =>
                            $movimiento
                                ->verificadoPor
                                ?->name,

                        'observacion' =>
                            null,
                    ]);
                }
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
            'reserva' => 'package',
            'precio' => 'chart',
            'venta' => 'chart',
            'pago' => 'chart',
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

    private function humanizarEstadoPago(
        ?string $estado
    ): string {
        return match ($estado) {
            'PENDIENTE' =>
                'Pendiente de verificación',

            'VERIFICADO' =>
                'Verificado',

            'RECHAZADO' =>
                'Rechazado',

            default =>
                $this->humanizarCodigo(
                    $estado
                ),
        };
    }

    private function humanizarValor(
        ?string $valor
    ): string {
        if (
            $valor === null
            || trim($valor) === ''
        ) {
            return 'No registrado';
        }

        $valor = trim($valor);

        if (
            preg_match(
                '/^[A-Z0-9_]+$/',
                $valor
            ) === 1
        ) {
            return $this->humanizarCodigo(
                $valor
            );
        }

        return $valor;
    }

    private function humanizarCodigo(
        ?string $valor
    ): string {
        if (
            $valor === null
            || trim($valor) === ''
        ) {
            return 'No registrado';
        }

        return match ($valor) {
            'EN_PROCESO' =>
                'En proceso',

            'COBRO_CLIENTE' =>
                'Cobro al cliente',

            'SALDO_FAVOR_CLIENTE' =>
                'Saldo a favor del cliente',

            'SIN_DIFERENCIA' =>
                'Sin diferencia',

            'CONVERTIDA' =>
                'Convertida en venta',

            default =>
                Str::of($valor)
                    ->replace('_', ' ')
                    ->lower()
                    ->ucfirst()
                    ->toString(),
        };
    }

    private function formatearMonto(
        mixed $monto
    ): string {
        return number_format(
            (float) $monto,
            2,
            ',',
            '.'
        );
    }
}
