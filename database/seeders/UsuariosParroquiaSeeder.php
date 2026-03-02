<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UsuariosParroquiaSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'secretaria@parroquia.org.mx'],
            [
                'name' => 'Secretaria',
                'password' => Hash::make('S3cr3t.1'),
                'role' => 'secretaria',
                'requested_role' => 'secretaria',
                'status' => 'aprobado',
                'approved_at' => now(),
                'approved_by' => null,
            ]
        );
    }
}