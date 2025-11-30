@component('mail::message')
# 📅 {{ $subject }} - {{ $body['user'] }}  #{{ $body['id'] }}

@component('mail::table')
| 🔹 **Campo**            | 🔸 **Valor**           |
|-------------------------|------------------------|
| **⏰ Entrada**           | {{ $body['start_time'] }} |
| **🚪 Salida**            | {{ $body['end_time'] }}   |
| **🍴 Inicio Almuerzo**   | {{ $body['lunch_start_time'] }} |
| **⏳ Fin Almuerzo**      | {{ $body['lunch_end_time'] }}  |
@endcomponent

@component('mail::table')
| **Duración Total**      |                        |
|-------------------------|------------------------|
| 🕑 Almuerzo              | {{ $body['lunchDuration'] }} |
| 💼 Trabajo Neto         | {{ $body['workDuration'] }}  |
@endcomponent

@component('mail::panel')
📍 **Ubicacion:**  
{{ $body['stock'] }}
@endcomponent

@endcomponent