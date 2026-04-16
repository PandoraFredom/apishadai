<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            size: 80mm auto;
            margin: 0;
        }

        body {
            width: 70mm;
            font-family: 'Courier New', monospace;
            margin: 0;
            padding: 1mm;
            background: white;
        }

        .ticket-container {
            border: 2px dashed #333;
            padding: 8px;
            background: white;
            max-width: 100%;
        }

        .logo {
            text-align: center;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid #000;
        }

        .logo img {
            width: 150px;
            max-width: 90%;
            height: auto;
        }

        .ticket-number {
            text-align: center;
            margin: 12px 0;
        }

        .ticket-box {
            border: 3px double #000;
            border-radius: 8px;
            padding: 10px 18px;
            display: inline-block;
            font-weight: bold;
            font-size: 20px;
            background: #f9f9f9;
            letter-spacing: 1px;
        }

        .ticket-value {
            text-align: center;
            font-weight: bold;
            font-size: 16px;
            margin-top: 6px;
            color: #000;
        }

        .promo-section {
            text-align: center;
            margin: 12px 0;
            padding: 8px;
            background: #f0f0f0;
            border-radius: 5px;
        }

        .promo-section p {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0;
        }

        .info-section {
            margin: 10px 0;
            padding: 8px 0;
            border-top: 1px dashed #666;
            border-bottom: 1px dashed #666;
        }

        .info-row {
            margin: 5px 0;
            font-size: 12px;
            word-wrap: break-word;
        }

        .info-label {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
            color: #000;
            display: inline-block;
            min-width: auto;
        }

        .info-value {
            font-weight: bold;
            color: #000;
        }

        .footer {
            text-align: center;
            margin-top: 12px;
            padding-top: 8px;
            border-top: 2px solid #000;
        }

        .footer p {
            font-size: 12;
            margin: 2px 0;
            color: #000000;
        }

        .good-luck {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 12px;
            color: #000;
            display: inline-block;
            min-width: auto;
        }

        @media print {
            body {
                padding: 5mm;
            }
        }
    </style>
</head>

<body>
    <div class="ticket-container">
        <!-- Logo -->
        <div class="logo">
            <img src="{{ public_path('img/shlogo.jpg') }}" alt="Logo">
        </div>

        <!-- Número de Ticket -->
        <div class="ticket-number">
            <div class="ticket-box">
                BOLETO# {{ $ticket->ntiket }}
            </div>
            @if(($ticket->valor ?? 0) > 0)
                <div class="ticket-value">
                    VALOR: L.{{ $ticket->valor }}
                </div>
            @endif
        </div>

        <!-- Promoción -->
        <div class="promo-section">
            <p>{{ $ticket->Promocion->nombre }}</p>
        </div>

        <!-- Información del Cliente -->
        <div class="info-section">
            <div class="info-row">
                <span class="info-label">CLIENTE:</span>
                <span class="info-value">{{ $ticket->Cliente->pnombre }} {{ $ticket->Cliente->papellido }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">IDENTIDAD:</span>
                <span class="info-value">{{ $ticket->Cliente->docid }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">TELÉFONO:</span>
                <span class="info-value">{{ $ticket->Cliente->telefono }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">PUNTO:</span>
                <span class="info-value">{{ $ticket->Stock->descripcion }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">FECHA:</span>
                <span class="info-value">{{ $ticket->created_at->format('d/m/Y H:i') }}</span>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p class="good-luck">¡MUCHA SUERTE!</p>
            <p>Conserve este ticket</p>
            <p>Válido para el sorteo</p>
        </div>
    </div>
</body>

</html>
