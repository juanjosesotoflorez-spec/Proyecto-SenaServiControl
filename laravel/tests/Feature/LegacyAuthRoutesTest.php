<?php

namespace Tests\Feature;

use App\Enums\EstadoUsuario;
use App\Enums\RolUsuario;
use App\Models\Usuario;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class LegacyAuthRoutesTest extends TestCase
{
    public function test_login_page_is_available_to_guests(): void
    {
        $this->get(route('login'))->assertOk();
    }

    public function test_dashboard_redirects_guests_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_report_routes_redirect_guests_to_login(): void
    {
        $this->get(route('reportes.index'))->assertRedirect(route('login'));
        $this->get(route('reportes.pdf'))->assertRedirect(route('login'));
        $this->get(route('reportes.pdf.print'))->assertRedirect(route('login'));
    }

    public function test_service_request_routes_redirect_guests_to_login(): void
    {
        $this->get(route('solicitudes.index'))->assertRedirect(route('login'));
        $this->post(route('solicitudes.store'))->assertRedirect(route('login'));
    }

    public function test_authenticated_layout_renders_mobile_navigation_and_quick_search(): void
    {
        $usuario = new Usuario;
        $usuario->forceFill([
            'id_usuario' => 1,
            'usuario' => 'Operador',
            'rol' => RolUsuario::Administrador,
            'estado' => EstadoUsuario::Activo,
        ]);

        $this->actingAs($usuario)
            ->view('layouts.app', ['errors' => new ViewErrorBag])
            ->assertSee('mobile-navigation')
            ->assertSee('quick-search-input')
            ->assertSee('profile-menu')
            ->assertSee('build/assets/app-', false);
    }

    public function test_only_administrators_can_generate_pdf_reports(): void
    {
        $usuario = new Usuario;
        $usuario->forceFill([
            'id_usuario' => 1,
            'usuario' => 'Recepcionista',
            'rol' => RolUsuario::Recepcionista,
            'estado' => EstadoUsuario::Activo,
        ]);

        $this->actingAs($usuario)
            ->get(route('reportes.pdf'))
            ->assertForbidden();

        $this->actingAs($usuario)
            ->get(route('reportes.pdf.print'))
            ->assertForbidden();
    }

    public function test_login_rejects_invalid_input_before_accessing_the_database(): void
    {
        $this->post(route('login.store'), [
            'correo' => 'no-es-un-correo',
            'contrasena' => '',
        ])->assertSessionHasErrors(['correo', 'contrasena']);
    }
}
