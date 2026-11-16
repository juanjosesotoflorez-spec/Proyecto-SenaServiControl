<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReparacionController extends Controller
{
    // HU-007: Documentación de diagnóstico técnico y solución
    public function storeDiagnostico(Request $request, $id)
    {
        $validated = $request->validate([
            'diagnostico_tecnico' => 'required|string',
            'repuestos_necesarios' => 'required|array',
            'costo_estimado' => 'required|numeric|min:0',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Diagnóstico y presupuesto registrados correctamente',
            'orden_id' => $id,
            'data' => $validated
        ], 200);
    }
}