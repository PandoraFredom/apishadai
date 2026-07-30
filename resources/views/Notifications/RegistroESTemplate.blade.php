@component('mail::message')
# {{ $body['event_label'] }}

Se registró **{{ $body['event_label'] }}** para **{{ $body['user'] ?: 'Usuario' }}**
el {{ $body['event_time'] ?: 'horario indicado' }}.

@component('mail::table')
| Campo | Hora |
|:--|:--|
| Entrada | {{ $body['start_time'] ?: '—' }} |
| Inicio de almuerzo | {{ $body['lunch_start_time'] ?: '—' }} |
| Fin de almuerzo | {{ $body['lunch_end_time'] ?: '—' }} |
| Salida | {{ $body['end_time'] ?: '—' }} |
| Duración de almuerzo | {{ $body['lunchDuration'] ?: '—' }} |
| Duración de jornada | {{ $body['workDuration'] ?: '—' }} |
@endcomponent

@component('mail::panel')
**Stock:** {{ $body['stock'] ?: 'Sin stock' }}  
**Registro:** #{{ $body['id'] }}
@endcomponent

Este correo fue generado automáticamente por Shadai.

@endcomponent
