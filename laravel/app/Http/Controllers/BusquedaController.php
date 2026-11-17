<?php

namespace App\Http\Controllers;

use App\Http\Requests\BusquedaFiltroRequest;
use App\Models\Cliente;
use App\Models\Vehiculo;
use App\Models\Servicio;
use Illuminate\Support\Facades\Auth;

class BusquedaController extends Controller
{
    public function clientes(BusquedaFiltroRequest $request)
    {
        $user = Auth::user();
        $rol = $user->rol->value ?? $user->rol;
        $query = Cliente::query();

        if ($rol === 'cliente') {
            $query->where('id_usuario', $user->id_usuario ?? $user->id);
        } elseif ($rol === 'tecnico') {
            $query->whereHas('vehiculos.servicios', function ($q) use ($user) {
                $q->where('id_tecnico', $user->id_usuario ?? $user->id);
            });
        }

        if ($request->filled('documento')) {
            $query->where('documento', 'like', "%{$request->documento}%");
        }
        if ($request->filled('nombre')) {
            $query->where(function ($q) use ($request) {
                $q->where('nombre', 'like', "%{$request->nombre}%")
                  ->orWhere('apellido', 'like', "%{$request->nombre}%");
            });
        }
        if ($request->filled('telefono')) {
            $query->where('telefono', 'like', "%{$request->telefono}%");
        }

        $clientes = $query->orderBy('nombre')->paginate(15)->withQueryString();
        return view('busquedas.clientes', compact('clientes'));
    }

    public function vehiculos(BusquedaFiltroRequest $request)
    {
        $user = Auth::user();
        $rol = $user->rol->value ?? $user->rol;
        $query = Vehiculo::with('cliente');

        if ($rol === 'cliente') {
            $query->whereHas('cliente', fn($q) => $q->where('id_usuario', $user->id_usuario ?? $user->id));
        } elseif ($rol === 'tecnico') {
            $query->whereHas('servicios', fn($q) => $q->where('id_tecnico', $user->id_usuario ?? $user->id));
        }

        if ($request->filled('placa')) {
            $query->where('placa', 'like', "%{$request->placa}%");
        }
        if ($request->filled('marca')) {
            $query->where('marca', 'like', "%{$request->marca}%");
        }
        if ($request->filled('modelo')) {
            $query->where('modelo', 'like', "%{$request->modelo}%");
        }

        $vehiculos = $query->orderBy('placa')->paginate(15)->withQueryString();
        return view('busquedas.vehiculos', compact('vehiculos'));
    }

    public function servicios(BusquedaFiltroRequest $request)
    {
        $user = Auth::user();
        $rol = $user->rol->value ?? $user->rol;
        $query = Servicio::with(['vehiculo.cliente', 'tecnico']);

        if ($rol === 'cliente') {
            $query->whereHas('vehiculo.cliente', fn($q) => $q->where('id_usuario', $user->id_usuario ?? $user->id));
        } elseif ($rol === 'tecnico') {
            $query->where('id_tecnico', $user->id_usuario ?? $user->id);
        }

        if ($request->filled('numero')) {
            $query->where('id_servicio', $request->numero);
        }
        if ($request->filled('placa')) {
            $query->whereHas('vehiculo', fn($q) => $q->where('placa', 'like', "%{$request->placa}%"));
        }
        if ($request->filled('nombre')) {
            $query->whereHas('vehiculo.cliente', function ($q) use ($request) {
                $q->where('nombre', 'like', "%{$request->nombre}%")
                  ->orWhere('apellido', 'like', "%{$request->nombre}%");
            });
        }
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $servicios = $query->orderByDesc('fecha_ingreso')->paginate(15)->withQueryString();
        return view('busquedas.servicios', compact('servicios'));
    }
}