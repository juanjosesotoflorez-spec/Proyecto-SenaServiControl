<?php

namespace App\Http\Controllers;

use App\Enums\RolUsuario;
use App\Http\Requests\ClienteRequest;
use App\Models\Cliente;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
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
}
