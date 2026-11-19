@extends('layouts.app')

@section('title', 'Administración de usuarios')
@section('page-title', 'Administración')
@section('eyebrow', 'Control de acceso')

@section('content')
    <section class="admin-console-hero">
        <div>
            <span class="admin-console-kicker"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Centro de administración</span>
            <h2>Usuarios y permisos</h2>
            <p>Gestiona el acceso al sistema y conserva la trazabilidad de cada perfil.</p>
        </div>
        <div class="admin-console-stat">
            <span>Cuentas encontradas</span>
            <strong>{{ number_format($usuarios->total()) }}</strong>
        </div>
    </section>

    <div class="admin-notice">
        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
        <p>Para conservar la operación y el historial, una cuenta con perfil asociado no se puede eliminar. Desactívala desde su edición. Los cambios de rol requieren que exista únicamente la ficha del rol de destino.</p>
    </div>

    <div class="admin-table-toolbar">
        <div><h2>Directorio de cuentas</h2><p>Busca por usuario, correo, rol o estado.</p></div>
        <form class="admin-search" method="GET" action="{{ route('usuarios.index') }}" role="search">
            <label class="visually-hidden" for="user-search">Buscar cuentas</label>
            <input class="form-control" id="user-search" name="q" type="search" value="{{ $termino }}" placeholder="Buscar usuario o correo">
            <button class="btn btn-success" type="submit" aria-label="Buscar cuentas"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></button>
        </form>
    </div>

    <div class="surface table-panel table-responsive admin-data-table">
        <table class="table table-striped">
            <thead>
                <tr><th>Cuenta</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                @forelse ($usuarios as $usuario)
                    <tr>
                        <td><strong>{{ $usuario->usuario }}</strong><small class="admin-cell-caption">ID #{{ $usuario->id_usuario }}</small></td>
                        <td>{{ $usuario->correo }}</td>
                        <td><span class="admin-role-chip">{{ ucfirst($usuario->rol->value) }}</span></td>
                        <td><span class="admin-account-status {{ $usuario->estado === \App\Enums\EstadoUsuario::Activo ? 'is-active' : 'is-inactive' }}"><span></span>{{ ucfirst($usuario->estado?->value ?? 'Sin estado') }}</span></td>
                        <td>
                            <div class="admin-row-actions">
                                <a class="btn btn-outline-secondary btn-sm" href="{{ route('usuarios.edit', $usuario) }}" aria-label="Editar cuenta {{ $usuario->usuario }}">
                                    <i class="fa-solid fa-pen" aria-hidden="true"></i><span>Administrar</span>
                                </a>
                                <form method="POST" action="{{ route('usuarios.destroy', $usuario) }}" onsubmit="return confirm('¿Eliminar esta cuenta? Solo se permite si no tiene perfil ni historial asociado.');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm" type="submit" aria-label="Eliminar cuenta {{ $usuario->usuario }}">
                                        <i class="fa-solid fa-trash" aria-hidden="true"></i><span>Eliminar</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td class="table-empty" colspan="5">No se encontraron cuentas con esos criterios.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $usuarios->links() }}
@endsection
