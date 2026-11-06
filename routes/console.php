<?php

use Illuminate\Support\Facades\Schedule;

// 8:00 es el envío normal; 12:00 y 16:00 reintentan fallos y cubren cobros dados
// de alta durante el día. Un cobro ya enviado nunca se vuelve a enviar.
Schedule::command('reminders:send')->cron('0 8,12,16 * * *');
