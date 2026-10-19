<?php

namespace Tests\Feature\Auth;

use Illuminate\Support\Facades\RateLimiter;
use Tests\Support\AuthTestCase;

/** Valores por defecto de producción: proxies de confianza (límites por IP) y cookie de sesión segura con HTTPS. */
class ProductionDefaultsTest extends AuthTestCase
{
    public function test_trusted_proxy_from_env_makes_ip_limits_use_the_real_visitor_ip(): void
    {
        config(['trustedproxy' => $this->configWith('trustedproxy.php', ['TRUSTED_PROXIES' => '10.0.0.1'])]);

        $this->failedLoginThrough('10.0.0.1', '203.0.113.7');

        $this->assertSame(1, RateLimiter::attempts('login-ip:203.0.113.7'));
        $this->assertSame(0, RateLimiter::attempts('login-ip:10.0.0.1'));
    }

    public function test_without_trusted_proxies_forwarded_headers_are_ignored(): void
    {
        $config = $this->configWith('trustedproxy.php', ['TRUSTED_PROXIES' => null]);
        $this->assertArrayHasKey('proxies', $config);
        $this->assertNull($config['proxies']);
        config(['trustedproxy' => $config]);

        $this->failedLoginThrough('10.0.0.1', '203.0.113.7');

        $this->assertSame(1, RateLimiter::attempts('login-ip:10.0.0.1'));
        $this->assertSame(0, RateLimiter::attempts('login-ip:203.0.113.7'));
    }

    public function test_session_cookie_is_secure_by_default_only_with_https_app_url(): void
    {
        $this->assertFalse($this->configWith('session.php', ['APP_URL' => 'http://localhost', 'SESSION_SECURE_COOKIE' => null])['secure']);
        $this->assertTrue($this->configWith('session.php', ['APP_URL' => 'https://catequesis.example.org', 'SESSION_SECURE_COOKIE' => null])['secure']);
        // La variable explícita sigue mandando.
        $this->assertFalse($this->configWith('session.php', ['APP_URL' => 'https://catequesis.example.org', 'SESSION_SECURE_COOKIE' => 'false'])['secure']);
        $this->assertTrue($this->configWith('session.php', ['APP_URL' => 'http://localhost', 'SESSION_SECURE_COOKIE' => 'true'])['secure']);
    }

    private function failedLoginThrough(string $proxy, string $visitor): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => $proxy])
            ->withHeaders(['X-Forwarded-For' => $visitor])
            ->post('/login', ['email' => 'nadie@example.com', 'password' => 'incorrecta']);
    }

    /** Lee un archivo de config/ con variables de entorno controladas (null = sin definir) y las restaura después. */
    private function configWith(string $file, array $vars): array
    {
        $backup = [];
        foreach ($vars as $name => $value) {
            $backup[$name] = [$_SERVER[$name] ?? null, $_ENV[$name] ?? null, getenv($name)];
            $this->setEnv($name, $value, $value, $value);
        }

        try {
            return require config_path($file);
        } finally {
            foreach ($backup as $name => [$server, $env, $putenv]) {
                $this->setEnv($name, $server, $env, $putenv === false ? null : $putenv);
            }
        }
    }

    private function setEnv(string $name, ?string $server, ?string $env, ?string $putenv): void
    {
        if ($server === null) {
            unset($_SERVER[$name]);
        } else {
            $_SERVER[$name] = $server;
        }
        if ($env === null) {
            unset($_ENV[$name]);
        } else {
            $_ENV[$name] = $env;
        }
        putenv($putenv === null ? $name : "{$name}={$putenv}");
    }
}
