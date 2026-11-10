<?php

namespace Tests\Feature\Api;

use App\Models\Cliente;

class ClienteContactUpdateTest extends LegacyApiTestCase
{
    public function test_customer_can_update_contact_information_and_account_email(): void
    {
        $usuario = $this->createUsuario();
        Cliente::query()->create([
            'nombre' => 'Cliente',
            'apellido' => 'Actual',
            'documento' => 'CC-2001',
            'correo' => 'cliente@example.test',
            'id_usuario' => $usuario->getKey(),
        ]);
        $token = $this->postJson('/api/auth/login', [
            'correo' => 'cliente@example.test',
            'contrasena' => 'clave-segura-123',
        ])->assertOk()->json('access_token');

        $this->withToken($token)
            ->putJson('/api/me/contact', [
                'correo' => 'contacto-nuevo@example.test',
                'telefono' => '3005550099',
                'direccion' => 'Carrera 12',
            ])
            ->assertOk()
            ->assertJsonPath('data.correo', 'contacto-nuevo@example.test')
            ->assertJsonPath('data.telefono', '3005550099')
            ->assertJsonPath('data.direccion', 'Carrera 12');

        $this->assertDatabaseHas('usuario', [
            'id_usuario' => $usuario->getKey(),
            'correo' => 'contacto-nuevo@example.test',
        ]);
        $this->assertDatabaseHas('cliente', [
            'id_usuario' => $usuario->getKey(),
            'correo' => 'contacto-nuevo@example.test',
            'telefono' => '3005550099',
        ]);
    }

    public function test_contact_update_rejects_an_email_owned_by_another_user(): void
    {
        $usuario = $this->createUsuario();
        Cliente::query()->create([
            'nombre' => 'Cliente',
            'apellido' => 'Actual',
            'documento' => 'CC-2002',
            'correo' => 'cliente@example.test',
            'id_usuario' => $usuario->getKey(),
        ]);
        $this->createUsuario([
            'usuario' => 'otro',
            'correo' => 'otro@example.test',
        ]);
        $token = $this->postJson('/api/auth/login', [
            'correo' => 'cliente@example.test',
            'contrasena' => 'clave-segura-123',
        ])->assertOk()->json('access_token');

        $this->withToken($token)
            ->putJson('/api/me/contact', ['correo' => 'otro@example.test'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['correo']);
    }

    public function test_contact_update_requires_at_least_one_field_and_a_customer_token(): void
    {
        $this->putJson('/api/me/contact', ['telefono' => '3005550000'])->assertUnauthorized();

        $usuario = $this->createUsuario();
        $token = $this->postJson('/api/auth/login', [
            'correo' => 'cliente@example.test',
            'contrasena' => 'clave-segura-123',
        ])->assertOk()->json('access_token');

        $this->withToken($token)
            ->putJson('/api/me/contact', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['contacto']);
    }
}
