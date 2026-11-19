<?php

namespace App\Http\Controllers;

use App\Enums\EstadoUsuario;
use App\Enums\RolUsuario;
use App\Http\Requests\UpdateAdminUsuarioRequest;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UsuarioAdminController extends Controller
{
    private const PERFIL_POR_ROL = [
        'administrador' => 'administrador',
        'recepcionista' => 'recepcionista',
        'tecnico' => 'tecnico',
        'cliente' => 'cliente',
    ];

    public function index(Request $request): View
    {
        $termino = trim((string) $request->query('q', ''));
        $usuarios = Usuario::query()->when($termino !== '', function (Builder $query) use ($termino): void {
            $coincidencia = '%'.$termino.'%';
            $query->where(function (Builder $query) use ($coincidencia): void {
                $query->where('usuario', 'like', $coincidencia)
                    ->orWhere('correo', 'like', $coincidencia)
                    ->orWhere('rol', 'like', $coincidencia)
                    ->orWhere('estado', 'like', $coincidencia);
            });
        })
            ->orderBy('usuario')
            ->paginate(25)
            ->withQueryString();

        return view('usuarios.index', compact('usuarios', 'termino'));
    }

    public function edit(Usuario $usuario): View
    {
        return view('usuarios.edit', [
            'usuario' => $usuario,
            'roles' => RolUsuario::cases(),
            'estados' => EstadoUsuario::cases(),
        ]);
    }

    public function update(UpdateAdminUsuarioRequest $request, Usuario $usuario): RedirectResponse
    {
        $datos = $request->validated();
        $administradorActual = $request->user();

        DB::transaction(function () use ($usuario, $datos, $administradorActual): void {
            $usuario = Usuario::query()->whereKey($usuario->getKey())->lockForUpdate()->firstOrFail();
            $nuevoRol = RolUsuario::from($datos['rol']);
            $nuevoEstado = EstadoUsuario::from($datos['estado']);

            if (
                (int) $usuario->getKey() === (int) $administradorActual->getKey()
                && ($nuevoRol !== $usuario->rol || $nuevoEstado !== $usuario->estado)
            ) {
                throw ValidationException::withMessages([
                    'estado' => 'No puedes cambiar tu propio rol ni desactivar tu cuenta.',
                ]);
            }

            if ($nuevoRol !== $usuario->rol && ! $this->hasOnlyTargetProfile($usuario, $nuevoRol)) {
                throw ValidationException::withMessages([
                    'rol' => 'El rol no se puede cambiar porque no existe una ficha exclusiva para el nuevo rol. Conserva el rol actual para mantener el perfil y su historial.',
                ]);
            }

            if (
                $usuario->rol === RolUsuario::Administrador
                && $usuario->estado === EstadoUsuario::Activo
                && ($nuevoRol !== RolUsuario::Administrador || $nuevoEstado !== EstadoUsuario::Activo)
                && $this->activeAdministratorCount($usuario) === 0
            ) {
                throw ValidationException::withMessages([
                    'estado' => 'No puedes desactivar o cambiar el rol del último administrador activo.',
                ]);
            }

            $usuario->update($datos);
        });

        return redirect()->route('usuarios.index')->with('status', 'Cuenta actualizada correctamente.');
    }

    public function destroy(Request $request, Usuario $usuario): RedirectResponse
    {
        $administrador = $request->user();
        if ((int) $usuario->getKey() === (int) $administrador->getKey()) {
            return back()->withErrors(['usuario' => 'No puedes eliminar tu propia cuenta.']);
        }

        $eliminado = DB::transaction(function () use ($usuario): bool {
            $usuario = Usuario::query()->whereKey($usuario->getKey())->lockForUpdate()->firstOrFail();

            if ($usuario->rol === RolUsuario::Administrador && $usuario->estado === EstadoUsuario::Activo && $this->activeAdministratorCount($usuario) === 0) {
                return false;
            }

            if ($this->hasAnyProfile($usuario) || $this->hasOperationalHistory($usuario)) {
                return false;
            }

            $usuario->delete();

            return true;
        });

        if (! $eliminado) {
            return back()->withErrors([
                'usuario' => 'No se eliminó la cuenta: conserva un perfil, historial o es el último administrador activo. Puedes desactivarla en su lugar.',
            ]);
        }

        return redirect()->route('usuarios.index')->with('status', 'Cuenta eliminada correctamente.');
    }

    private function hasOnlyTargetProfile(Usuario $usuario, RolUsuario $targetRole): bool
    {
        $targetTable = self::PERFIL_POR_ROL[$targetRole->value];
        $targetExists = DB::table($targetTable)->where('id_usuario', $usuario->getKey())->exists();

        if (! $targetExists) {
            return false;
        }

        foreach (self::PERFIL_POR_ROL as $table) {
            if ($table !== $targetTable && DB::table($table)->where('id_usuario', $usuario->getKey())->exists()) {
                return false;
            }
        }

        return true;
    }

    private function hasAnyProfile(Usuario $usuario): bool
    {
        foreach (self::PERFIL_POR_ROL as $table) {
            if (DB::table($table)->where('id_usuario', $usuario->getKey())->exists()) {
                return true;
            }
        }

        return false;
    }

    private function hasOperationalHistory(Usuario $usuario): bool
    {
        return DB::table('historial_actividad_usuario')->where('id_usuario', $usuario->getKey())->exists()
            || DB::table('incidencia')->where('id_usuario_reporta', $usuario->getKey())->exists()
            || DB::table('incidencia')->where('id_usuario_atendio', $usuario->getKey())->exists();
    }

    private function activeAdministratorCount(Usuario $except): int
    {
        return Usuario::query()
            ->where('rol', RolUsuario::Administrador->value)
            ->where('estado', EstadoUsuario::Activo->value)
            ->where('id_usuario', '!=', $except->getKey())
            ->lockForUpdate()
            ->count();
    }
}
