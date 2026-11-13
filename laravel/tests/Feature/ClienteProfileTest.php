<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\Cliente;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Api\LegacyApiTestCase;

class ClienteProfileTest extends LegacyApiTestCase
{
    public function test_customer_can_update_contact_fields_without_changing_protected_identity_data(): void
    {
        $usuario = $this->createUsuario();
        $cliente = Cliente::query()->create([
            'nombre' => 'Cliente',
            'apellido' => 'Actual',
            'documento' => 'CC-3001',
            'telefono' => '3005550000',
            'direccion' => 'Calle 10',
            'correo' => 'cliente@example.test',
            'id_usuario' => $usuario->getKey(),
        ]);
        $contrasenaOriginal = $usuario->getRawOriginal('contrasena');

        $this->actingAs($usuario)
            ->get(route('perfil.edit'))
            ->assertOk()
            ->assertSee('Foto de perfil')
            ->assertSee('Documento')
            ->assertSee('Usuario, documento y rol no se pueden modificar.');

        $this->put(route('perfil.update'), [
            'correo' => 'contacto-nuevo@example.test',
            'telefono' => '3015550099',
            'direccion' => 'Carrera 12',
            'usuario' => 'cuenta-ajena',
            'documento' => '99999999',
            'rol' => RolUsuario::Administrador->value,
            'contrasena' => 'otra-clave',
            'nombre' => 'Nombre alterado',
            'apellido' => 'Apellido alterado',
        ])->assertRedirect(route('perfil.edit'));

        $this->assertDatabaseHas('usuario', [
            'id_usuario' => $usuario->getKey(),
            'usuario' => 'cliente',
            'correo' => 'contacto-nuevo@example.test',
            'rol' => RolUsuario::Cliente->value,
            'contrasena' => $contrasenaOriginal,
        ]);
        $this->assertDatabaseHas('cliente', [
            'id_cliente' => $cliente->getKey(),
            'nombre' => 'Cliente',
            'apellido' => 'Actual',
            'documento' => 'CC-3001',
            'telefono' => '3015550099',
            'direccion' => 'Carrera 12',
            'correo' => 'contacto-nuevo@example.test',
        ]);
    }

    public function test_customer_can_upload_and_view_a_profile_photo(): void
    {
        Storage::fake('local');
        $usuario = $this->createUsuario();
        $cliente = Cliente::query()->create([
            'nombre' => 'Cliente',
            'apellido' => 'Foto',
            'documento' => 'CC-3002',
            'correo' => 'cliente@example.test',
            'id_usuario' => $usuario->getKey(),
        ]);
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+nmYQAAAAASUVORK5CYII=', true);

        $this->actingAs($usuario)
            ->post(route('perfil.foto.update'), [
                'foto' => UploadedFile::fake()->createWithContent('avatar.png', $png),
            ])
            ->assertRedirect(route('perfil.edit'));

        Storage::disk('local')->assertExists('profile-photos/'.$cliente->getKey().'/avatar.png');
        $this->get(route('perfil.foto'))
            ->assertOk()
            ->assertHeader('content-type', 'image/png');
    }

    public function test_only_customers_can_open_the_profile_editor(): void
    {
        $tecnico = $this->createUsuario([
            'usuario' => 'tecnico',
            'correo' => 'tecnico@example.test',
            'rol' => RolUsuario::Tecnico,
        ]);

        $this->actingAs($tecnico)
            ->get(route('perfil.edit'))
            ->assertForbidden();
    }
}
