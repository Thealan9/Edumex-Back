<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte Edumex</title>
    <style>
        /* QUITAMOS LOS MÁRGENES DE LA HOJA FÍSICA */
        @page {
            margin: 0cm 0cm;
        }

        body {
            font-family: Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #181848;
        }

        /* BANNER SUPERIOR CORREGIDO */
        .header {
            background-color: #181848;
            color: #ffffff;
            /* Quitamos el padding y el box-sizing de aquí para evitar que DOMPDF rompa el ancho */
        }
        .header table {
            width: 100%;
            border-collapse: collapse;
        }
        .header td {
            /* Se lo pasamos a las celdas. Así DOMPDF calcula el 100% de la hoja correctamente */
            padding: 40px 40px 30px 40px;
            vertical-align: top;
        }
        .title { font-size: 22px; font-weight: bold; margin: 0; }
        .subtitle { font-size: 10px; font-weight: normal; margin-top: 5px; }
        .period-text { font-size: 12px; text-align: right; text-transform: uppercase; }

        /* CONTENEDOR PRINCIPAL */
        .content-wrapper {
            padding: 20px 40px;
        }

        /* TÍTULO DEL DOCUMENTO */
        .doc-title { font-size: 14px; margin-top: 10px; color: #181848; font-weight: normal; text-transform: uppercase; }

        /* RESUMEN FINANCIERO (2 COLUMNAS) */
        .summary-box { width: 100%; margin-top: 20px; margin-bottom: 30px; font-size: 10px; }
        .summary-box table { width: 100%; border-collapse: collapse; }
        .summary-box td { padding: 3px 0; vertical-align: top; width: 50%; }

        .text-red { color: #dc2626; }
        .text-green { color: #10b981; font-weight: bold; font-size: 11px; }

        /* TABLA DE PRODUCTOS */
        .data-table { width: 100%; border-collapse: collapse; font-size: 10px; margin-top: 10px; }
        .data-table th { background-color: #304878; color: white; padding: 8px 5px; text-align: left; }
        .data-table td { padding: 6px 5px; border-bottom: 1px solid #e5e7eb; }

        /* PIE DE PÁGINA */
        .footer {
            position: fixed;
            bottom: 20px;
            left: 0px;
            right: 0px;
            text-align: center;
            color: #646464;
            font-size: 8px;
        }
    </style>
</head>
<body>

<div class="header">
    <table>
        <tr>
            <td>
                <div class="title">EDUMEX</div>
                <div class="subtitle">SISTEMA DE CONTROL EDITORIAL Y LOGÍSTICO</div>
            </td>
            <td class="period-text">
                {{ strtoupper($fecha) }}
            </td>
        </tr>
    </table>
</div>

<div class="content-wrapper">

    <div class="doc-title">RENDIMIENTO FINANCIERO (CORTE DIARIO)</div>

    <div class="summary-box">
        <table>
            <tr>
                <td>
                    <div>Venta Bruta: ${{ number_format($totales['venta_bruta'], 2) }}</div>
                    <div class="text-red">Descuentos: -${{ number_format($totales['descuentos_totales'], 2) }}</div>
                    <div>Ingresos Netos: ${{ number_format($totales['ingresos_totales'], 2) }}</div>
                </td>
                <td>
                    <div>Inversión del Día: ${{ number_format($totales['inversion_compras'], 2) }}</div>
                    <div>Inversión Recuperada: {{ number_format($totales['porcentaje_recuperacion'], 1) }}%</div>
                    <div class="text-green mt-1">Ganancia Diaria: ${{ number_format($totales['utilidad_neta'], 2) }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="data-table">
        <thead>
        <tr>
            <th>PRODUCTO</th>
            <th>VOL. (F/D)</th>
            <th>VENTA NETA</th>
            <th>UTILIDAD</th>
        </tr>
        </thead>
        <tbody>
        @foreach($financialData as $item)
            <tr>
                <td>{{ $item->titulo }}</td>
                <td>{{ $item->unidades_fisicas }}F / {{ $item->unidades_digitales }}E</td>
                <td>${{ number_format($item->total_neto, 2) }}</td>
                <td>${{ number_format($item->ganancia_bruta_item, 2) }}</td>
            </tr>
        @endforeach
        @if(count($financialData) === 0)
            <tr>
                <td colspan="4" style="text-align: center; padding: 20px;">No hubo movimientos registrados en este día.</td>
            </tr>
        @endif
        </tbody>
    </table>

</div>

<div class="footer">Reporte EDUMEX</div>

</body>
</html>
