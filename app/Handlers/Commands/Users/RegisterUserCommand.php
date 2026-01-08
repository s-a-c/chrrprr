<?php

declare(strict_types=1);

namespace App\Handlers\Commands\Users;

/**
 * Regfinal ister User Command.
 *
 * DTO for the RegisterUser command.
 */
final readonly class RegisterUserCommand
{
    /**
     * @param  array<string, mixed>  $data  User registration data
     */
    public function __construct(
        public array $data
    ) {}
}
