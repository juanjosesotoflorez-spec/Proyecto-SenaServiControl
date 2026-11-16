<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Servicio;

class AsignacionTecnicoController extends Controller
{
    // RF-061 a RF-065 / HU-008: Asignación y reasignación de técnico responsable con historial
    public function asignar(Request $request, $id)
    {
        $validated = $request->validate([
            'tecnico_id' => 'required|exists:users,id',
            'motivo_reasignacion' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            $servicio = Servicio::findOrFail($id);
            $tecnicoAnterior = $servicio->id_tecnico;

            // 1. Actualizar el técnico responsable en la orden de servicio
            $servicio->update([
                'id_tecnico' => $validated['tecnico_id'],
            ]);

            // 2. Registrar la traza en la tabla de historial de asignaciones (RF-061 a RF-065)
            DB::table('historial_asignacion_tecnico')->insert([
                'id_servicio' => $servicio->id,
                'tecnico_anterior' => $tecnicoAnterior,
                'tecnico_nuevo' => $validated['tecnico_id'],
                'motivo' => $validated['motivo_reasignacion'] ?? 'Asignación inicial de técnico',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Técnico asignado y registrado en el historial con éxito',
                'data' => [
                    'servicio_id' => $servicio->id,
                    'tecnico_nuevo' => $validated['tecnico_id'],
                    'tecnico_anterior' => $tecnicoAnterior,
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Error al procesar la asignación del técnico: ' . $e->getMessage()
            ], 422);
        }
    }
}