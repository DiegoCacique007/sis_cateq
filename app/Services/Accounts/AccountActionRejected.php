<?php

namespace App\Services\Accounts;

use RuntimeException;

/** Una acción sobre una cuenta se rechazó; el mensaje, en español, se muestra a Secretaría. */
class AccountActionRejected extends RuntimeException
{
}
