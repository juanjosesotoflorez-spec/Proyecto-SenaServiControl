<?php

namespace App\Http\Controllers;

use App\Enums\RolUsuario;
use App\Http\Requests\ClienteRequest;
use App\Http\Requests\UpdateAdminClienteRequest;
use App\Models\Cliente;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ClienteController extends Controller
{
    public function index(): View
    {
        $usuario = request()->user();
        abort_unless(
            $usuario instanceof Usuario
                && in_array($usuario->rol, [RolUsuario::Administrador, RolUsuario::Recepcionista], true),
            403
        );

        return view('clientes.index', [
            'clientes' => Cliente::query()->orderBy('apellido')->orderBy('nombre')->paginate(25),
        ]);
    }

    public function store(ClienteRequest $request): RedirectResponse
    {
        Cliente::query()->create($request->validated());

        return redirect()->route('clientes.index')->with('status', 'Cliente registrado correctamente.');
    }

    public function edit(Cliente $cliente): View
    {
        return view('clientes.edit', compact('cliente'));
    }

    public function update(UpdateAdminClienteRequest $request, Cliente $cliente): RedirectResponse
    {
        $datos = $request->validated();

        DB::transaction(function () use ($cliente, $datos): void {
            $cliente->update($datos);
            $cliente->usuario?->update(['correo' => $datos['correo']]);
        });

        return redirect()->route('clientes.index')->with('status', 'Cliente actualizado correctamente.');
    }

    public function destroy(Cliente $cliente): RedirectResponse
    {
        if (
            $cliente->vehiculos()->exists()
            || $cliente->notificaciones()->exists()
            || $cliente->usuario()->exists()
        ) {
            return back()->withErrors([
                'cliente' => 'No se puede eliminar este cliente porque tiene vehículos, cuenta de acceso o notificaciones asociadas. Conserva su historial y desactiva su cuenta si corresponde.',
            ]);
        }

        $cliente->delete();

        return redirect()->route('clientes.index')->with('status', 'Cliente eliminado correctamente.');
    }
}
