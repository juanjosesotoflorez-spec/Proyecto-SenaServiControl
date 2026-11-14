<?php

namespace App\Http\Controllers;

use App\Enums\EstadoServicioEnum;
use App\Enums\RolUsuario;
use App\Http\Requests\EntregaVehiculoRequest;
use App\Models\EntregaVehiculo;
use App\Models\HistorialActividadUsuario;
use App\Models\Recepcionista;
use App\Models\Servicio;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EntregaVehiculoController extends Controller
{
    public function store(EntregaVehiculoRequest $request, Servicio $servicio): RedirectResponse
    {
        $usuario = $request->user();
        abort_unless($usuario instanceof Usuario, 403);

        if ($usuario->rol === RolUsuario::Administrador) {
            $idRecepcionista = $request->validated('id_recepcionista');
        } else {
            $recepcionista = Recepcionista::query()
                ->where('id_usuario', $usuario->getKey())
                ->first();

            if (! $recepcionista) {
                throw ValidationException::withMessages([
                    'entrega' => 'Tu cuenta no tiene un perfil de recepcionista vinculado.',
                ]);
            }

            $idRecepcionista = $recepcionista->getKey();
        }

        DB::transaction(function () use ($idRecepcionista, $servicio, $usuario): void {
            $servicio = Servicio::query()
                ->whereKey($servicio->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($servicio->estado, [EstadoServicioEnum::Finalizado, EstadoServicioEnum::Entregado], true)) {
                throw ValidationException::withMessages([
                    'entrega' => 'Solo se pueden registrar entregas de servicios finalizados.',
                ]);
            }

            if (EntregaVehiculo::query()->where('id_servicio', $servicio->getKey())->exists()) {
                throw ValidationException::withMessages([
                    'entrega' => 'Este servicio ya tiene un registro de entrega.',
                ]);
            }

            $fechaSalida = now();
            $entrega = EntregaVehiculo::query()->create([
                'id_servicio' => $servicio->getKey(),
                'id_recepcionista' => $idRecepcionista,
                'fecha_registro' => $fechaSalida,
                'fecha_salida' => $fechaSalida,
            ]);

            $servicio->update([
                'estado' => EstadoServicioEnum::Entregado,
                'fecha_entrega_real' => $servicio->fecha_entrega_real ?? $fechaSalida->toDateString(),
            ]);

            HistorialActividadUsuario::query()->create([
                'id_usuario' => $usuario->getKey(),
                'id_servicio' => $servicio->getKey(),
                'entidad' => 'entrega_vehiculo',
                'id_registro' => $entrega->getKey(),
                'accion' => 'entrega_registrada',
                'descripcion' => 'Se registró la entrega del vehículo y el servicio quedó como Entregado.',
                'fecha_hora' => $fechaSalida,
            ]);
        });

        return redirect()
            ->route('servicios.index')
            ->with('status', 'Entrega registrada correctamente.');
    }
}
