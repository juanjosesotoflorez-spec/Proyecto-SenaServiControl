<?php

namespace Tests\Unit;

use App\Enums\RolUsuario;
use App\Models\Usuario;
use App\Services\JwtTokenService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class JwtTokenServiceTest extends TestCase
{
    public function test_it_issues_and_decodes_a_role_scoped_token(): void
    {
        $usuario = new Usuario;
        $usuario->forceFill([
            'id_usuario' => 17,
            'usuario' => 'cliente',
            'correo' => 'cliente@example.test',
            'rol' => RolUsuario::Cliente,
        ]);

        $tokens = app(JwtTokenService::class);
        $issued = $tokens->issue($usuario);
        $claims = $tokens->decode($issued['access_token']);

        self::assertSame('Bearer', $issued['token_type']);
        self::assertSame(3600, $issued['expires_in']);
        self::assertSame('17', $claims->sub);
        self::assertSame(RolUsuario::Cliente->value, $claims->role);
        self::assertNotSame('', $claims->jti);
    }

    public function test_it_revokes_tokens_until_their_expiration(): void
    {
        Cache::flush();
        $tokens = app(JwtTokenService::class);

        self::assertFalse($tokens->isRevoked('test-token-id'));

        $tokens->revoke('test-token-id', time() + 60);

        self::assertTrue($tokens->isRevoked('test-token-id'));
    }
}
