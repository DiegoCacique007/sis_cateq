<?php

namespace App\Services\Authorization;

use App\Enums\UserRole;

/** Instantánea por petición, creada exclusivamente a partir del servidor. */
final readonly class AccessContext
{
    public function __construct(
        public int $userId,
        public UserRole $role,
        public string $status,
        public ?int $communityId,
        public ?int $activePeriodId,
    ) {}
}
