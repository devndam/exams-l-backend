<?php

namespace App\Support;

class AuthContext
{
    public ?int $id = null;

    public ?string $role = null;

    public ?string $sessionToken = null;

    public function isAdmin(): bool
    {
        return $this->role === Constants::ROLE_ADMIN;
    }

    public function isCandidate(): bool
    {
        return $this->role === Constants::ROLE_CANDIDATE;
    }
}
