<?php

namespace App\Http\Controllers;

use App\Http\Requests\BusquedaFiltroRequest;
use App\Models\Servicio;
use Illuminate\Support\Facades\Auth;

class HistorialOperacionesController extends Controller
{
    public function index(BusquedaFiltroRequest $request)
    {
        $user = Auth::user();
        $rol = $user->rol->value ?? $user->rol;
        $query = Servicio::with(['vehiculo.cliente', 'tecnico']);

        if ($rol === 'cliente') {
            $query->whereHas('vehiculo.cliente', fn($q) => $q->where('id_usuario', $user->id_usuario ?? $user->id));
        } elseif ($rol === 'tecnico') {
            $query->where('id_tecnico', $user->id_usuario ?? $user->id);
        }

        if ($request->filled('placa')) {
            $query->whereHas('vehiculo', fn($q) => $q->where('placa', 'like', "%{$request->placa}%"));
        }
        if ($request->filled('documento')) {
            $query->whereHas('vehiculo.cliente', fn($q) => $q->where('documento', 'like', "%{$request->documento}%"));
        }
        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_ingreso', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_ingreso', '<=', $request->fecha_hasta);
        }

        $operaciones = $query->orderByDesc('fecha_ingreso')->paginate(20)->withQueryString();
        return view('historial.cronologico', compact('operaciones'));
    }
}