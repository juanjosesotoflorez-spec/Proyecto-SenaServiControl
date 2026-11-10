<?php

namespace App\Services;

use App\Models\Usuario;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Cache;
use LogicException;
use UnexpectedValueException;

class JwtTokenService
{
    private const REVOCATION_PREFIX = 'jwt:revoked:';

    public function issue(Usuario $usuario): array
    {
        $issuedAt = time();
        $expiresAt = $issuedAt + $this->ttlSeconds();
        $tokenId = bin2hex(random_bytes(16));

        $token = JWT::encode([
            'iss' => $this->issuer(),
            'iat' => $issuedAt,
            'nbf' => $issuedAt,
            'exp' => $expiresAt,
            'sub' => (string) $usuario->getKey(),
            'jti' => $tokenId,
            'role' => $usuario->rol->value,
        ], $this->secret(), 'HS256');

        return [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => $this->ttlSeconds(),
            'user' => [
                'id' => $usuario->getKey(),
                'username' => $usuario->usuario,
                'email' => $usuario->correo,
                'role' => $usuario->rol->value,
            ],
        ];
    }

    public function decode(string $token): object
    {
        $payload = JWT::decode($token, new Key($this->secret(), 'HS256'));

        if (
            ! isset($payload->iss, $payload->sub, $payload->jti, $payload->role, $payload->exp)
            || ! is_string($payload->iss)
            || ! is_string($payload->sub)
            || ! is_string($payload->jti)
            || ! is_string($payload->role)
            || ! is_int($payload->exp)
            || ! hash_equals($this->issuer(), $payload->iss)
        ) {
            throw new UnexpectedValueException('Invalid token claims.');
        }

        return $payload;
    }

    public function revoke(string $tokenId, int $expiresAt): void
    {
        Cache::put(
            self::REVOCATION_PREFIX.$tokenId,
            true,
            max(1, $expiresAt - time())
        );
    }

    public function isRevoked(string $tokenId): bool
    {
        return Cache::has(self::REVOCATION_PREFIX.$tokenId);
    }

    private function secret(): string
    {
        $secret = (string) config('jwt.secret');

        if (strlen($secret) < 32) {
            throw new LogicException('JWT_SECRET must contain at least 32 characters.');
        }

        return $secret;
    }

    private function issuer(): string
    {
        return (string) config('jwt.issuer');
    }

    private function ttlSeconds(): int
    {
        return max(1, (int) config('jwt.ttl', 60)) * 60;
    }
}
