<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <style>
        body {
            width: 80mm;
            font-family: monospace;
            font-size: 15px;
            text-align: center;
            margin: -45;
            padding: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            word-wrap: break-word;
            overflow-wrap: break-word;
            background: none;
        }

        .logo img {
            width: 180px;
            margin: 10px 0;
        }

        h3 {
            font-size: 18px;
            margin: 5px 0;
        }

        p {
            margin: 4px 0;
            font-size: 15px;
        }

        .ticket-box {
            border: 1px solid #000;
            border-radius: 4px;
            padding: 6px 10px;
            margin-bottom: 10px;
            display: inline-block;
            font-weight: bold;
            font-size: 17px;
        }

        .qr {
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <div class="logo">
        <img src="{{ public_path('img/shlogo.jpg') }}" alt="Logo">
    </div>

    <div class="ticket-box">
        Ticket #{{ $ticket->ntiket }}
    </div>

    <p>Promoción: {{ $ticket->Promocion->nombre }}</p>
    <p>Cliente: {{ $ticket->Cliente->pnombre }} {{ $ticket->Cliente->papellido }}</p>
    <p>Identidad: {{ $ticket->Cliente->docid }}</p>
    <p>Teléfono: {{ $ticket->Cliente->telefono }}</p>
    <p>Punto: {{ $ticket->Stock->descripcion }}</p>
    <p>Fecha: {{ $ticket->created_at->format('d/m/Y H:i') }}</p>

</body>

</html>
