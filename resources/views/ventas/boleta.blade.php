<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">

    <title>
        Nota de venta {{ $venta->numero }}
    </title>

    <style>
        @page {
            margin: 6mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #202020;
        }

        .boleta {
            border: 1.5px solid #222;
            border-radius: 8px;
            padding: 10px 12px 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .encabezado td {
            vertical-align: top;
        }

        .logo {
            font-size: 29px;
            line-height: 1;
            font-weight: 900;
            letter-spacing: -2px;
        }

        .contacto {
            margin-top: 5px;
            line-height: 1.45;
            color: #444;
        }

        .titulo {
            text-align: center;
            font-size: 18px;
            font-weight: 900;
            letter-spacing: -.5px;
        }

        .fecha {
            margin-top: 5px;
            border: 2px solid #555;
        }

        .fecha th {
            padding: 2px 4px;
            background: #444;
            color: #fff;
            font-size: 7px;
            text-align: center;
        }

        .fecha td {
            border-right: 1px solid #777;
            padding: 5px 4px;
            text-align: center;
            font-weight: bold;
        }

        .fecha td:last-child {
            border-right: 0;
        }

        .datos {
            margin-top: 9px;
        }

        .linea {
            border-bottom: 1px dotted #555;
            min-height: 16px;
            padding: 2px 3px;
        }

        .items {
            margin-top: 8px;
            border: 1px solid #777;
        }

        .items th {
            padding: 4px 5px;
            border-right: 1px solid #777;
            border-bottom: 1px solid #777;
            background: #efefef;
            font-size: 8px;
            text-align: left;
        }

        .items td {
            padding: 5px;
            border-right: 1px solid #aaa;
            border-bottom: 1px solid #ccc;
            vertical-align: top;
        }

        .items th:last-child,
        .items td:last-child {
            border-right: 0;
        }

        .items tr:last-child td {
            border-bottom: 0;
        }

        .derecha {
            text-align: right !important;
        }

        .total {
            margin-top: 8px;
            font-size: 11px;
        }

        .total strong {
            font-size: 14px;
        }

        .literal {
            margin-top: 5px;
            border-bottom: 1px dotted #555;
            padding: 3px 2px 5px;
            line-height: 1.35;
        }

        .garantia {
            margin-top: 5px;
            font-size: 7.5px;
            color: #555;
        }

        .firmas {
            margin-top: 13px;
        }

        .firma {
            width: 42%;
            text-align: center;
            vertical-align: bottom;
        }

        .firma-linea {
            border-top: 1px dotted #555;
            padding-top: 3px;
            font-weight: bold;
        }

        .documento {
            margin-top: 4px;
            text-align: right;
            font-size: 7px;
            color: #666;
        }
    </style>
</head>

<body>
@php
    $nombreCliente =
        $venta->cliente_nombre_snapshot
        ?? $venta->cliente?->nombre_completo
        ?? 'Sin nombre';

    $telefonoCliente =
        $venta->cliente_telefono_snapshot
        ?? $venta->cliente?->telefono
        ?? '';

    $fecha =
        $venta->fecha_venta
        ?? $venta->created_at;

    $lugar =
        $datosEmpresa['lugar']
        ?? 'ORURO';
@endphp

<div class="boleta">
    <table class="encabezado">
        <tr>
            <td style="width: 37%;">
                @php
                    $logoPath =
                        public_path(
                            'images/oneshop-logo.png'
                        );
                @endphp

                @if(file_exists($logoPath))
                    <img
                        src="{{ $logoPath }}"
                        alt="OneShop"
                        style="width: 180px; max-height: 48px;"
                    >
                @else
                    <div class="logo">
                        ONESHOP
                    </div>
                @endif

                <div class="contacto">
                    @foreach(($datosEmpresa['telefonos'] ?? []) as $telefono)
                        <div>
                            {{ $telefono }}
                        </div>
                    @endforeach

                    <div>
                        {{ $datosEmpresa['correo'] ?? '' }}
                    </div>

                    <div>
                        {{ $datosEmpresa['red_social'] ?? '' }}
                    </div>
                </div>
            </td>

            <td style="width: 24%;"></td>

            <td style="width: 39%;">
                <div class="titulo">
                    NOTA DE VENTA Y GARANTÍA
                </div>

                <table class="fecha">
                    <tr>
                        <th>LUGAR</th>
                        <th>DÍA</th>
                        <th>MES</th>
                        <th>AÑO</th>
                    </tr>

                    <tr>
                        <td>{{ $lugar }}</td>
                        <td>{{ $fecha?->format('d') }}</td>
                        <td>{{ $fecha?->format('m') }}</td>
                        <td>{{ $fecha?->format('Y') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="datos">
        <tr>
            <td style="width: 12%; font-size: 11px;">
                Señor(es):
            </td>

            <td class="linea" style="width: 56%;">
                <strong>{{ $nombreCliente }}</strong>
            </td>

            <td style="width: 8%; padding-left: 8px; font-size: 11px;">
                Telf.:
            </td>

            <td class="linea" style="width: 24%;">
                {{ $telefonoCliente }}
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 23%;">Marca</th>
                <th style="width: 25%;">Modelo</th>
                <th style="width: 20%;">Serial</th>
                <th style="width: 14%;">Condición</th>
                <th class="derecha" style="width: 18%;">Precio</th>
            </tr>
        </thead>

        <tbody>
            @foreach($venta->detalles as $detalle)
                @php
                    $marca =
                        $detalle->marca_snapshot
                        ?? $detalle->producto?->marca?->nombre
                        ?? $detalle->equipo?->producto?->marca?->nombre
                        ?? '-';

                    $modelo =
                        $detalle->modelo_snapshot
                        ?? $detalle->producto?->modelo
                        ?? $detalle->equipo?->producto?->modelo
                        ?? '-';

                    $codigo =
                        $detalle->codigo_interno_snapshot
                        ?? $detalle->equipo?->codigo_interno
                        ?? '-';

                    $codigoVisible =
                        $codigo === '-'
                            ? '-'
                            : '#'
                                . ltrim(
                                    (string) $codigo,
                                    '#'
                                );

                    $condicion =
                        $detalle->condicion_venta_snapshot
                        ?? 'USADO';
                @endphp

                <tr>
                    <td>{{ $marca }}</td>
                    <td>{{ $modelo }}</td>
                    <td><strong>{{ $codigoVisible }}</strong></td>
                    <td>{{ $condicion }}</td>
                    <td class="derecha">
                        Bs {{ number_format((float) $detalle->precio_unitario, 2, ',', '.') }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="total">
        <tr>
            <td style="width: 67%;">
                <div class="literal">
                    <strong>Son:</strong>
                    {{ $totalLiteral }}
                </div>
            </td>

            <td class="derecha" style="width: 33%;">
                Total a pagar:
                <strong>
                    Bs {{ number_format((float) $venta->total, 2, ',', '.') }}
                </strong>
            </td>
        </tr>
    </table>

    <div class="garantia">
        La garantía corresponde a las condiciones registradas para los equipos al momento de la venta.
        Conserve esta nota para cualquier atención posterior.
    </div>

    <table class="firmas">
        <tr>
            <td class="firma">
                <div class="firma-linea">
                    Recibí conforme
                </div>
            </td>

            <td style="width: 16%;"></td>

            <td class="firma">
                <div class="firma-linea">
                    Entregué conforme
                </div>
            </td>
        </tr>
    </table>

    <div class="documento">
        {{ $venta->numero }}
    </div>
</div>
</body>
</html>
