# Capas de seguridad de SenaServiControl

Este esquema identifica controles implementados en el código y en el middleware estándar de Laravel. La seguridad de producción también depende de la configuración de Apache, HTTPS y secretos del entorno.

| Capa | Control | Ubicación |
|---|---|---|
| 1. Autenticación | Sesión web, contraseñas verificadas con `Hash` y compatibilidad temporal para migrar contraseñas heredadas; JWT HS256 para clientes API. | `app/Services/AuthenticatesUsuario.php`, `app/Services/JwtTokenService.php`, `app/Http/Controllers/AuthController.php` |
| 2. Credenciales de sesión y API | Regeneración de sesión tras iniciar sesión; duración y revocación temporal de JWT; rotación de claves desde el entorno. | `app/Http/Controllers/AuthController.php`, `app/Services/JwtTokenService.php`, `config/jwt.php` |
| 3. Autorización por rol | Middleware que comprueba el rol actual del usuario en la base de datos. No confía exclusivamente en el rol incluido en el JWT. | `app/Http/Middleware/EnsureRole.php`, registrado en `bootstrap/app.php` |
| 4. Validación y autorización de entrada | Form Requests con reglas y autorización antes de procesar datos. | `app/Http/Requests/` |
| 5. CSRF | Middleware `web` de Laravel y token CSRF en formularios de sesión. La API usa Bearer JWT y no formularios basados en cookies. | `bootstrap/app.php`, vistas Blade |
| 6. XSS | Escape HTML automático de Blade con `{{ ... }}`. No se debe imprimir HTML no confiable con `{!! ... !!}`. | `resources/views/` |
| 7. Inyección SQL | Eloquent y Query Builder enlazan los valores de consulta; no construir SQL con entrada concatenada del usuario. | `app/Models/`, `app/Http/Controllers/` |
| 8. Defensa en profundidad | Rate limiting de login y respuestas con cabeceras contra MIME sniffing, framing de terceros y acceso innecesario a sensores. `APP_DEBUG=false` y HTTPS se deben configurar en producción. | `routes/api.php`, `routes/web.php`, `app/Http/Middleware/SecurityHeaders.php`, `.env` |

Las contraseñas se almacenan mediante hash y no mediante cifrado reversible. No se almacenan secretos en el repositorio. Genera `APP_KEY` y `JWT_SECRET` para cada entorno; no reutilices una clave de pruebas en producción.
