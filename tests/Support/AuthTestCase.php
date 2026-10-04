<?php

namespace Tests\Support;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

abstract class AuthTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Solo el esquema de autenticación: las migraciones académicas de creación faltan.
        // No usar RefreshDatabase aquí ni modificar la base local de la aplicación.
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());

        foreach ([
            '0001_01_01_000000_create_users_table.php',
            '2026_02_28_193309_add_role_to_users_table.php',
            '2026_03_01_035229_add_status_approval_to_users_table.php',
            '2026_03_02_162336_add_requested_role_to_users_table.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
    }
}
