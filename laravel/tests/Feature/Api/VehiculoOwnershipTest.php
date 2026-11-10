<?php

namespace Tests\Feature\Api;

use App\Enums\RolUsuario;
use App\Models\Cliente;
use App\Models\Vehiculo;

class VehiculoOwnershipTest extends LegacyApiTestCase
{
    public function test_customer_vehicle_is_automatically_associated_with_its_owner(): void
    {
        $usuario = $this->createUsuario();
        $cliente = Cliente::query()->create([
            'nombre' => 'Cliente',
            'apellido' => 'Propietario',
            'documento' => 'CC-5001',
            'id_usuario' => $usuario->getKey(),
        ]);
        $token = $this->postJson('/api/auth/login', [
            'correo' => 'cliente@example.test',
            'contrasena' => 'clave-segura-123',
        ])->assertOk()->json('access_token');

        $this->withToken($token)
            ->postJson('/api/vehiculos', [
                'placa' => 'ABC123',
                'marca' => 'Toyota',
                'modelo' => 'Corolla',
                'anio' => 2022,
                'kilometraje' => 10000,
            ])
            ->assertCreated()
            ->assertJsonPath('data.cliente.id', $cliente->getKey())
            ->assertJsonPath('data.placa', 'ABC123');

        $this->assertDatabaseHas('vehiculo', [
            'placa' => 'ABC123',
            'id_cliente' => $cliente->getKey(),
        ]);
    }

    public function test_customer_cannot_choose_another_owner_or_view_their_vehicle(): void
    {
        $customer = $this->createUsuario();
        $owner = Cliente::query()->create([
            'nombre' => 'Cliente',
            'apellido' => 'Actual',
            'documento' => 'CC-5002',
            'id_usuario' => $customer->getKey(),
        ]);
        $otherOwner = Cliente::query()->create([
            'nombre' => 'Otra',
            'apellido' => 'Persona',
            'documento' => 'CC-5003',
        ]);
        $this->createUsuario([
            'usuario' => 'otro',
            'correo' => 'otro@example.test',
        ]);
        $otherVehicle = Vehiculo::query()->create([
            'placa' => 'XYZ999',
            'id_cliente' => $otherOwner->getKey(),
        ]);
        $token = $this->postJson('/api/auth/login', [
            'correo' => 'cliente@example.test',
            'contrasena' => 'clave-segura-123',
        ])->assertOk()->json('access_token');

        $this->withToken($token)
            ->postJson('/api/vehiculos', [
                'placa' => 'BAD123',
                'id_cliente' => $otherOwner->getKey(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['id_cliente']);

        $this->withToken($token)
            ->getJson('/api/vehiculos/'.$otherVehicle->getKey())
            ->assertNotFound();

        $this->withToken($token)
            ->getJson('/api/vehiculos')
            ->assertOk()
            ->assertJsonMissing(['placa' => 'XYZ999']);

        $this->assertSame(0, $owner->vehiculos()->count());
    }

    public function test_staff_can_assign_a_vehicle_to_an_existing_customer(): void
    {
        $this->createUsuario([
            'usuario' => 'recepcion',
            'correo' => 'recepcion@example.test',
            'rol' => RolUsuario::Recepcionista,
        ]);
        $cliente = Cliente::query()->create([
            'nombre' => 'Cliente',
            'apellido' => 'Taller',
            'documento' => 'CC-5004',
        ]);
        $token = $this->postJson('/api/auth/login', [
            'correo' => 'recepcion@example.test',
            'contrasena' => 'clave-segura-123',
        ])->assertOk()->json('access_token');

        $this->withToken($token)
            ->postJson('/api/vehiculos', [
                'placa' => 'TAL001',
                'id_cliente' => $cliente->getKey(),
            ])
            ->assertCreated()
            ->assertJsonPath('data.cliente.id', $cliente->getKey());
    }
}
