# SenaServiControl

Sistema de gestión para taller automotriz construido con Laravel 12, PHP 8.2, MySQL/MariaDB y Bootstrap 5.

## Requisitos

- PHP 8.2+ con las extensiones requeridas por Laravel y Dompdf.
- Composer 2, Node.js y npm.
- MySQL/MariaDB y Apache con `mod_rewrite` (XAMPP).

## Instalación

1. Importa tu esquema/datos de desarrollo de `sena_serviteca_1` en MySQL. No incluyas respaldos con datos personales en GitHub.
2. Desde la carpeta `laravel`, instala dependencias y compila el frontend:

   ```sh
   composer install
   copy .env.example .env
   php artisan key:generate
   npm install
   npm run build
   ```

3. Configura `.env` con la URL y credenciales locales de MySQL (`DB_DATABASE=sena_serviteca_1`).
4. En XAMPP, configura el `DocumentRoot` de Apache en `laravel/public`. Como alternativa de desarrollo, el `.htaccess` de la raíz redirige hacia esa carpeta. En ese caso, configura `APP_URL` en consecuencia.
5. Inicia Apache y MySQL.

No ejecutes migraciones sobre una base de datos existente sin revisar primero su esquema y sus datos.

## Pruebas y compilación

```sh
cd laravel
php artisan test
npm run build
```

Los reportes PDF de administrador se generan con `barryvdh/laravel-dompdf`. Bootstrap y sus componentes JavaScript se incluyen en el build local de Vite.

## Seguridad

- No subas archivos `.env`, respaldos SQL con datos personales ni credenciales.
- Mantén `APP_DEBUG=false` y `APP_KEY` privada en producción.
- Los formularios web usan CSRF, las vistas Blade escapan la salida y las consultas Eloquent usan parámetros enlazados.
