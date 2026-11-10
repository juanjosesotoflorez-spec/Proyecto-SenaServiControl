<?php

namespace Tests\Feature\Api;

class JwtAuthenticationTest extends LegacyApiTestCase
{
    public function test_active_user_can_login_and_use_a_bearer_token(): void
    {
        $this->createUsuario();

        $login = $this->postJson('/api/auth/login', [
            'correo' => 'cliente@example.test',
            'contrasena' => 'clave-segura-123',
        ])->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.role', 'cliente');

        $token = $login->json('access_token');

        $this->withToken($token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('email', 'cliente@example.test')
            ->assertJsonMissingPath('contrasena');
    }

    public function test_invalid_credentials_and_missing_tokens_are_rejected(): void
    {
        $this->createUsuario();

        $this->postJson('/api/auth/login', [
            'correo' => 'cliente@example.test',
            'contrasena' => 'incorrecta',
        ])->assertUnauthorized();

        $this->getJson('/api/auth/me')->assertUnauthorized();
        $this->withToken('invalid.token.value')->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_logout_revokes_the_current_bearer_token(): void
    {
        $this->createUsuario();
        $token = $this->postJson('/api/auth/login', [
            'correo' => 'cliente@example.test',
            'contrasena' => 'clave-segura-123',
        ])->assertOk()->json('access_token');

        $this->withToken($token)
            ->postJson('/api/auth/logout')
            ->assertOk();

        $this->withToken($token)
            ->getJson('/api/auth/me')
            ->assertUnauthorized();
    }
}
