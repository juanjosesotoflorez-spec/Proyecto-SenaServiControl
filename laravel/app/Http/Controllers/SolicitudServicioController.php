<?php

namespace App\Http\Controllers;

use App\Enums\EstadoServicioEnum;
use App\Enums\RolUsuario;
use App\Http\Requests\AcceptServiceRequest;
use App\Http\Requests\StoreSolicitudServicioRequest;
use App\Models\Cliente;
use App\Models\HistorialActividadUsuario;
use App\Models\HistorialAsignacionTecnico;
use App\Models\Notificacion;
use App\Models\Seguimiento;
use App\Models\Servicio;
use App\Models\SolicitudServicio;
use App\Models\Tecnico;
use App\Models\Usuario;
use App\Models\Vehiculo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SolicitudServicioController extends Controller
{
    public function index(): View
    {
        $usuario = request()->user();
        abort_unless($usuario instanceof Usuario, 403);

        $esCliente = $usuario->rol === RolUsuario::Cliente;
        $esTecnico = $usuario->rol === RolUsuario::Tecnico;
        abort_unless(
            $esCliente || $esTecnico || in_array($usuario->rol, [RolUsuario::Administrador, RolUsuario::Recepcionista], true),
            403
        );

        $solicitudes = SolicitudServicio::query()->with(['vehiculo.cliente', 'servicio']);
        $vehiculos = collect();

        if ($esCliente) {
            $cliente = Cliente::query()->where('id_usuario', $usuario->getKey())->firstOrFail();
            $solicitudes->whereHas('vehiculo', fn ($query) => $query->where('id_cliente', $cliente->getKey()));
            $vehiculos = Vehiculo::query()->where('id_cliente', $cliente->getKey())->orderBy('placa')->get();
        } elseif ($esTecnico) {
            $solicitudes->where('estado', 'Pendiente')->whereNull('id_servicio');
        }

        return view('solicitudes.index', [
            'usuario' => $usuario,
            'esCliente' => $esCliente,
            'esTecnico' => $esTecnico,
            'vehiculos' => $vehiculos,
            'solicitudes' => $solicitudes
                ->orderByRaw("CASE WHEN estado = 'Pendiente' AND id_servicio IS NULL THEN 0 ELSE 1 END")
                ->orderByDesc('fecha_solicitud')
                ->paginate(25)
                ->withQueryString(),
        ]);
    }

    public function store(StoreSolicitudServicioRequest $request): RedirectResponse
    {
        $usuario = $request->user();
        abort_unless($usuario instanceof Usuario, 403);
        $datos = $request->validated();
        $cliente = Cliente::query()->where('id_usuario', $usuario->getKey())->firstOrFail();

        DB::transaction(function () use ($datos, $usuario, $cliente): void {
            $solicitud = SolicitudServicio::query()->create([
                'id_vehiculo' => $datos['id_vehiculo'],
                'tipo_servicio' => $datos['tipo_servicio'],
                'descripcion' => $datos['descripcion'],
                'fecha_solicitada' => $datos['fecha_solicitada'],
                'estado' => 'Pendiente',
            ]);

            HistorialActividadUsuario::query()->create([
                'id_usuario' => $usuario->getKey(),
                'entidad' => 'solicitud_servicio',
                'id_registro' => $solicitud->getKey(),
                'accion' => 'solicitud_servicio_creada',
                'descripcion' => 'El cliente solicitó una cita o servicio para su vehículo.',
                'fecha_hora' => now(),
            ]);
        });

        return redirect()->route('solicitudes.index')->with('status', 'Solicitud enviada. Un técnico revisará y aceptará el servicio.');
    }

    public function accept(AcceptServiceRequest $request, SolicitudServicio $solicitud): RedirectResponse
    {
        $usuario = $request->user();
        abort_unless($usuario instanceof Usuario, 403);
        $tecnico = Tecnico::query()->where('id_usuario', $usuario->getKey())->firstOrFail();

        $servicio = DB::transaction(function () use ($solicitud, $usuario, $tecnico): Servicio {
            $solicitud = SolicitudServicio::query()
                ->whereKey($solicitud->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($solicitud->estado !== 'Pendiente' || $solicitud->id_servicio !== null) {
                throw ValidationException::withMessages([
                    'solicitud' => 'Esta solicitud ya fue atendida o dejó de estar disponible.',
                ]);
            }

            $vehiculo = Vehiculo::query()->with('cliente')->findOrFail($solicitud->id_vehiculo);
            $estado = EstadoServicioEnum::Pendiente;
            $fechaSolicitada = (string) $solicitud->getRawOriginal('fecha_solicitada');
            $servicio = Servicio::query()->create([
                'tipo_servicio' => $solicitud->tipo_servicio,
                'descripcion' => $solicitud->descripcion,
                'fecha_ingreso' => today(),
                'fecha_cita' => $fechaSolicitada,
                'estado' => $estado,
                'costo' => 0,
                'id_vehiculo' => $vehiculo->getKey(),
                'id_tecnico' => $tecnico->getKey(),
                'id_recepcionista' => null,
            ]);

            HistorialAsignacionTecnico::query()->create([
                'id_servicio' => $servicio->getKey(),
                'id_tecnico_anterior' => null,
                'id_tecnico_nuevo' => $tecnico->getKey(),
                'id_recepcionista' => null,
                'motivo' => 'Servicio creado al aceptar la solicitud del cliente #'.$solicitud->getKey().'.',
                'estado_aceptacion' => 'Aceptada',
                'fecha_aceptacion' => now(),
            ]);

            Seguimiento::query()->create([
                'fecha' => today(),
                'fecha_hora' => now(),
                'estado' => $estado->value,
                'comentario' => 'Solicitud aceptada. Fecha preferida por el cliente: '.$fechaSolicitada.'. '.$solicitud->descripcion,
                'id_servicio' => $servicio->getKey(),
                'id_tecnico' => null,
                'aprendiz' => '',
            ]);

            $solicitud->update([
                'estado' => 'Aceptada',
                'id_servicio' => $servicio->getKey(),
            ]);

            Notificacion::query()->create([
                'mensaje' => 'Tu solicitud #'.$solicitud->getKey().' fue aceptada como servicio #'.$servicio->getKey().'. Fecha preferida: '.$fechaSolicitada.'.',
                'fecha_envio' => now(),
                'estado' => 'Interna',
                'id_cliente' => $vehiculo->cliente->getKey(),
                'id_servicio' => $servicio->getKey(),
            ]);

            HistorialActividadUsuario::query()->create([
                'id_usuario' => $usuario->getKey(),
                'id_servicio' => $servicio->getKey(),
                'entidad' => 'solicitud_servicio',
                'id_registro' => $solicitud->getKey(),
                'accion' => 'solicitud_servicio_aceptada',
                'descripcion' => 'El técnico aceptó la solicitud del cliente y creó una orden de servicio.',
                'fecha_hora' => now(),
            ]);

            return $servicio;
        });

        return redirect()->route('servicios.index')->with('status', 'Solicitud aceptada y convertida en servicio #'.$servicio->getKey().'.');
    }
}