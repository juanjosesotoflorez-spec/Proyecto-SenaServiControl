<?php

namespace Tests\Feature;

use App\Enums\EstadoUsuario;
use App\Enums\RolUsuario;
use App\Models\Cliente;
use App\Models\Usuario;
use App\Models\Vehiculo;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Api\LegacyApiTestCase;

class AdminRecordsTest extends LegacyApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['administrador', 'recepcionista', 'tecnico'] as $table) {
            Schema::connection('sqlite')->create($table, function (Blueprint $blueprint): void {
                $blueprint->increments('id');
                $blueprint->unsignedInteger('id_usuario')->nullable();
            });
        }

        Schema::connection('sqlite')->create('notificacion', function (Blueprint $table): void {
            $table->increments('id_notificacion');
            $table->unsignedInteger('id_cliente');
        });
        Schema::connection('sqlite')->create('servicio', function (Blueprint $table): void {
            $table->increments('id_servicio');
            $table->unsignedInteger('id_vehiculo');
        });
        Schema::connection('sqlite')->create('solicitud_servicio', function (Blueprint $table): void {
            $table->increments('id_solicitud');
            $table->unsignedInteger('id_vehiculo');
        });
        Schema::connection('sqlite')->create('historial_actividad_usuario', function (Blueprint $table): void {
            $table->increments('id_actividad');
            $table->unsignedInteger('id_usuario');
        });
        Schema::connection('sqlite')->create('incidencia', function (Blueprint $table): void {
            $table->increments('id_incidencia');
            $table->unsignedInteger('id_usuario_reporta');
            $table->unsignedInteger('id_usuario_atendio')->nullable();
        });
    }

    public function test_administrator_can_update_a_client_and_keep_account_email_in_sync(): void
    {
        $admin = $this->createAdmin();
        $customer = $this->createUsuario([
            'usuario' => 'cliente-editar',
            'correo' => 'cliente@example.test',
            'rol' => RolUsuario::Cliente,
        ]);
        $client = Cliente::query()->create([
            'nombre' => 'Ana',
            'apellido' => 'López',
            'documento' => '100001',
            'telefono' => '3005550011',
            'direccion' => 'Calle 10',
            'correo' => 'cliente@example.test',
            'id_usuario' => $customer->getKey(),
        ]);

        $this->actingAs($admin)
            ->put(route('clientes.update', $client), [
                'nombre' => 'Ana',
                'apellido' => 'López',
                'documento' => '100001',
                'telefono' => '3005550022',
                'direccion' => 'Carrera 20',
                'correo' => 'ana.nueva@example.test',
            ])
            ->assertRedirect(route('clientes.index'));

        $this->assertDatabaseHas('cliente', [
            'id_cliente' => $client->getKey(),
            'correo' => 'ana.nueva@example.test',
            'direccion' => 'Carrera 20',
        ]);
        $this->assertDatabaseHas('usuario', [
            'id_usuario' => $customer->getKey(),
            'correo' => 'ana.nueva@example.test',
        ]);
    }

    public function test_non_administrator_cannot_use_admin_edit_routes(): void
    {
        $receptionist = $this->createUsuario([
            'usuario' => 'recepcionista',
            'correo' => 'recepcionista@example.test',
            'rol' => RolUsuario::Recepcionista,
        ]);
        $client = Cliente::query()->create([
            'nombre' => 'Cliente',
            'apellido' => 'Prueba',
            'documento' => '100002',
            'telefono' => '3005550012',
            'direccion' => 'Calle 11',
            'correo' => 'prueba@example.test',
        ]);

        $this->actingAs($receptionist)
            ->get(route('clientes.edit', $client))
            ->assertForbidden();

        $this->actingAs($receptionist)
            ->get(route('usuarios.index'))
            ->assertForbidden();
    }

    public function test_client_cannot_be_deleted_when_it_has_vehicles_or_an_account(): void
    {
        $admin = $this->createAdmin();
        $client = Cliente::query()->create([
            'nombre' => 'Cliente',
            'apellido' => 'Con vehículo',
            'documento' => '100003',
            'telefono' => '3005550013',
            'direccion' => 'Calle 12',
            'correo' => 'vehiculo@example.test',
        ]);
        Vehiculo::query()->create([
            'placa' => 'ABC123',
            'marca' => 'Toyota',
            'modelo' => 'Yaris',
            'color' => 'Gris',
            'anio' => 2020,
            'kilometraje' => 1000,
            'id_cliente' => $client->getKey(),
        ]);

        $this->actingAs($admin)
            ->delete(route('clientes.destroy', $client))
            ->assertSessionHasErrors('cliente');

        $this->assertDatabaseHas('cliente', ['id_cliente' => $client->getKey()]);
    }

    public function test_client_with_a_linked_account_cannot_be_deleted(): void
    {
        $admin = $this->createAdmin();
        $customer = $this->createUsuario([
            'usuario' => 'cliente-vinculado',
            'correo' => 'vinculado@example.test',
            'rol' => RolUsuario::Cliente,
        ]);
        $client = Cliente::query()->create([
            'nombre' => 'Cliente',
            'apellido' => 'Vinculado',
            'documento' => '100007',
            'telefono' => '3005550016',
            'direccion' => 'Calle 15',
            'correo' => 'vinculado@example.test',
            'id_usuario' => $customer->getKey(),
        ]);

        $this->actingAs($admin)
            ->delete(route('clientes.destroy', $client))
            ->assertSessionHasErrors('cliente');

        $this->assertDatabaseHas('cliente', ['id_cliente' => $client->getKey()]);
    }

    public function test_vehicle_cannot_be_deleted_when_it_has_service_history(): void
    {
        $admin = $this->createAdmin();
        $client = Cliente::query()->create([
            'nombre' => 'Cliente',
            'apellido' => 'Prueba',
            'documento' => '100004',
            'telefono' => '3005550014',
            'direccion' => 'Calle 13',
            'correo' => 'vehiculo2@example.test',
        ]);
        $vehicle = Vehiculo::query()->create([
            'placa' => 'DEF456',
            'marca' => 'Kia',
            'modelo' => 'Rio',
            'color' => 'Azul',
            'anio' => 2021,
            'kilometraje' => 2000,
            'id_cliente' => $client->getKey(),
        ]);
        DB::table('servicio')->insert(['id_vehiculo' => $vehicle->getKey()]);

        $this->actingAs($admin)
            ->delete(route('vehiculos.destroy', $vehicle))
            ->assertSessionHasErrors('vehiculo');

        $this->assertDatabaseHas('vehiculo', ['id_vehiculo' => $vehicle->getKey()]);
    }

    public function test_vehicle_owner_cannot_be_changed_after_service_history_exists(): void
    {
        $admin = $this->createAdmin();
        $clients = collect([
            ['nombre' => 'Cliente', 'apellido' => 'Uno', 'documento' => '100005', 'correo' => 'uno@example.test'],
            ['nombre' => 'Cliente', 'apellido' => 'Dos', 'documento' => '100006', 'correo' => 'dos@example.test'],
        ])->map(fn (array $data): Cliente => Cliente::query()->create($data + [
            'telefono' => '3005550015',
            'direccion' => 'Calle 14',
        ]));
        $vehicle = Vehiculo::query()->create([
            'placa' => 'GHI789',
            'marca' => 'Mazda',
            'modelo' => 'Mazda 2',
            'color' => 'Rojo',
            'anio' => 2022,
            'kilometraje' => 3000,
            'id_cliente' => $clients[0]->getKey(),
        ]);
        DB::table('servicio')->insert(['id_vehiculo' => $vehicle->getKey()]);

        $this->actingAs($admin)
            ->put(route('vehiculos.update', $vehicle), [
                'placa' => 'GHI789',
                'marca' => 'Mazda',
                'modelo' => 'Mazda 2',
                'color' => 'Rojo',
                'anio' => 2022,
                'kilometraje' => 3000,
                'id_cliente' => $clients[1]->getKey(),
            ])
            ->assertSessionHasErrors('id_cliente');

        $this->assertDatabaseHas('vehiculo', [
            'id_vehiculo' => $vehicle->getKey(),
            'id_cliente' => $clients[0]->getKey(),
        ]);
    }

    public function test_admin_management_pages_render(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->get(route('usuarios.index'))->assertOk();
        $this->actingAs($admin)->get(route('clientes.index'))->assertOk();
        $this->actingAs($admin)->get(route('vehiculos.index'))->assertOk();
    }

    public function test_records_without_profiles_or_history_can_be_deleted(): void
    {
        $admin = $this->createAdmin();
        $client = Cliente::query()->create([
            'nombre' => 'Cliente',
            'apellido' => 'Sin actividad',
            'documento' => '100008',
            'telefono' => '3005550017',
            'direccion' => 'Calle 16',
            'correo' => 'sinactividad@example.test',
        ]);
        $vehicle = Vehiculo::query()->create([
            'placa' => 'JKL012',
            'marca' => 'Honda',
            'modelo' => 'Civic',
            'color' => 'Negro',
            'anio' => 2020,
            'kilometraje' => 5000,
            'id_cliente' => $client->getKey(),
        ]);
        $account = $this->createUsuario([
            'usuario' => 'cuenta-sin-perfil',
            'correo' => 'cuenta@example.test',
            'rol' => RolUsuario::Cliente,
        ]);

        $this->actingAs($admin)
            ->delete(route('vehiculos.destroy', $vehicle))
            ->assertRedirect(route('vehiculos.index'));
        $this->assertDatabaseMissing('vehiculo', ['id_vehiculo' => $vehicle->getKey()]);

        $this->delete(route('clientes.destroy', $client))
            ->assertRedirect(route('clientes.index'));
        $this->assertDatabaseMissing('cliente', ['id_cliente' => $client->getKey()]);

        $this->delete(route('usuarios.destroy', $account))
            ->assertRedirect(route('usuarios.index'));
        $this->assertDatabaseMissing('usuario', ['id_usuario' => $account->getKey()]);
    }

    public function test_administrator_can_deactivate_a_user_but_cannot_change_role_without_its_profile(): void
    {
        $admin = $this->createAdmin();
        $customer = $this->createUsuario([
            'usuario' => 'cliente-estado',
            'correo' => 'estado@example.test',
            'rol' => RolUsuario::Cliente,
        ]);

        $this->actingAs($admin)
            ->put(route('usuarios.update', $customer), [
                'usuario' => 'cliente-estado',
                'correo' => 'estado@example.test',
                'rol' => RolUsuario::Cliente->value,
                'estado' => EstadoUsuario::Inactivo->value,
            ])
            ->assertRedirect(route('usuarios.index'));

        $this->assertDatabaseHas('usuario', [
            'id_usuario' => $customer->getKey(),
            'estado' => EstadoUsuario::Inactivo->value,
        ]);

        $this->actingAs($admin)
            ->put(route('usuarios.update', $customer), [
                'usuario' => 'cliente-estado',
                'correo' => 'estado@example.test',
                'rol' => RolUsuario::Tecnico->value,
                'estado' => EstadoUsuario::Inactivo->value,
            ])
            ->assertSessionHasErrors('rol');
    }

    public function test_last_active_administrator_cannot_be_deactivated_or_deleted(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->put(route('usuarios.update', $admin), [
                'usuario' => $admin->usuario,
                'correo' => $admin->correo,
                'rol' => RolUsuario::Administrador->value,
                'estado' => EstadoUsuario::Inactivo->value,
            ])
            ->assertSessionHasErrors('estado');

        $this->actingAs($admin)
            ->delete(route('usuarios.destroy', $admin))
            ->assertSessionHasErrors('usuario');

        $this->assertDatabaseHas('usuario', [
            'id_usuario' => $admin->getKey(),
            'estado' => EstadoUsuario::Activo->value,
        ]);
    }

    private function createAdmin(): Usuario
    {
        return $this->createUsuario([
            'usuario' => 'admin-'.uniqid(),
            'correo' => uniqid('admin-').'@example.test',
            'rol' => RolUsuario::Administrador,
            'estado' => EstadoUsuario::Activo,
        ]);
    }
}
