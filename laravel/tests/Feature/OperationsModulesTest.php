<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\Servicio;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Api\LegacyApiTestCase;

class OperationsModulesTest extends LegacyApiTestCase
{
    private int $recordSequence = 0;

    public function test_administrator_can_download_a_client_summary_pdf(): void
    {
        $this->createOperationsSchema();
        $this->createService('Finalizado', now()->toDateString());
        $administrator = $this->createUsuario(['rol' => RolUsuario::Administrador]);

        $response = $this->actingAs($administrator)->get(route('reportes.pdf'));

        $response->assertOk();
        self::assertStringStartsWith('%PDF-', $response->getContent());
        self::assertStringContainsString('attachment;', (string) $response->headers->get('content-disposition'));

        $html = view('reportes.pdf', [
            'servicios' => Servicio::query()->with('vehiculo.cliente')->get(),
            'generadoEn' => now(),
        ])->render();

        self::assertStringContainsString('Cliente y contacto', $html);
        self::assertStringContainsString('Documento:', $html);
        self::assertStringContainsString('ana1@example.test', $html);
        self::assertStringContainsString('Servicio realizado', $html);
        self::assertStringContainsString('Valor total:', $html);
        self::assertStringNotContainsString('Técnico', $html);
        self::assertStringNotContainsString('id_servicio', $html);
    }

    public function test_receptionist_can_view_reports_and_print_the_service_report_pdf(): void
    {
        $this->createOperationsSchema();
        $this->createService('En proceso', now()->toDateString());
        $receptionist = $this->createUsuario([
            'usuario' => 'recepcionista',
            'correo' => 'recepcionista@example.test',
            'rol' => RolUsuario::Recepcionista,
        ]);

        $this->actingAs($receptionist)
            ->get(route('reportes.index'))
            ->assertOk()
            ->assertSee(route('reportes.pdf'))
            ->assertSee(route('reportes.pdf.print'));

        $download = $this->get(route('reportes.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        self::assertStringContainsString('attachment;', (string) $download->headers->get('content-disposition'));

        $print = $this->get(route('reportes.pdf.print'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        self::assertStringContainsString('inline;', (string) $print->headers->get('content-disposition'));
    }

    public function test_receptionist_can_open_service_details_with_client_and_vehicle_history(): void
    {
        $this->createOperationsSchema();
        $serviceId = $this->createService('En proceso', now()->toDateString());
        $receptionist = $this->createUsuario([
            'usuario' => 'recepcionista',
            'correo' => 'recepcionista@example.test',
            'rol' => RolUsuario::Recepcionista,
        ]);
        DB::table('solicitud_servicio')->insert([
            'id_vehiculo' => DB::table('servicio')->where('id_servicio', $serviceId)->value('id_vehiculo'),
            'tipo_servicio' => 'Mantenimiento',
            'descripcion' => 'Revisión general',
            'fecha_solicitada' => now()->toDateString(),
            'estado' => 'Aceptada',
            'fecha_solicitud' => now(),
            'id_servicio' => $serviceId,
        ]);

        $this->actingAs($receptionist)
            ->get(route('solicitudes.index'))
            ->assertOk()
            ->assertSee(route('servicios.show', $serviceId));

        $this->actingAs($receptionist)
            ->get(route('servicios.show', $serviceId))
            ->assertOk()
            ->assertSee('Información del cliente')
            ->assertSee('Ana Cliente')
            ->assertSee('ana1@example.test')
            ->assertSee('9000000001')
            ->assertSee('3005550011')
            ->assertSee('ABC001')
            ->assertSee('Toyota')
            ->assertSee('Historial del cliente')
            ->assertSee('Servicio #'.$serviceId);
    }

    public function test_customer_cannot_open_another_customers_service_details(): void
    {
        $this->createOperationsSchema();
        $serviceId = $this->createService('En proceso', now()->toDateString());
        $customer = $this->createUsuario();
        $otherClientId = DB::table('cliente')->insertGetId([
            'nombre' => 'Otro',
            'apellido' => 'Cliente',
            'documento' => '9000000099',
        ]);
        DB::table('cliente')->where('id_cliente', $otherClientId)->update([
            'id_usuario' => $customer->getKey(),
        ]);

        $this->actingAs($customer)
            ->get(route('servicios.show', $serviceId))
            ->assertForbidden();
    }

    public function test_technician_can_view_only_their_assigned_service_details(): void
    {
        $this->createOperationsSchema();
        $serviceId = $this->createService('En proceso', now()->toDateString());
        $technicianId = (int) DB::table('servicio')
            ->where('id_servicio', $serviceId)
            ->value('id_tecnico');
        $technician = $this->createUsuario([
            'usuario' => 'tecnico',
            'correo' => 'tecnico@example.test',
            'rol' => RolUsuario::Tecnico,
        ]);
        DB::table('tecnico')->where('id_tecnico', $technicianId)->update([
            'id_usuario' => $technician->getKey(),
        ]);

        $this->actingAs($technician)
            ->get(route('servicios.show', $serviceId))
            ->assertOk()
            ->assertSee('Información del cliente')
            ->assertDontSee('Historial del cliente');
    }

    public function test_administrator_can_record_vehicle_delivery_and_service_state(): void
    {
        $this->createOperationsSchema();
        $serviceId = $this->createService('Finalizado', now()->toDateString());
        $administrator = $this->createUsuario(['rol' => RolUsuario::Administrador]);
        $receptionistId = $this->createReceptionist();

        $this->actingAs($administrator)
            ->post(route('servicios.delivery.store', $serviceId), [
                'id_recepcionista' => $receptionistId,
            ])
            ->assertRedirect(route('servicios.index'))
            ->assertSessionHas('status', 'Entrega registrada correctamente.');

        $this->assertDatabaseHas('servicio', [
            'id_servicio' => $serviceId,
            'estado' => 'Entregado',
        ]);
        $this->assertDatabaseHas('entrega_vehiculo', [
            'id_servicio' => $serviceId,
            'id_recepcionista' => $receptionistId,
            'estado_confirmacion_cliente' => 'Pendiente',
        ]);
        $this->assertDatabaseHas('historial_actividad_usuario', [
            'id_usuario' => $administrator->getKey(),
            'id_servicio' => $serviceId,
            'accion' => 'entrega_registrada',
        ]);

        $this->get(route('servicios.index'))
            ->assertOk()
            ->assertSee('Registrada')
            ->assertSee('Rosa Recepción');
    }

    public function test_receptionist_can_record_delivery_as_their_own_profile(): void
    {
        $this->createOperationsSchema();
        $serviceId = $this->createService('Finalizado', now()->toDateString());
        $receptionist = $this->createUsuario([
            'usuario' => 'recepcionista',
            'correo' => 'recepcionista@example.test',
            'rol' => RolUsuario::Recepcionista,
        ]);
        $receptionistId = $this->createReceptionist($receptionist->getKey());

        $this->actingAs($receptionist)
            ->post(route('servicios.delivery.store', $serviceId))
            ->assertRedirect(route('servicios.index'));

        $this->assertDatabaseHas('entrega_vehiculo', [
            'id_servicio' => $serviceId,
            'id_recepcionista' => $receptionistId,
        ]);
        $this->assertDatabaseHas('servicio', [
            'id_servicio' => $serviceId,
            'estado' => 'Entregado',
        ]);

        $this->get(route('servicios.index'))
            ->assertOk()
            ->assertSee('Registrada')
            ->assertSee('Rosa Recepción');
    }

    public function test_only_administrators_and_receptionists_can_register_deliveries(): void
    {
        $this->createOperationsSchema();
        $serviceId = $this->createService('Finalizado', now()->toDateString());
        $customer = $this->createUsuario();

        $this->actingAs($customer)
            ->post(route('servicios.delivery.store', $serviceId))
            ->assertForbidden();

        $this->assertDatabaseCount('entrega_vehiculo', 0);
    }

    public function test_delivery_cannot_be_registered_before_a_service_is_finalized(): void
    {
        $this->createOperationsSchema();
        $serviceId = $this->createService('En proceso', now()->toDateString());
        $administrator = $this->createUsuario(['rol' => RolUsuario::Administrador]);
        $receptionistId = $this->createReceptionist();

        $this->actingAs($administrator)
            ->from(route('servicios.index'))
            ->post(route('servicios.delivery.store', $serviceId), [
                'id_recepcionista' => $receptionistId,
            ])
            ->assertRedirect(route('servicios.index'))
            ->assertSessionHasErrors('entrega');

        $this->assertDatabaseCount('entrega_vehiculo', 0);
        $this->assertDatabaseHas('servicio', [
            'id_servicio' => $serviceId,
            'estado' => 'En proceso',
        ]);
    }

    public function test_administrator_dashboard_charts_use_real_service_aggregates(): void
    {
        $this->createOperationsSchema();
        $this->createService('Finalizado', now()->toDateString());
        $this->createService('Entregado', now()->toDateString());
        $administrator = $this->createUsuario(['rol' => RolUsuario::Administrador]);

        $this->actingAs($administrator)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-dashboard-chart="states"', false)
            ->assertSee('data-dashboard-chart="monthly"', false)
            ->assertViewHas('graficas', function (array $graficas): bool {
                return array_sum($graficas['estados']['valores']) === 2
                    && array_sum($graficas['mensual']['servicios']) === 2
                    && array_sum($graficas['mensual']['ingresos']) === 300000.0;
            });
    }

    public function test_non_administrator_dashboard_does_not_render_administrator_charts(): void
    {
        $this->createOperationsSchema();
        $receptionist = $this->createUsuario([
            'usuario' => 'recepcionista',
            'correo' => 'recepcionista@example.test',
            'rol' => RolUsuario::Recepcionista,
        ]);

        $this->actingAs($receptionist)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('data-dashboard-chart=', false)
            ->assertViewHas('graficas', null);
    }

    public function test_technician_dashboard_shows_working_income_and_client_metrics(): void
    {
        $this->createOperationsSchema();
        $assignedServiceId = $this->createService('En proceso', now()->toDateString());
        $this->createService('Finalizado', now()->toDateString());
        $technicianId = (int) DB::table('servicio')
            ->where('id_servicio', $assignedServiceId)
            ->value('id_tecnico');
        $technicianUser = $this->createUsuario([
            'usuario' => 'tecnico',
            'correo' => 'tecnico@example.test',
            'rol' => RolUsuario::Tecnico,
        ]);
        DB::table('tecnico')->where('id_tecnico', $technicianId)->update([
            'id_usuario' => $technicianUser->getKey(),
        ]);
        DB::table('historial_asignacion_tecnico')->insert([
            'id_servicio' => $assignedServiceId,
            'id_tecnico_nuevo' => $technicianId,
            'estado_aceptacion' => 'Aceptada',
        ]);

        $this->actingAs($technicianUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Ingresos de tus servicios este mes')
            ->assertSee('Clientes registrados')
            ->assertSee('$150.000')
            ->assertDontSee('Indicador disponible para administración y recepción')
            ->assertViewHas('ingresosMes', fn ($ingresos): bool => (float) $ingresos === 150000.0)
            ->assertViewHas('clientesRegistrados', 2);

        $this->get(route('servicios.index'))
            ->assertOk()
            ->assertSee('Gestión del servicio')
            ->assertSee('Actualizar proceso')
            ->assertSee('Detalle del avance')
            ->assertSee('data-bs-toggle="modal"', false)
            ->assertSee('id="service-progress-modal-'.$assignedServiceId.'"', false)
            ->assertSee('name="comentario"', false)
            ->assertDontSee('<details class="service-process"', false)
            ->assertSee('El cliente recibirá una notificación con este avance.');
    }

    public function test_invalid_technician_progress_reopens_the_matching_service_modal(): void
    {
        $this->createOperationsSchema();
        $serviceId = $this->createService('En proceso', now()->toDateString());
        $technicianId = (int) DB::table('servicio')
            ->where('id_servicio', $serviceId)
            ->value('id_tecnico');
        $technician = $this->createUsuario([
            'usuario' => 'tecnico',
            'correo' => 'tecnico@example.test',
            'rol' => RolUsuario::Tecnico,
        ]);
        DB::table('tecnico')->where('id_tecnico', $technicianId)->update([
            'id_usuario' => $technician->getKey(),
        ]);
        DB::table('historial_asignacion_tecnico')->insert([
            'id_servicio' => $serviceId,
            'id_tecnico_nuevo' => $technicianId,
            'estado_aceptacion' => 'Aceptada',
        ]);

        $this->actingAs($technician)
            ->from(route('servicios.index'))
            ->post(route('servicios.progress', $serviceId), [
                'estado' => 'En proceso',
                'comentario' => '',
                'form_service_id' => $serviceId,
            ])
            ->assertRedirect(route('servicios.index'))
            ->assertSessionHasErrors('comentario');

        $this->get(route('servicios.index'))
            ->assertOk()
            ->assertSee('data-open-service-modal="true"', false)
            ->assertSee('id="service-progress-modal-'.$serviceId.'"', false);
    }

    public function test_customer_dashboard_shows_only_their_vehicles_and_pending_requests(): void
    {
        $this->createOperationsSchema();
        $customerServiceId = $this->createService('En proceso', now()->toDateString());
        $otherServiceId = $this->createService('Pendiente', now()->toDateString());
        $customerVehicleId = (int) DB::table('servicio')
            ->where('id_servicio', $customerServiceId)
            ->value('id_vehiculo');
        $customerId = (int) DB::table('vehiculo')
            ->where('id_vehiculo', $customerVehicleId)
            ->value('id_cliente');
        $otherVehicleId = (int) DB::table('servicio')
            ->where('id_servicio', $otherServiceId)
            ->value('id_vehiculo');
        $secondCustomerVehicleId = DB::table('vehiculo')->insertGetId([
            'placa' => 'XYZ123',
            'id_cliente' => $customerId,
        ]);
        $customer = $this->createUsuario();
        DB::table('cliente')->where('id_cliente', $customerId)->update([
            'id_usuario' => $customer->getKey(),
        ]);
        DB::table('solicitud_servicio')->insert([
            [
                'id_vehiculo' => $customerVehicleId,
                'tipo_servicio' => 'Mantenimiento',
                'descripcion' => 'Solicitud pendiente del cliente',
                'fecha_solicitada' => now()->toDateString(),
                'estado' => 'Pendiente',
                'fecha_solicitud' => now(),
            ],
            [
                'id_vehiculo' => $secondCustomerVehicleId,
                'tipo_servicio' => 'Diagnostico',
                'descripcion' => 'Solicitud atendida',
                'fecha_solicitada' => now()->toDateString(),
                'estado' => 'Aceptada',
                'fecha_solicitud' => now(),
            ],
            [
                'id_vehiculo' => $otherVehicleId,
                'tipo_servicio' => 'Reparacion',
                'descripcion' => 'Solicitud de otro cliente',
                'fecha_solicitada' => now()->toDateString(),
                'estado' => 'Pendiente',
                'fecha_solicitud' => now(),
            ],
        ]);

        $this->actingAs($customer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Mis vehículos')
            ->assertSee('Solicitudes pendientes')
            ->assertDontSee('Ingresos del mes')
            ->assertDontSee('Clientes registrados')
            ->assertViewHas('misVehiculos', 2)
            ->assertViewHas('solicitudesPendientes', 1);
    }

    private function createOperationsSchema(): void
    {
        Schema::connection('sqlite')->create('servicio', function (Blueprint $table): void {
            $table->increments('id_servicio');
            $table->string('tipo_servicio', 50);
            $table->text('descripcion')->nullable();
            $table->date('fecha_ingreso');
            $table->date('fecha_entrega')->nullable();
            $table->date('fecha_entrega_estimada')->nullable();
            $table->date('fecha_entrega_real')->nullable();
            $table->string('estado', 30);
            $table->decimal('costo', 10, 2)->default(0);
            $table->unsignedInteger('id_vehiculo');
            $table->unsignedInteger('id_tecnico');
            $table->unsignedInteger('id_recepcionista')->nullable();
        });

        Schema::connection('sqlite')->create('tecnico', function (Blueprint $table): void {
            $table->increments('id_tecnico');
            $table->string('nombre', 50);
            $table->string('apellido', 50);
            $table->string('documento', 20);
            $table->string('telefono', 20)->nullable();
            $table->string('especialidad', 100)->nullable();
            $table->string('experiencia', 100)->nullable();
            $table->unsignedInteger('id_usuario')->nullable();
        });

        Schema::connection('sqlite')->create('recepcionista', function (Blueprint $table): void {
            $table->increments('id_recepcionista');
            $table->string('nombre', 50);
            $table->string('apellido', 50);
            $table->string('documento', 20);
            $table->string('telefono', 20)->nullable();
            $table->string('turno', 50)->nullable();
            $table->unsignedInteger('id_usuario')->nullable();
        });

        Schema::connection('sqlite')->create('entrega_vehiculo', function (Blueprint $table): void {
            $table->increments('id_entrega');
            $table->unsignedInteger('id_servicio')->unique();
            $table->unsignedInteger('id_recepcionista');
            $table->dateTime('fecha_registro');
            $table->dateTime('fecha_salida')->nullable();
            $table->string('estado_confirmacion_cliente', 20)->default('Pendiente');
            $table->dateTime('fecha_confirmacion_cliente')->nullable();
        });

        Schema::connection('sqlite')->create('historial_actividad_usuario', function (Blueprint $table): void {
            $table->increments('id_actividad');
            $table->unsignedInteger('id_usuario');
            $table->unsignedInteger('id_servicio')->nullable();
            $table->string('entidad', 40);
            $table->unsignedInteger('id_registro')->nullable();
            $table->string('accion', 60);
            $table->text('descripcion');
            $table->dateTime('fecha_hora');
        });

        Schema::connection('sqlite')->create('historial_asignacion_tecnico', function (Blueprint $table): void {
            $table->increments('id_asignacion');
            $table->unsignedInteger('id_servicio');
            $table->unsignedInteger('id_tecnico_nuevo')->nullable();
            $table->string('estado_aceptacion', 20)->nullable();
        });

        Schema::connection('sqlite')->create('solicitud_servicio', function (Blueprint $table): void {
            $table->increments('id_solicitud');
            $table->unsignedInteger('id_vehiculo');
            $table->string('tipo_servicio', 100);
            $table->text('descripcion');
            $table->date('fecha_solicitada');
            $table->string('estado', 20);
            $table->dateTime('fecha_solicitud');
            $table->unsignedInteger('id_servicio')->nullable();
        });

        Schema::connection('sqlite')->create('seguimiento', function (Blueprint $table): void {
            $table->increments('id_seguimiento');
            $table->date('fecha');
            $table->dateTime('fecha_hora');
            $table->string('estado', 30);
            $table->text('comentario');
            $table->unsignedInteger('id_servicio');
            $table->unsignedInteger('id_tecnico')->nullable();
            $table->string('aprendiz')->nullable();
        });

        Schema::connection('sqlite')->create('mantenimiento', function (Blueprint $table): void {
            $table->increments('id_mantenimiento');
            $table->string('tipo_mantenimiento', 100)->nullable();
            $table->text('observaciones')->nullable();
            $table->date('fecha_programacion')->nullable();
            $table->string('estado_autorizacion', 30)->nullable();
            $table->string('estado_trabajo', 30)->nullable();
            $table->dateTime('fecha_respuesta_autorizacion')->nullable();
            $table->unsignedInteger('id_servicio');
        });

        Schema::connection('sqlite')->create('reparacion', function (Blueprint $table): void {
            $table->increments('id_reparacion');
            $table->text('problema_reportado')->nullable();
            $table->text('dano_detectado')->nullable();
            $table->decimal('presupuesto', 10, 2)->nullable();
            $table->text('solucion')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('estado', 30)->nullable();
            $table->string('estado_presupuesto', 30)->nullable();
            $table->dateTime('fecha_respuesta_presupuesto')->nullable();
            $table->unsignedInteger('id_servicio');
        });
    }

    private function createService(string $status, string $date): int
    {
        $this->recordSequence++;
        $clientId = DB::table('cliente')->insertGetId([
            'nombre' => 'Ana',
            'apellido' => 'Cliente',
            'documento' => '900'.str_pad((string) $this->recordSequence, 7, '0', STR_PAD_LEFT),
            'telefono' => '3005550011',
            'direccion' => 'Calle 10 # 20-30',
            'correo' => 'ana'.$this->recordSequence.'@example.test',
        ]);
        $vehicleId = DB::table('vehiculo')->insertGetId([
            'placa' => 'ABC'.str_pad((string) $this->recordSequence, 3, '0', STR_PAD_LEFT),
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
            'color' => 'Blanco',
            'anio' => 2022,
            'kilometraje' => 25000,
            'id_cliente' => $clientId,
        ]);
        $technicianId = DB::table('tecnico')->insertGetId([
            'nombre' => 'Técnico',
            'apellido' => 'Prueba',
            'documento' => '800'.str_pad((string) $this->recordSequence, 7, '0', STR_PAD_LEFT),
        ]);

        return DB::table('servicio')->insertGetId([
            'tipo_servicio' => 'Mantenimiento',
            'descripcion' => 'Servicio de prueba',
            'fecha_ingreso' => $date,
            'estado' => $status,
            'costo' => 150000,
            'id_vehiculo' => $vehicleId,
            'id_tecnico' => $technicianId,
        ]);
    }

    private function createReceptionist(?int $userId = null): int
    {
        return DB::table('recepcionista')->insertGetId([
            'nombre' => 'Rosa',
            'apellido' => 'Recepción',
            'documento' => '700'.str_pad((string) ($this->recordSequence + 1), 7, '0', STR_PAD_LEFT),
            'id_usuario' => $userId,
        ]);
    }
}
