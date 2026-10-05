<?php

namespace App\Services\Authorization;

final readonly class AccessDecision
{
    private function __construct(public string $state, public string $code) {}

    public static function allow(): self
    {
        return new self('allowed', 'ALLOWED');
    }

    public static function deny(string $code = 'OUTSIDE_SCOPE'): self
    {
        return new self('denied', $code);
    }

    public static function missing(string $code): self
    {
        return new self('missing_context', $code);
    }

    public static function ambiguous(): self
    {
        return new self('ambiguous', 'AMBIGUOUS_ASSIGNMENT');
    }

    public function isAllowed(): bool
    {
        return $this->state === 'allowed';
    }
}
