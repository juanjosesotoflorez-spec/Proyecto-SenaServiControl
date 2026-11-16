<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AsignacionTecnicoController extends Controller
{
    // HU-008: Asignación y reasignación de técnico responsable
    public function asignar(Request $request, $id)
    {
        $validated = $request->validate([
            'tecnico_id' => 'required|exists:users,id',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Técnico asignado exitosamente al servicio',
            'servicio_id' => $id,
            'tecnico_id' => $validated['tecnico_id']
        ], 200);
    }
}