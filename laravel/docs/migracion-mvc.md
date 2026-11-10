# Migración MVC de SenaServiControl

## Estado de la migración

El esquema de `database/sena_serviteca_1.sql` contiene 19 tablas, tres vistas y cuatro procedimientos almacenados. Laravel debe conectarse a esa base existente; no se deben ejecutar migraciones destructivas ni volver a crear sus tablas sin una revisión del esquema.

Esta primera etapa agrega modelos Eloquent para las tablas y vistas, enums de usuario/servicio y una ruta vertical funcional para inicio de sesión, consulta y registro de clientes, vehículos y servicios. Las transacciones del registro de servicio incluyen asignación, seguimiento inicial, notificación y auditoría. Los estados de servicio contemplan los valores sembrados en la base y los usados por las pantallas heredadas.

Los scripts de `codigos_php/` se conservan temporalmente. No son seguros de borrar todavía: los 42 archivos de la raíz de esa carpeta son puntos de entrada que incluyen otros 42 archivos ubicados por rol o compartidos. Las páginas actuales y redirecciones dependen de esos puntos de entrada. Las comprobaciones de sintaxis de PHP de los 84 scripts no encontraron errores.

## Destino de los archivos heredados

### Autenticación

| Archivo actual | Destino Laravel |
|---|---|
| `codigos_php/autenticacion/login.php` | `AuthController`, `LoginRequest`, `Usuario` y `resources/views/auth/login.blade.php` |
| `codigos_php/autenticacion/logout.php` | `AuthController::destroy` y ruta `POST /logout` |
| `codigos_php/autenticacion/register.php` | `AuthController`/`UsuarioController`, un Form Request de registro y vistas Blade; pendiente |
| `codigos_php/autenticacion/contacto.php` | `ContactoController`, `StoreContactoRequest` y `resources/views/contacto/`; pendiente |

### Administración

| Archivo actual | Destino Laravel |
|---|---|
| `codigos_php/administrador/admin_clientes.php` | `ClienteController`, `StoreClienteRequest`, `Cliente` y `resources/views/clientes/` |
| `codigos_php/administrador/admin_vehiculos.php` | `VehiculoController`, `StoreVehiculoRequest`, `Vehiculo` y `resources/views/vehiculos/` |
| `codigos_php/administrador/admin_servicios.php` | `ServicioController`, Form Requests de servicio, `Servicio` y `resources/views/servicios/` |
| `codigos_php/administrador/admin_repuestos.php` | `RepuestoController`, Form Requests, `Repuesto` y `resources/views/repuestos/`; pendiente |
| `codigos_php/administrador/admin_usuarios.php` | `UsuarioController`, Form Requests, `Usuario` y `resources/views/usuarios/`; pendiente |
| `codigos_php/administrador/dashboard_admin.php` | `DashboardController` y vistas Blade del panel por rol; panel específico pendiente |

### Clientes

| Archivo actual | Destino Laravel |
|---|---|
| `codigos_php/cliente/cliente_perfil.php` | `PerfilController`, Form Requests de perfil y `resources/views/perfil/`; pendiente |
| `codigos_php/cliente/cliente_servicios.php` | `ServicioController`, `Servicio` y `resources/views/servicios/` |
| `codigos_php/cliente/cliente_solicitud_servicio.php` | `SolicitudServicioController`, Form Requests, `SolicitudServicio` y `resources/views/solicitudes/`; pendiente |
| `codigos_php/cliente/cliente_vehiculos.php` | `VehiculoController`, `StoreVehiculoRequest`, `Vehiculo` y `resources/views/vehiculos/` |
| `codigos_php/cliente/dashboard_cliente.php` | `DashboardController` y vista Blade del panel por rol |
| `codigos_php/cliente/registro_serviteca.php` | `SolicitudServicioController`, Form Requests y vista de solicitudes; pendiente |

### Operaciones compartidas

| Archivo actual | Destino Laravel |
|---|---|
| `codigos_php/compartido/asignaciones_tecnicos.php` | `AsignacionTecnicoController`, Form Requests, `HistorialAsignacionTecnico` y vistas; pendiente |
| `codigos_php/compartido/dashboard_sidebar.php` | Layout/componentes Blade en `resources/views/layouts/` y `resources/views/components/` |
| `codigos_php/compartido/entregas.php` | `EntregaController`, Form Requests, `EntregaVehiculo` y vistas; pendiente |
| `codigos_php/compartido/historial_actividades.php` | `ActividadController`, `HistorialActividadUsuario` y vistas; pendiente |
| `codigos_php/compartido/historial_servicios.php` | `ServicioController`, `Servicio` y vistas de historial; pendiente |
| `codigos_php/compartido/incidencias.php` | `IncidenciaController`, Form Requests, `Incidencia` y vistas; pendiente |
| `codigos_php/compartido/mantenimientos.php` | `MantenimientoController`, Form Requests, `Mantenimiento` y vistas; pendiente |
| `codigos_php/compartido/notificaciones.php` | `NotificacionController`, `Notificacion` y vistas; pendiente |
| `codigos_php/compartido/perfil_usuario.php` | `PerfilController`, Form Requests y vistas de perfil; pendiente |
| `codigos_php/compartido/reparaciones.php` | `ReparacionController`, Form Requests, `Reparacion`/`DetalleReparacion` y vistas; pendiente |
| `codigos_php/compartido/reportes.php` | `ReporteController`, clases de exportación PDF/Excel y vistas; pendiente |
| `codigos_php/compartido/solicitudes_servicio.php` | `SolicitudServicioController`, Form Requests, `SolicitudServicio` y vistas; pendiente |
| `codigos_php/compartido/auditoria_helper.php` | `HistorialActividadUsuario` y, al completar la migración, un servicio de auditoría inyectable |

### Recepción

| Archivo actual | Destino Laravel |
|---|---|
| `codigos_php/recepcionista/actualizar_fecha_entrega.php` | acción de `ServicioController` y Form Request de fechas; pendiente |
| `codigos_php/recepcionista/guardar_servicios.php` | `ServicioController::store`, `StoreServicioRequest` y los modelos relacionados |
| `codigos_php/recepcionista/recepcionista_clientes.php` | `ClienteController` y vistas de clientes |
| `codigos_php/recepcionista/recepcionista_servicios.php` | `ServicioController` y vistas de servicios |
| `codigos_php/recepcionista/recepcionista_vehiculos.php` | `VehiculoController` y vistas de vehículos |
| `codigos_php/recepcionista/registro_cliente.php` | `ClienteController::store` y `StoreClienteRequest` |
| `codigos_php/recepcionista/registro_recepcionista.php` | `RecepcionistaController`, Form Requests, `Recepcionista` y vistas; pendiente |
| `codigos_php/recepcionista/registro_servicios.php` | `ServicioController` y vista de registro de servicios |
| `codigos_php/recepcionista/dashboard_recepcionista.php` | `DashboardController` y vista Blade del panel por rol |

### Técnicos

| Archivo actual | Destino Laravel |
|---|---|
| `codigos_php/tecnico/dashboard_tecnico.php` | `DashboardController` y vista Blade del panel por rol |
| `codigos_php/tecnico/seguimiento_tecnico.php` | `SeguimientoController`, Form Requests, `Seguimiento` y vistas; pendiente |
| `codigos_php/tecnico/tecnico_reparaciones.php` | `ReparacionController`, Form Requests, `Reparacion` y vistas; pendiente |
| `codigos_php/tecnico/tecnico_servicios.php` | `ServicioController`, `Servicio` y vistas |

### Puntos de compatibilidad

Cada `codigos_php/<nombre>.php` ubicado directamente en la raíz incluye el archivo del mismo nombre en `administrador/`, `autenticacion/`, `cliente/`, `compartido/`, `recepcionista/` o `tecnico/`. Son envoltorios, no implementaciones duplicadas. Se deben retirar únicamente cuando se actualicen todos los enlaces, formularios y redirecciones a rutas Laravel con nombre.

`db.php` debe reemplazarse en el corte final por `config/database.php` y las variables `DB_*` de `.env`; no debe cargarse desde controladores ni vistas. Los procedimientos almacenados se conservan en el dump de la base mientras existan consumidores externos: `registrar_cliente`, `registrar_vehiculo` y `registrar_servicio` corresponden a altas gestionadas por sus controladores; `actualizar_estado_servicio` corresponde a la transición de estado en `ServicioController` o un servicio de dominio. El alta de servicio Laravel agrega en una sola transacción el historial, el seguimiento, la notificación y la auditoría que el procedimiento almacenado no cubre.

## Componentes Laravel agregados

- `app/Models/`: modelos para las 19 tablas, modelos de lectura para las tres vistas y relaciones explícitas sobre los nombres de tabla y claves primarias existentes.
- `app/Enums/`: roles y estado de usuario definidos por la base, más los estados de servicio iniciales verificados en el dump.
- `app/Http/Controllers/`: autenticación, panel, clientes, vehículos y servicios.
- `app/Http/Requests/`: validación de inicio de sesión y de altas de clientes, vehículos y servicios.
- `resources/views/`: layout, inicio de sesión, panel y páginas de clientes, vehículos y servicios.
- `routes/web.php`: rutas web Laravel protegidas con middleware `auth`/`guest`.
- `config/auth.php`: proveedor Eloquent configurado para la tabla heredada `usuario`.
- `.env.example`: conexión local MySQL para `sena_serviteca_1`; las credenciales de despliegue deben mantenerse exclusivamente en `.env`.

## Pasos para completar el corte

1. Importar o respaldar la base existente y configurar `.env` con credenciales locales; no versionar ese archivo.
2. Terminar los controladores, Form Requests y vistas marcados como pendientes, incluidos los reportes y sus pruebas.
3. Probar cada rol y flujo con una copia de la base; verificar que las consultas, auditorías y cambios transaccionales mantengan el comportamiento anterior.
4. Actualizar los enlaces y formularios de `index.html` para apuntar a rutas Laravel.
5. Configurar el servidor web con `laravel/public` como document root. Mantener el código de aplicación, `.env`, `storage/` y `vendor/` fuera del directorio público.
6. Eliminar `codigos_php/`, `db.php` y las dependencias Composer duplicadas solo después de comprobar que no quedan referencias activas y que los flujos migrados están aceptados.
