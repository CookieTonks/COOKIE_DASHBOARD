<?php

return [
    // Días hábiles antes del vencimiento en que sale el recordatorio.
    'business_days_before' => (int) env('REMINDER_BUSINESS_DAYS_BEFORE', 2),

    // Correo que recibe una copia oculta de cada recordatorio. Vacío: sin copia.
    'bcc' => env('REMINDER_BCC'),
];
