# Importar la base de datos en XAMPP/phpMyAdmin

La base completa del proyecto está en un solo archivo: `sena_serviteca_1.sql`. Incluye tablas, datos de ejemplo, índices, claves foráneas, vistas y procedimientos almacenados.

## Importación

1. Inicia Apache y MySQL desde el panel de XAMPP.
2. Abre `http://localhost/phpmyadmin/`.
3. En la página principal, abre la pestaña **Importar** sin seleccionar una base de datos.
4. Selecciona `sena_serviteca_1.sql` de esta carpeta y pulsa **Importar**.
5. Verifica que aparezca la base `sena_serviteca_1` con sus tablas.

El archivo crea la base `sena_serviteca_1` si todavía no existe y selecciona ese esquema automáticamente. No borra tablas ni datos existentes: para una instalación limpia, respalda lo que necesites y elimina la base anterior desde phpMyAdmin antes de importarlo.

## Conexión

La aplicación se conecta a `sena_serviteca_1` desde `db.php`, usando el usuario `root` sin contraseña, que es la configuración local habitual de XAMPP. Si cambiaste las credenciales de MySQL, actualiza ese archivo para que coincida.
