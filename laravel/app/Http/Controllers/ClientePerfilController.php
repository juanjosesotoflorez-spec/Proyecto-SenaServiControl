<?php

namespace App\Http\Controllers;

use App\Enums\RolUsuario;
use App\Http\Requests\Api\UpdateContactRequest;
use App\Http\Requests\UpdateClientePhotoRequest;
use App\Models\Cliente;
use App\Models\Usuario;
use App\Services\UpdateClienteContact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class ClientePerfilController extends Controller
{
    public function edit(): View
    {
        $cliente = $this->cliente(request()->user());

        return view('perfil.edit', [
            'usuario' => request()->user(),
            'cliente' => $cliente,
        ]);
    }

    public function update(UpdateContactRequest $request, UpdateClienteContact $updater): RedirectResponse
    {
        $usuario = $request->user();
        abort_unless($usuario instanceof Usuario, 403);

        $updater->handle($usuario, $request->validated());

        return redirect()->route('perfil.edit')->with('status', 'Los datos de contacto se actualizaron correctamente.');
    }

    public function updatePhoto(UpdateClientePhotoRequest $request): RedirectResponse
    {
        $cliente = $this->cliente($request->user());
        $disk = Storage::disk('local');
        $directory = 'profile-photos/'.$cliente->getKey();
        $path = $request->file('foto')->storeAs(
            $directory,
            'avatar.'.$request->file('foto')->extension(),
            'local'
        );

        if (! $path) {
            throw new RuntimeException('No se pudo guardar la foto de perfil.');
        }

        foreach ($disk->directoryExists($directory) ? $disk->files($directory) : [] as $existingPath) {
            if ($existingPath !== $path) {
                $disk->delete($existingPath);
            }
        }

        return redirect()->route('perfil.edit')->with('status', 'La foto de perfil se actualizó correctamente.');
    }

    public function photo(): Response
    {
        $cliente = $this->cliente(request()->user());
        $disk = Storage::disk('local');
        $directory = 'profile-photos/'.$cliente->getKey();
        $photoPath = collect($disk->directoryExists($directory) ? $disk->files($directory) : [])
            ->first(fn (string $path): bool => preg_match('/^avatar\.(jpe?g|png|webp)$/i', basename($path)) === 1);

        if (! $photoPath) {
            return response(
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 96 96"><rect width="96" height="96" rx="48" fill="#edf7e7"/><circle cx="48" cy="35" r="17" fill="#39a900"/><path d="M17 86c3-18 15-27 31-27s28 9 31 27" fill="#39a900"/></svg>',
                200,
                ['Content-Type' => 'image/svg+xml', 'Cache-Control' => 'private, no-store']
            );
        }

        return $disk->response($photoPath, null, ['Cache-Control' => 'private, no-store']);
    }

    private function cliente(mixed $usuario): Cliente
    {
        abort_unless($usuario instanceof Usuario && $usuario->rol === RolUsuario::Cliente, 403);

        return Cliente::query()->where('id_usuario', $usuario->getKey())->firstOrFail();
    }
}
