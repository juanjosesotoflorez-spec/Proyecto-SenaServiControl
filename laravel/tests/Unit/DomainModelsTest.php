<?php

namespace Tests\Unit;

use App\Enums\EstadoServicioEnum;
use App\Enums\EstadoUsuario;
use App\Enums\RolUsuario;
use App\Models\Administrador;
use App\Models\Cliente;
use App\Models\ConfirmacionNotificacion;
use App\Models\DetalleReparacion;
use App\Models\EntregaVehiculo;
use App\Models\HistorialActividadUsuario;
use App\Models\HistorialAsignacionTecnico;
use App\Models\Incidencia;
use App\Models\Mantenimiento;
use App\Models\Notificacion;
use App\Models\Recepcionista;
use App\Models\Reparacion;
use App\Models\Repuesto;
use App\Models\Seguimiento;
use App\Models\Servicio;
use App\Models\SolicitudServicio;
use App\Models\Tecnico;
use App\Models\Usuario;
use App\Models\Vehiculo;
use App\Models\VistaInventarioRepuestos;
use App\Models\VistaSeguimientoServicios;
use App\Models\VistaServicios;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DomainModelsTest extends TestCase
{
    #[DataProvider('legacyTables')]
    public function test_legacy_model_maps_to_its_existing_table_and_key(
        string $modelClass,
        string $table,
        string $primaryKey
    ): void {
        $model = new $modelClass;

        self::assertSame($table, $model->getTable());
        self::assertSame($primaryKey, $model->getKeyName());
        self::assertFalse($model->usesTimestamps());
    }

    public function test_database_enum_values_match_the_legacy_schema(): void
    {
        self::assertSame(
            ['administrador', 'recepcionista', 'cliente', 'tecnico'],
            array_column(RolUsuario::cases(), 'value')
        );
        self::assertSame(['activo', 'inactivo'], array_column(EstadoUsuario::cases(), 'value'));
        self::assertSame(
            ['Pendiente', 'En proceso', 'Finalizado', 'Entregado'],
            array_column(EstadoServicioEnum::cases(), 'value')
        );
    }

    public function test_service_status_is_cast_to_its_enum(): void
    {
        $service = new Servicio(['estado' => EstadoServicioEnum::EnProceso->value]);

        self::assertSame(EstadoServicioEnum::EnProceso, $service->estado);
    }

    public function test_service_status_enum_exposes_dynamic_badge_colors(): void
    {
        self::assertSame('bg-warning text-dark', EstadoServicioEnum::Pendiente->badgeClass());
        self::assertSame('bg-info text-dark', EstadoServicioEnum::EnProceso->badgeClass());
        self::assertSame('bg-success', EstadoServicioEnum::Finalizado->badgeClass());
        self::assertSame('bg-success', EstadoServicioEnum::Entregado->badgeClass());
    }

    public function test_view_models_are_mapped_as_non_incrementing_read_models(): void
    {
        $views = [
            [VistaInventarioRepuestos::class, 'vista_inventario_repuestos', 'id_repuesto'],
            [VistaSeguimientoServicios::class, 'vista_seguimiento_servicios', 'id_servicio'],
            [VistaServicios::class, 'vista_servicios', 'id_servicio'],
        ];

        foreach ($views as [$modelClass, $table, $primaryKey]) {
            $model = new $modelClass;

            self::assertSame($table, $model->getTable());
            self::assertSame($primaryKey, $model->getKeyName());
            self::assertFalse($model->getIncrementing());
            self::assertFalse($model->usesTimestamps());
        }
    }

    public static function legacyTables(): array
    {
        return [
            'administrador' => [Administrador::class, 'administrador', 'id_administrador'],
            'cliente' => [Cliente::class, 'cliente', 'id_cliente'],
            'confirmacion_notificacion' => [ConfirmacionNotificacion::class, 'confirmacion_notificacion', 'id_confirmacion'],
            'detalle_reparacion' => [DetalleReparacion::class, 'detalle_reparacion', 'id_detalle'],
            'entrega_vehiculo' => [EntregaVehiculo::class, 'entrega_vehiculo', 'id_entrega'],
            'historial_actividad_usuario' => [HistorialActividadUsuario::class, 'historial_actividad_usuario', 'id_actividad'],
            'historial_asignacion_tecnico' => [HistorialAsignacionTecnico::class, 'historial_asignacion_tecnico', 'id_asignacion'],
            'incidencia' => [Incidencia::class, 'incidencia', 'id_incidencia'],
            'mantenimiento' => [Mantenimiento::class, 'mantenimiento', 'id_mantenimiento'],
            'notificacion' => [Notificacion::class, 'notificacion', 'id_notificacion'],
            'recepcionista' => [Recepcionista::class, 'recepcionista', 'id_recepcionista'],
            'reparacion' => [Reparacion::class, 'reparacion', 'id_reparacion'],
            'repuesto' => [Repuesto::class, 'repuesto', 'id_repuesto'],
            'seguimiento' => [Seguimiento::class, 'seguimiento', 'id_seguimiento'],
            'servicio' => [Servicio::class, 'servicio', 'id_servicio'],
            'solicitud_servicio' => [SolicitudServicio::class, 'solicitud_servicio', 'id_solicitud'],
            'tecnico' => [Tecnico::class, 'tecnico', 'id_tecnico'],
            'usuario' => [Usuario::class, 'usuario', 'id_usuario'],
            'vehiculo' => [Vehiculo::class, 'vehiculo', 'id_vehiculo'],
        ];
    }
}
