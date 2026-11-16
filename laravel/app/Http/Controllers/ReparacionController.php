<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Servicio;
use App\Enums\EstadoServicioEnum;

class ReparacionController extends Controller
{
    // HU-007: Consultar el inventario de repuestos desde la vista de base de datos
    public function inventarioRepuestos()
    {
        $repuestos = DB::table('vista_inventario_repuestos')->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Inventario de repuestos consultado correctamente',
            'data' => $repuestos
        ], 200);
    }

    // HU-007: Documentación de diagnóstico técnico, presupuesto y control de inventario
    public function storeDiagnostico(Request $request, $id)
    {
        $validated = $request->validate([
            'diagnostico_tecnico' => 'required|string',
            'costo_estimado' => 'required|numeric|min:0',
            'repuestos' => 'required|array',
            'repuestos.*.id_repuesto' => 'required|integer',
            'repuestos.*.cantidad' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            $servicio = Servicio::findOrFail($id);

            // 1. Actualizar el diagnóstico y el presupuesto en la orden de servicio
            $servicio->update([
                'diagnostico_tecnico' => $validated['diagnostico_tecnico'],
                'costo' => $validated['costo_estimado'],
                'estado' => EstadoServicioEnum::EnProceso->value,
            ]);

            // 2. Control de inventario utilizando la vista_inventario_repuestos
            foreach ($validated['repuestos'] as $item) {
                $repuestoStock = DB::table('vista_inventario_repuestos')
                    ->where('id', $item['id_repuesto'])
                    ->first();

                if ($repuestoStock && isset($repuestoStock->stock_actual) && $repuestoStock->stock_actual < $item['cantidad']) {
                    throw new \Exception("Stock insuficiente para el repuesto ID: {$item['id_repuesto']}");
                }

                // Registrar el detalle de los repuestos consumidos en la reparación
                DB::table('detalle_reparacion')->insert([
                    'id_servicio' => $servicio->id,
                    'id_repuesto' => $item['id_repuesto'],
                    'cantidad' => $item['cantidad'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Diagnóstico, presupuesto y control de inventario registrados con éxito',
                'data' => $servicio
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Error en el proceso de diagnóstico: ' . $e->getMessage()
            ], 422);
        }
    }
}