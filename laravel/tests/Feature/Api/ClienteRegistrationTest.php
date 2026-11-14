<?php

namespace Tests\Feature\Api;

use App\Enums\RolUsuario;
use App\Models\Cliente;
use App\Models\Usuario;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class ClienteRegistrationTest extends LegacyApiTestCase
{
    public function test_auth_pages_do_not_show_fixed_validation_help_when_clean(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('form-text text-muted')
            ->assertDontSee('invalid-feedback d-block')
            ->assertSee('data-validate', false)
            ->assertSee('novalidate', false);

        $this->get(route('register'))
            ->assertOk()
            ->assertDontSee('form-text text-muted')
            ->assertDontSee('invalid-feedback d-block')
            ->assertSee('data-validate', false)
            ->assertSee('novalidate', false);
    }

    public function test_web_login_authenticates_against_the_usuario_password_column(): void
    {
        $this->createUsuario(['correo' => 'cliente@example.test']);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'correo' => 'cliente@example.test',
                'contrasena' => 'clave-segura-123',
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
    }

    public function test_dashboard_renders_for_a_customer_account_without_a_linked_customer_profile(): void
    {
        Schema::connection('sqlite')->create('servicio', function (Blueprint $table): void {
            $table->increments('id_servicio');
            $table->string('estado');
            $table->date('fecha_ingreso');
            $table->decimal('costo', 10, 2)->default(0);
            $table->unsignedInteger('id_vehiculo')->nullable();
            $table->unsignedInteger('id_tecnico')->nullable();
        });

        $usuario = $this->createUsuario();

        $this->actingAs($usuario)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Tu cuenta no tiene un perfil de cliente vinculado.')
            ->assertSee('Todavía no hay servicios registrados.');
    }

    public function test_web_registration_creates_a_customer_account_and_starts_a_session(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee(route('register.store'), false)
            ->assertSee('auth-register-panel')
            ->assertSee('Una letra mayúscula')
            ->assertSee('Al menos dos números')
            ->assertSee('Un carácter especial')
            ->assertSee('../../index.html', false);

        $response = $this->post(route('register.store'), [
            'usuario' => 'nuevo-cliente',
            'correo' => 'nuevo@gmail.com',
            'contrasena' => 'Clave-segura-12',
            'contrasena_confirmation' => 'Clave-segura-12',
            'nombre' => 'Ana',
            'apellido' => 'Lopez',
            'documento' => '9001001',
            'telefono' => '3005550011',
            'direccion' => 'Calle 10',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('usuario', [
            'usuario' => 'nuevo-cliente',
            'correo' => 'nuevo@gmail.com',
            'rol' => RolUsuario::Cliente->value,
            'estado' => 'activo',
        ]);
        $this->assertDatabaseHas('cliente', [
            'documento' => '9001001',
            'correo' => 'nuevo@gmail.com',
        ]);
        $this->assertTrue(Hash::check('Clave-segura-12', Usuario::query()->firstOrFail()->contrasena));
    }

    public function test_web_registration_rejects_mismatched_passwords(): void
    {
        $this->from(route('register'))
            ->post(route('register.store'), [
                'usuario' => 'nuevo-cliente',
                'correo' => 'nuevo@gmail.com',
                'contrasena' => 'Clave-segura-12',
                'contrasena_confirmation' => 'Otra-clave-12',
                'nombre' => 'Ana',
                'apellido' => 'Lopez',
                'documento' => '9001001',
            ])
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('contrasena');

        $this->assertDatabaseCount('usuario', 0);
        $this->assertDatabaseCount('cliente', 0);
    }

    public function test_customer_registration_rejects_passwords_missing_policy_requirements(): void
    {
        foreach (['clave-segura-12', 'Clave-segura-1', 'Clavesegura12'] as $password) {
            $this->from(route('register'))
                ->post(route('register.store'), [
                    'usuario' => 'nuevo-cliente',
                    'correo' => 'nuevo@gmail.com',
                    'contrasena' => $password,
                    'contrasena_confirmation' => $password,
                    'nombre' => 'Ana',
                    'apellido' => 'Lopez',
                    'documento' => '9001001',
                ])
                ->assertRedirect(route('register'))
                ->assertSessionHasErrors('contrasena');
        }

        $this->assertDatabaseCount('usuario', 0);
        $this->assertDatabaseCount('cliente', 0);
    }

    public function test_customer_registration_creates_a_unique_user_and_customer_record(): void
    {
        $this->postJson('/api/register', [
            'usuario' => 'nuevo-cliente',
            'correo' => 'nuevo@gmail.com',
            'contrasena' => 'Clave-segura-12',
            'contrasena_confirmation' => 'Clave-segura-12',
            'nombre' => 'Ana',
            'apellido' => 'Lopez',
            'documento' => '9001001',
            'telefono' => '3005550011',
            'direccion' => 'Calle 10',
        ])->assertCreated()
            ->assertJsonPath('data.documento', '9001001')
            ->assertJsonPath('data.correo', 'nuevo@gmail.com')
            ->assertJsonMissingPath('data.contrasena');

        $this->assertDatabaseHas('usuario', [
            'usuario' => 'nuevo-cliente',
            'correo' => 'nuevo@gmail.com',
            'rol' => RolUsuario::Cliente->value,
            'estado' => 'activo',
        ]);
        $this->assertDatabaseHas('cliente', [
            'documento' => '9001001',
            'correo' => 'nuevo@gmail.com',
        ]);
    }

    public function test_customer_registration_rejects_invalid_contact_data(): void
    {
        $this->postJson('/api/register', [
            'usuario' => 'nuevo-cliente',
            'correo' => 'correo-no-valido',
            'contrasena' => 'Clave-segura-12',
            'contrasena_confirmation' => 'Clave-segura-12',
            'nombre' => 'A1na',
            'apellido' => 'Ló#pez',
            'documento' => '12345',
            'telefono' => '300ABC0011',
            'direccion' => 'abc',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['correo', 'nombre', 'apellido', 'documento', 'telefono', 'direccion']);

        $this->assertDatabaseCount('usuario', 0);
        $this->assertDatabaseCount('cliente', 0);
    }

    public function test_customer_registration_rejects_duplicate_document_and_email(): void
    {
        $this->createUsuario(['correo' => 'cliente@gmail.com']);
        Cliente::query()->create([
            'nombre' => 'Cliente',
            'apellido' => 'Existente',
            'documento' => '1001000',
            'correo' => 'cliente@gmail.com',
        ]);

        $this->postJson('/api/register', [
            'usuario' => 'otro-cliente',
            'correo' => 'cliente@gmail.com',
            'contrasena' => 'Clave-segura-12',
            'contrasena_confirmation' => 'Clave-segura-12',
            'nombre' => 'Otra',
            'apellido' => 'Persona',
            'documento' => '1001000',
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
