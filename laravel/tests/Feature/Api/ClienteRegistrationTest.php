<?php

namespace Tests\Feature\Api;

use App\Enums\RolUsuario;
use App\Models\Cliente;
use Illuminate\Support\Facades\Route;

class ClienteRegistrationTest extends LegacyApiTestCase
{
    public function test_customer_registration_creates_a_unique_user_and_customer_record(): void
    {
        $this->postJson('/api/register', [
            'usuario' => 'nuevo-cliente',
            'correo' => 'nuevo@example.test',
            'contrasena' => 'clave-segura-123',
            'contrasena_confirmation' => 'clave-segura-123',
            'nombre' => 'Ana',
            'apellido' => 'Lopez',
            'documento' => 'CC-9001',
            'telefono' => '3005550011',
            'direccion' => 'Calle 10',
        ])->assertCreated()
            ->assertJsonPath('data.documento', 'CC-9001')
            ->assertJsonPath('data.correo', 'nuevo@example.test')
            ->assertJsonMissingPath('data.contrasena');

        $this->assertDatabaseHas('usuario', [
            'usuario' => 'nuevo-cliente',
            'correo' => 'nuevo@example.test',
            'rol' => RolUsuario::Cliente->value,
            'estado' => 'activo',
        ]);
        $this->assertDatabaseHas('cliente', [
            'documento' => 'CC-9001',
            'correo' => 'nuevo@example.test',
        ]);
    }

    public function test_customer_registration_rejects_duplicate_document_and_email(): void
    {
        $this->createUsuario();
        Cliente::query()->create([
            'nombre' => 'Cliente',
            'apellido' => 'Existente',
            'documento' => 'CC-1000',
            'correo' => 'cliente@example.test',
        ]);

        $this->postJson('/api/register', [
            'usuario' => 'otro-cliente',
            'correo' => 'cliente@example.test',
            'contrasena' => 'clave-segura-123',
            'contrasena_confirmation' => 'clave-segura-123',
            'nombre' => 'Otra',
            'apellido' => 'Persona',
            'documento' => 'CC-1000',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['correo', 'documento']);
    }

    public function test_customer_directory_requires_a_staff_role(): void
    {
        $route = Route::getRoutes()->getByName('api.clientes.index');
        self::assertNotNull($route);
        self::assertContains('role:administrador,recepcionista', $route->gatherMiddleware());

        $this->getJson('/api/clientes')->assertUnauthorized();

        $this->createUsuario();
        $token = $this->postJson('/api/auth/login', [
            'correo' => 'cliente@example.test',
            'contrasena' => 'clave-segura-123',
        ])->assertOk()->json('access_token');

        $this->withToken($token)->getJson('/api/clientes')->assertForbidden();
    }
}
