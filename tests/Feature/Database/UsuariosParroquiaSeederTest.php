<?php

namespace Tests\Feature\Database;

use App\Models\User;
use Database\Seeders\UsuariosParroquiaSeeder;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\Support\AuthTestCase;

/** AuthTestCase exige SQLite en memoria: el seeder nunca se ejecuta contra la base real. */
class UsuariosParroquiaSeederTest extends AuthTestCase
{
    private const VARIABLE = 'SEED_SECRETARIA_PASSWORD';

    // Valores de prueba evidentes; ninguno se parece a una contraseña real.
    private const TEST_VALUE = 'valor-de-prueba-no-real';
    private const OTHER_TEST_VALUE = 'otro-valor-de-prueba-no-real';

    protected function setUp(): void
    {
        parent::setUp();
        $this->removeVariable();
    }

    protected function tearDown(): void
    {
        $this->removeVariable();
        parent::tearDown();
    }

    private function setVariable(string $value): void
    {
        $_ENV[self::VARIABLE] = $value;
        $_SERVER[self::VARIABLE] = $value;
        putenv(self::VARIABLE.'='.$value);
    }

    private function removeVariable(): void
    {
        unset($_ENV[self::VARIABLE], $_SERVER[self::VARIABLE]);
        putenv(self::VARIABLE);
    }

    private function seedSecretary(): void
    {
        $this->app->make(UsuariosParroquiaSeeder::class)->run();
    }

    public function test_variable_is_removed_before_each_test(): void
    {
        $this->assertNull(env(self::VARIABLE));
        $this->assertFalse(getenv(self::VARIABLE));
    }

    public static function missingValues(): array
    {
        return ['sin variable' => [null], 'vacía' => [''], 'solo espacios' => ['              ']];
    }

    #[DataProvider('missingValues')]
    public function test_missing_variable_stops_seeder_without_creating_account(?string $value): void
    {
        if ($value !== null) {
            $this->setVariable($value);
        }
        try {
            $this->seedSecretary();
            $this->fail('El seeder debía detenerse.');
        } catch (RuntimeException $error) {
            $this->assertSame('Falta SEED_SECRETARIA_PASSWORD. Defínela en .env antes de ejecutar este seeder. Si la configuración está en caché, ejecuta primero php artisan config:clear.', $error->getMessage());
        }
        $this->assertSame(0, User::count());
    }

    public function test_short_value_stops_seeder_without_creating_account(): void
    {
        $this->setVariable('corto');
        try {
            $this->seedSecretary();
            $this->fail('El seeder debía detenerse.');
        } catch (RuntimeException $error) {
            $this->assertSame('SEED_SECRETARIA_PASSWORD debe tener al menos 12 caracteres. Corrígela en .env antes de ejecutar este seeder.', $error->getMessage());
            $this->assertStringNotContainsString('corto', $error->getMessage());
        }
        $this->assertSame(0, User::count());
    }

    public function test_valid_value_creates_approved_secretary_with_hashed_password(): void
    {
        $this->setVariable(self::TEST_VALUE);
        $this->seedSecretary();

        $user = User::sole();
        $this->assertSame('secretaria@parroquia.org.mx', $user->email);
        $this->assertSame('secretaria', $user->role);
        $this->assertSame('aprobado', $user->status);
        $this->assertNotSame(self::TEST_VALUE, $user->getAttributes()['password']);
        $this->assertTrue(Hash::check(self::TEST_VALUE, $user->password));
    }

    public function test_second_run_with_other_value_never_modifies_existing_account(): void
    {
        $this->setVariable(self::TEST_VALUE);
        $this->seedSecretary();
        User::sole()->update(['status' => 'bloqueado']);

        $this->setVariable(self::OTHER_TEST_VALUE);
        $this->seedSecretary();

        $user = User::sole();
        $this->assertTrue(Hash::check(self::TEST_VALUE, $user->password));
        $this->assertFalse(Hash::check(self::OTHER_TEST_VALUE, $user->password));
        $this->assertSame('bloqueado', $user->status);
    }

    public function test_seeder_source_has_no_hardcoded_password(): void
    {
        $source = file_get_contents(database_path('seeders/UsuariosParroquiaSeeder.php'));
        $this->assertDoesNotMatchRegularExpression('/Hash::make\(\s*[\'"]/', $source);
        $this->assertDoesNotMatchRegularExpression('/[\'"]password[\'"]\s*=>\s*[\'"]/', $source);
        $this->assertStringContainsString("env('SEED_SECRETARIA_PASSWORD')", $source);
    }
}
