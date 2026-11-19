<?php

namespace App\Http\Controllers;

use App\Enums\RolUsuario;
use App\Http\Requests\VehiculoRequest;
use App\Http\Requests\UpdateAdminVehiculoRequest;
use App\Models\Cliente;
use App\Models\Usuario;
use App\Models\Vehiculo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VehiculoController extends Controller
{
    public function index(): View
    {
        $usuario = request()->user();
        abort_unless($usuario instanceof Usuario, 403);
        abort_unless(
            in_array($usuario->rol, [RolUsuario::Administrador, RolUsuario::Recepcionista, RolUsuario::Cliente], true),
            403
        );

        $vehiculos = Vehiculo::query()->with('cliente');

        if ($usuario->rol === RolUsuario::Cliente) {
            $cliente = Cliente::query()->where('id_usuario', $usuario->getKey())->firstOrFail();
            $vehiculos->where('id_cliente', $cliente->getKey());
        }

        return view('vehiculos.index', [
            'vehiculos' => $vehiculos->orderBy('placa')->paginate(25),
            'usuario' => $usuario,
            'clientes' => in_array($usuario->rol, [RolUsuario::Administrador, RolUsuario::Recepcionista], true)
                ? Cliente::query()->orderBy('apellido')->orderBy('nombre')->get()
                : collect(),
        ]);
    }

    public function store(VehiculoRequest $request): RedirectResponse
    {
        $usuario = $request->user();
        abort_unless($usuario instanceof Usuario, 403);

        $data = $request->validated();
        if ($usuario->rol === RolUsuario::Cliente) {
            $cliente = Cliente::query()->where('id_usuario', $usuario->getKey())->firstOrFail();
            $data['id_cliente'] = $cliente->getKey();
        }

        Vehiculo::query()->create($data);

        return redirect()->route('vehiculos.index')->with('status', 'Vehículo registrado correctamente.');
    }

    public function edit(Vehiculo $vehiculo): View
    {
        return view('vehiculos.edit', [
            'vehiculo' => $vehiculo->load('cliente'),
            'clientes' => Cliente::query()->orderBy('apellido')->orderBy('nombre')->get(),
        ]);
    }

    public function update(UpdateAdminVehiculoRequest $request, Vehiculo $vehiculo): RedirectResponse
    {
        $datos = $request->validated();
        $cambiaPropietario = (int) $datos['id_cliente'] !== (int) $vehiculo->id_cliente;

        if (
            $cambiaPropietario
            && ($vehiculo->servicios()->exists() || $vehiculo->solicitudesServicio()->exists())
        ) {
            throw ValidationException::withMessages([
                'id_cliente' => 'No se puede cambiar el propietario de un vehículo con servicios o solicitudes asociadas, porque afectaría su historial.',
            ]);
        }

        $vehiculo->update($datos);

        return redirect()->route('vehiculos.index')->with('status', 'Vehículo actualizado correctamente.');
    }

    public function destroy(Vehiculo $vehiculo): RedirectResponse
    {
        if ($vehiculo->servicios()->exists() || $vehiculo->solicitudesServicio()->exists()) {
            return back()->withErrors([
                'vehiculo' => 'No se puede eliminar este vehículo porque tiene servicios o solicitudes asociadas. El historial debe conservarse.',
            ]);
        }

        $vehiculo->delete();

        return redirect()->route('vehiculos.index')->with('status', 'Vehículo eliminado correctamente.');
    }
}
