<?php

namespace App\Console\Commands;

use App\Models\Holiday;
use App\Models\ReminderLog;
use App\Services\DueGenerator;
use App\Services\ReminderService;
use App\Support\BusinessCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendPaymentReminders extends Command
{
    protected $signature = 'reminders:send {--dry-run : Muestra qué se enviaría, sin enviar nada}';

    protected $description = 'Genera los cobros del ciclo y envía los recordatorios que tocan hoy';

    public function handle(DueGenerator $generator, ReminderService $reminders): int
    {
        // El candado evita correos duplicados si dos ejecuciones se empalman.
        $lock = Cache::lock('reminders:send', 900);

        if (! $lock->get()) {
            $this->warn('Ya hay otra ejecución en curso.');

            return self::SUCCESS;
        }

        try {
            $today = CarbonImmutable::today();
            Holiday::ensureYear($today->year);
            Holiday::ensureYear($today->year + 1);
            $generator->syncAll();

            if (! (new BusinessCalendar)->isBusinessDay($today)) {
                $this->info('Hoy no es día hábil: no se envían correos.');

                return self::SUCCESS;
            }

            // Con el mailer "log" nada sale, pero los cobros quedarían marcados como
            // enviados y ya no se mandarían al conectar el SMTP.
            if (config('mail.default') === 'log' && ! $this->option('dry-run')) {
                $this->warn('MAIL_MAILER=log: configura el SMTP para enviar. No se envió nada.');

                return self::SUCCESS;
            }

            $dues = $reminders->dueToday();
            $failed = 0;

            foreach ($dues as $due) {
                $label = "{$due->plan->client->name} / {$due->plan->concept} ({$due->due_date->toDateString()})";

                if ($this->option('dry-run')) {
                    $this->line("Se enviaría: $label");

                    continue;
                }

                $log = $reminders->send($due, 'auto');

                if ($log->status === ReminderLog::FAILED) {
                    $failed++;
                    $this->error("Falló: $label - {$log->error}");
                } else {
                    $this->line("Enviado: $label");
                }
            }

            $this->info("Recordatorios: {$dues->count()}, fallidos: $failed.");

            return $failed > 0 ? self::FAILURE : self::SUCCESS;
        } finally {
            $lock->release();
        }
    }
}
