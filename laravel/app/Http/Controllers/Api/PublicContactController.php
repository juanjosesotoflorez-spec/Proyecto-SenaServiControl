<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PublicContactController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $contact = $request->validate([
            'nombre' => ['required', 'string', 'max:100', 'regex:/^(?=.*\p{L})\P{N}+$/u'],
            'correo' => ['required', 'email:rfc', 'max:254'],
            'telefono' => ['nullable', 'string', 'max:30', 'regex:/^[0-9]{7,30}$/'],
            'mensaje' => ['required', 'string', 'max:5000'],
            'sitio_web' => ['nullable', 'string', 'max:0'],
        ], [
            'nombre.regex' => 'Escribe tu nombre con letras y sin números.',
            'correo.email' => 'Ingresa un correo electrónico válido.',
            'telefono.regex' => 'Ingresa entre 7 y 30 números, sin espacios ni símbolos.',
        ]);

        $smtp = config('mail.mailers.smtp');
        if (
            config('mail.default') !== 'smtp'
            || blank($smtp['username'] ?? null)
            || blank($smtp['password'] ?? null)
        ) {
            return response()->json([
                'message' => 'El correo SMTP de Gmail está incompleto. Configura el usuario y la contraseña de aplicación en laravel/.env.',
            ], 503);
        }

        Mail::to(config('mail.contact_address'))->send(new ContactMessage($contact));

        return response()->json([
            'message' => 'Tu mensaje fue enviado a la Serviteca. Gracias por contactarnos.',
        ]);
    }
}
