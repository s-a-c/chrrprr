<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use Laravel\Fortify\Contracts\ResetsUserPasswords;

final class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;
}
