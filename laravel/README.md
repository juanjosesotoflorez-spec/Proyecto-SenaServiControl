# SenaServiControl

Aplicación web sobre Laravel 12 y PHP 8.2 para administrar clientes, vehículos, servicios, solicitudes y reportes.

## Requisitos

- PHP 8.2 o superior con las extensiones requeridas por Laravel y Dompdf.
- Composer 2.
- Node.js y npm.
- MySQL/MariaDB con el esquema `sena_serviteca_1`.
- Apache con `mod_rewrite` habilitado para XAMPP.

## Instalación local

1. Prepara una base MySQL/MariaDB `sena_serviteca_1` con el esquema compatible y sin datos personales reales. No publiques volcados con información de clientes.
2. Desde esta carpeta ejecuta:

   ```sh
   composer install
   copy .env.example .env
   php artisan key:generate
   npm install
   npm run build
   ```

3. Configura `.env` con el nombre de la base de datos, usuario y contraseña locales. Genera un secreto JWT independiente con `php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"` y guárdalo como `JWT_SECRET` en `.env`; nunca publiques ese valor.
4. En XAMPP, configura el `DocumentRoot` del virtual host directamente en `laravel/public`. Si se mantiene el repositorio dentro de `htdocs`, el `.htaccess` de la raíz redirige el acceso a `laravel/public`.
5. Ajusta `APP_URL` a la URL base real (por ejemplo, `http://localhost/SENA_SERVITECA_1/laravel/public` al acceder mediante la redirección de XAMPP).
6. Inicia Apache y MySQL y visita la URL del virtual host.

No ejecutes migraciones sobre la base de datos heredada sin revisar previamente sus tablas y datos; el esquema de entrega es `database/sena_serviteca_1.sql`.

## Comandos

```sh
php artisan test
npm run build
```

Laravel enruta las peticiones web mediante `public/index.php`; `public/.htaccess` reescribe las rutas no físicas a ese front controller. Bootstrap 5 se incluye en el build de Vite junto con sus componentes JavaScript.

## API JWT

Las páginas Blade conservan la autenticación por sesión. Para clientes API:

1. `POST /api/auth/login` recibe `correo` y `contrasena`, y devuelve un token Bearer firmado con HS256 y con el rol del usuario.
2. Envía `Authorization: Bearer <token>` a `GET /api/auth/me`.
3. `POST /api/auth/logout` revoca el token hasta su expiración.

Los tokens duran 60 minutos por defecto; puedes ajustarlo con `JWT_TTL`. Configura `JWT_ISSUER` solo si necesitas fijar un emisor diferente de `APP_URL`.

## Reportes

La descarga y la vista de impresión PDF están disponibles para el rol administrador. Los PDF se generan con `barryvdh/laravel-dompdf`.

## Seguridad de aplicación

- Las rutas web usan el middleware `web` de Laravel, con sesión y protección CSRF para formularios.
- Blade escapa las expresiones `{{ ... }}`; evita `{!! ... !!}` con datos no confiables.
- Las consultas usan Eloquent/Query Builder y parámetros enlazados.
- Los Form Requests aplican autorización y reglas de validación.
- Laravel cifra cookies firmadas y los valores protegidos por `APP_KEY`; las contraseñas se almacenan con hash, no con cifrado reversible.
- En producción configura `APP_DEBUG=false`, una `APP_KEY` privada y credenciales de base de datos con privilegios mínimos.
