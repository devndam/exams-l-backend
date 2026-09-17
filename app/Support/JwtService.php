<?php

namespace App\Support;

use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use InvalidArgumentException;
use UnexpectedValueException;

class JwtService
{
    private const ALGO = 'HS256';

    public function sign(array $payload): string
    {
        $now = time();
        $claims = $payload + [
            'iat' => $now,
            'exp' => $now + $this->expirySeconds(),
        ];

        return JWT::encode($claims, config('exams.jwt.secret'), self::ALGO);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ExpiredException|SignatureInvalidException|UnexpectedValueException
     */
    public function verify(string $token): array
    {
        $decoded = JWT::decode($token, new Key(config('exams.jwt.secret'), self::ALGO));

        return (array) $decoded;
    }

    /**
     * Parses the same "4h" / "30m" / "3600" style strings jsonwebtoken's `expiresIn` accepts.
     */
    private function expirySeconds(): int
    {
        $expiry = (string) config('exams.jwt.expiry', '4h');

        if (ctype_digit($expiry)) {
            return (int) $expiry;
        }

        if (! preg_match('/^(\d+)\s*(s|m|h|d|w)$/i', trim($expiry), $matches)) {
            throw new InvalidArgumentException("Invalid JWT_EXPIRY value: {$expiry}");
        }

        $amount = (int) $matches[1];
        $unit = strtolower($matches[2]);

        return $amount * match ($unit) {
            's' => 1,
            'm' => 60,
            'h' => 3600,
            'd' => 86400,
            'w' => 604800,
        };
    }
}
