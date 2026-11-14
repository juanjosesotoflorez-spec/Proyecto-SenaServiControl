<?php

namespace App\Http\Controllers;

use App\Enums\RolUsuario;
use App\Http\Requests\VehiculoRequest;
use App\Models\Cliente;
use App\Models\Usuario;
use App\Models\Vehiculo;
use Illuminate\Http\RedirectResponse;
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
}
