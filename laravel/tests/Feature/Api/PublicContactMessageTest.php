<?php

namespace Tests\Feature\Api;

use App\Mail\ContactMessage;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PublicContactMessageTest extends TestCase
{
    public function test_public_contact_form_sends_a_message_to_the_configured_address(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.contact_address' => 'senaserviteca@gmail.com',
            'mail.mailers.smtp.username' => 'senaserviteca@gmail.com',
            'mail.mailers.smtp.password' => 'test-app-password',
        ]);
        Mail::fake();

        $this->postJson('/api/contacto', $this->validContact())
            ->assertOk()
            ->assertJsonPath('message', 'Tu mensaje fue enviado a la Serviteca. Gracias por contactarnos.');

        Mail::assertSent(ContactMessage::class, function (ContactMessage $mail): bool {
            if (
                ! $mail->hasTo('senaserviteca@gmail.com')
                || ! $mail->hasReplyTo('juan@example.com')
                || $mail->contact['nombre'] !== 'Juan Pérez'
                || $mail->envelope()->subject !== 'Nuevo mensaje de Juan Pérez | Serviteca SENA'
            ) {
                return false;
            }

            $html = $mail->render();

            return str_contains($html, 'Hola, equipo de la Serviteca')
                && str_contains($html, 'Necesito información sobre un servicio.')
                && str_contains($html, 'Responder al usuario');
        });
    }

    public function test_contact_email_escapes_user_supplied_html(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.contact_address' => 'senaserviteca@gmail.com',
            'mail.mailers.smtp.username' => 'senaserviteca@gmail.com',
            'mail.mailers.smtp.password' => 'test-app-password',
        ]);
        Mail::fake();

        $this->postJson('/api/contacto', array_merge($this->validContact(), [
            'mensaje' => '<script>alert(1)</script>',
        ]))->assertOk();

        Mail::assertSent(ContactMessage::class, function (ContactMessage $mail): bool {
            $html = $mail->render();

            return ! str_contains($html, '<script>')
                && str_contains($html, '&lt;script&gt;alert(1)&lt;/script&gt;');
        });
    }

    public function test_invalid_contact_data_is_rejected_without_sending_email(): void
    {
        config(['mail.default' => 'smtp']);
        Mail::fake();

        $this->postJson('/api/contacto', [
            'nombre' => 'Juan 123',
            'correo' => 'correo-invalido',
            'telefono' => '123',
            'mensaje' => '',
            'sitio_web' => 'bot',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nombre', 'correo', 'telefono', 'mensaje', 'sitio_web']);

        Mail::assertNothingSent();
    }

    public function test_log_mailer_does_not_report_a_message_as_sent(): void
    {
        config(['mail.default' => 'log']);
        Mail::fake();

        $this->postJson('/api/contacto', $this->validContact())
            ->assertServiceUnavailable()
            ->assertJsonPath('message', 'El correo SMTP de Gmail está incompleto. Configura el usuario y la contraseña de aplicación en laravel/.env.');

        Mail::assertNothingSent();
    }

    public function test_smtp_without_an_app_password_does_not_attempt_delivery(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.username' => 'senaserviteca@gmail.com',
            'mail.mailers.smtp.password' => null,
        ]);
        Mail::fake();

        $this->postJson('/api/contacto', $this->validContact())
            ->assertServiceUnavailable()
            ->assertJsonPath('message', 'El correo SMTP de Gmail está incompleto. Configura el usuario y la contraseña de aplicación en laravel/.env.');

        Mail::assertNothingSent();
    }

    private function validContact(): array
    {
        return [
            'nombre' => 'Juan Pérez',
            'correo' => 'juan@example.com',
            'telefono' => '3001234567',
            'mensaje' => 'Necesito información sobre un servicio.',
            'sitio_web' => '',
        ];
    }
}
