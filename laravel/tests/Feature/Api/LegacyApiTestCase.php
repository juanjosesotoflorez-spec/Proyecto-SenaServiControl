<?php

namespace Tests\Feature\Api;

use App\Enums\EstadoUsuario;
use App\Enums\RolUsuario;
use App\Models\Usuario;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

abstract class LegacyApiTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'jwt.secret' => str_repeat('api-test-secret-', 4),
        ]);
        DB::purge('sqlite');

        Schema::connection('sqlite')->create('usuario', function (Blueprint $table): void {
            $table->increments('id_usuario');
            $table->string('usuario', 50);
            $table->string('correo', 100)->unique();
            $table->string('contrasena', 255);
            $table->string('rol', 30);
            $table->string('estado', 30)->nullable();
        });

        Schema::connection('sqlite')->create('cliente', function (Blueprint $table): void {
            $table->increments('id_cliente');
            $table->string('nombre', 50);
            $table->string('apellido', 50);
            $table->string('documento', 20)->unique();
            $table->string('telefono', 20)->nullable();
            $table->string('direccion', 100)->nullable();
            $table->string('correo', 100)->nullable();
            $table->unsignedInteger('id_usuario')->nullable()->unique();
        });

        Schema::connection('sqlite')->create('vehiculo', function (Blueprint $table): void {
            $table->increments('id_vehiculo');
            $table->string('placa', 10)->unique();
            $table->string('marca', 50)->nullable();
            $table->string('modelo', 50)->nullable();
            $table->string('color', 30)->nullable();
            $table->unsignedInteger('anio')->nullable();
            $table->unsignedInteger('kilometraje')->nullable();
            $table->unsignedInteger('id_cliente');
        });
    }

    protected function createUsuario(array $attributes = []): Usuario
    {
        return Usuario::query()->create(array_merge([
            'usuario' => 'cliente',
            'correo' => 'cliente@example.test',
            'contrasena' => 'clave-segura-123',
            'rol' => RolUsuario::Cliente,
            'estado' => EstadoUsuario::Activo,
        ], $attributes));
    }
}
