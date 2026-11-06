Hola, {{ $due->plan->client->name }}:

Te recordamos que tienes un pago próximo a vencer.

Concepto: {{ $due->plan->concept }}
Monto: {{ \App\Support\Format::money($due->amount, $due->currency) }}
Fecha de pago: {{ $due->due_date->translatedFormat('l j \d\e F \d\e Y') }}

Si ya realizaste tu pago, por favor ignora este mensaje. Gracias.

Saludos,
{{ config('mail.from.name') }}
