<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendTestEmail extends Command
{
    protected $signature = 'reminders:test {email}';

    protected $description = 'Envía un correo de prueba para comprobar el SMTP';

    public function handle(): int
    {
        try {
            Mail::raw('Si lees esto, el SMTP del administrador de pagos funciona.', function ($message) {
                $message->to($this->argument('email'))->subject('Prueba de correo');
            });
        } catch (Throwable $e) {
            $this->error('No se pudo enviar: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Mailer usado: '.config('mail.default').'. Correo entregado al servidor.');

        return self::SUCCESS;
    }
}
