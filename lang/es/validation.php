<?php


return [
    'required' => 'Este campo es obligatorio.',
    'string' => 'Este campo debe ser texto.',
    'email' => 'Escribe un correo válido.',
    'ascii' => 'El correo no puede llevar acentos ni ñ.',
    'date' => 'Escribe una fecha válida.',
    'numeric' => 'Escribe un número.',
    'in' => 'Elige una opción válida.',
    'enum' => 'Elige una opción válida.',
    'after_or_equal' => 'La fecha debe ser igual o posterior a la próxima fecha de pago.',
    'min' => ['numeric' => 'Debe ser al menos :min.', 'string' => 'Debe tener al menos :min caracteres.'],
    'max' => ['numeric' => 'No puede ser mayor a :max.', 'string' => 'No puede tener más de :max caracteres.'],
];
