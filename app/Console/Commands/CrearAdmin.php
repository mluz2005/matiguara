<?php

namespace App\Console\Commands;

use App\Models\Usuario;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

/**
 * COMANDO: matiguara:crear-admin
 * -------------------------------------------------------------------------
 * Crea (o actualiza la clave de) la cuenta de administración de una
 * instalación NUEVA, sin pasar por los sembradores de prueba, que crean
 * cuentas con claves conocidas (Admin1234!, Docente123!, ...) y NO deben
 * cargarse en producción.
 *
 * Uso:
 *   php artisan matiguara:crear-admin correo@ejemplo.com --nombre=Maria --apellido=Pesallaccia
 *
 * La clave se pide por consola (no queda en el historial del servidor).
 * -------------------------------------------------------------------------
 */
class CrearAdmin extends Command
{
    protected $signature = 'matiguara:crear-admin
                            {email : Correo con el que ingresará la administración}
                            {--nombre=Administración : Nombre}
                            {--apellido=MatiGuarã : Apellido}';

    protected $description = 'Crea la cuenta de administración (pide la clave por consola)';

    public function handle(): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('El correo no es válido.');
            return self::FAILURE;
        }

        $clave = (string) $this->secret('Clave (mínimo 12 caracteres)');
        $repite = (string) $this->secret('Repetí la clave');

        if ($clave !== $repite) {
            $this->error('Las claves no coinciden.');
            return self::FAILURE;
        }
        if (mb_strlen($clave) < 12) {
            $this->error('La clave debe tener al menos 12 caracteres.');
            return self::FAILURE;
        }

        $usuario = Usuario::where('email', $email)->first();

        if ($usuario && $usuario->perfil !== 'admin') {
            $this->error('Ese correo ya existe con otro perfil (' . $usuario->perfil . '). Usá otro correo.');
            return self::FAILURE;
        }

        if ($usuario) {
            $usuario->password_hash = Hash::make($clave);
            $usuario->activo = true;
            $usuario->save();
            $this->info('Se actualizó la clave de ' . $email);
            return self::SUCCESS;
        }

        Usuario::create([
            'nombre'        => (string) $this->option('nombre'),
            'apellido'      => (string) $this->option('apellido'),
            'email'         => $email,
            'password_hash' => Hash::make($clave),
            'perfil'        => 'admin',
            'activo'        => true,
        ]);

        $this->info('Administración creada: ' . $email);
        return self::SUCCESS;
    }
}
