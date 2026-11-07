<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdmin extends Command
{
    // --from-env es para hostings sin terminal: toma ADMIN_PASSWORD del .env y lo corre un cron.
    protected $signature = 'app:admin {email} {--name=Administrador} {--from-env}';

    protected $description = 'Crea un usuario o cambia su contraseña (no hay registro público)';

    public function handle(): int
    {
        $email = strtolower((string) $this->argument('email'));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Correo no válido.');

            return self::FAILURE;
        }

        $password = $this->option('from-env')
            ? (string) env('ADMIN_PASSWORD')
            : (string) $this->secret('Contraseña (mínimo 10 caracteres)');

        if (strlen($password) < 10) {
            $this->error('La contraseña debe tener al menos 10 caracteres.');

            return self::FAILURE;
        }

        $user = User::firstOrNew(['email' => $email]);
        $user->name = $user->name ?: (string) $this->option('name');
        $user->password = Hash::make($password);
        $user->save();

        $this->info("Usuario listo: $email");

        return self::SUCCESS;
    }
}
