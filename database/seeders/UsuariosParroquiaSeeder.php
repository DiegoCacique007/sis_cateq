<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use RuntimeException;

class UsuariosParroquiaSeeder extends Seeder
{
    public function run(): void
    {
        // La contraseña inicial nunca se escribe en el código; si falta, el seeder se detiene sin tocar la base.
        $password = env('SEED_SECRETARIA_PASSWORD');
        if (! is_string($password) || trim($password) === '') {
            throw new RuntimeException('Falta SEED_SECRETARIA_PASSWORD. Defínela en .env antes de ejecutar este seeder. Si la configuración está en caché, ejecuta primero php artisan config:clear.');
        }
        if (mb_strlen($password) < 12) {
            throw new RuntimeException('SEED_SECRETARIA_PASSWORD debe tener al menos 12 caracteres. Corrígela en .env antes de ejecutar este seeder.');
        }

        // Una cuenta existente nunca se modifica: solo se crea si no existe.
        User::firstOrCreate(
            ['email' => 'secretaria@parroquia.org.mx'],
            [
                'name' => 'Secretaria',
                'password' => Hash::make($password),
                'role' => 'secretaria',
                'requested_role' => 'secretaria',
                'status' => 'aprobado',
                'approved_at' => now(),
                'approved_by' => null,
            ]
        );
    }
}