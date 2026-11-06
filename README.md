🚗 SenaServiControl - Sistema de Gestión para Serviteca y Taller Automotriz

SenaServiControl es un sistema web de grado empresarial diseñado para la gestión, control, auditoría y seguimiento integral de operaciones en servitecas y talleres automotrices. La plataforma optimiza los flujos de trabajo administrativos y técnicos, permitiendo gestionar clientes, vehículos, órdenes de servicio, mantenimientos, reparaciones e inventario de repuestos en tiempo real bajo una arquitectura robusta y escalable.

👥 Roles del Sistema y Control de Acceso (RBAC)

El sistema implementa un control estricto de acceso basado en roles (RBAC):

🛡️ Administrador: Supervisión global del taller, gestión de usuarios, asignación y balanceo de carga técnica, auditoría de actividades (historial_actividad_usuario), gestión de incidencias, evaluación de tendencias y generación de reportes estadísticos por períodos.

📋 Recepcionista: Registro y consulta de clientes y vehículos, apertura de órdenes de servicio, programación de citas (solicitud_servicio), notificación a clientes, control de entregas (entrega_vehiculo) y emisión de reportes vehiculares.

🔧 Técnico: Inspección técnica, registro de diagnósticos y fallas, actualización de avances diarios (seguimiento), reporte de soluciones/mantenimientos y solicitud de repuestos consumidos.

👤 Cliente: Consulta de ficha técnica vehicular, seguimiento en tiempo real del progreso del servicio, aprobación de presupuestos de reparación y confirmación de entregas.

🚀 Módulos del Sistema y Funcionalidades

El proyecto se estructura operacionalmente en cuatro grandes módulos funcionales:

1. 📝 Registro de Clientes y Vehículos

Gestión de Clientes: Inserción y validación de datos personales de propietarios, vinculación con cuentas de acceso únicas y control de duplicados mediante documento de identidad.

Gestión Vehicular: Registro de ficha técnica (placa única, marca, modelo, color, año y kilometraje) asociada obligatoriamente a un cliente activo.

Validación y Trazabilidad: Corrección de datos, verificación de integridad y control histórico de modificaciones.

2. 🔍 Gestión de Clientes y Seguimiento

Historial de Servicios: Consulta de intervenciones anteriores para diagnósticos preventivos y trazabilidad del vehículo.

Sistema de Notificaciones: Emisión de alertas automáticas sobre avances, aprobación de presupuestos y aviso de vehículo listo para entrega (notificacion).

Bitácora de Seguimiento: Registro cronológico de avances diarios con soporte para aprendices de apoyo y técnicos encargados.

3. 🛠️ Gestión de Servicios y Reparaciones

Mantenimiento Preventivo y General: Clasificación de servicios, programación de fechas y registro de observaciones técnicas.

Reparaciones y Presupuestos: Diagnóstico de fallos detectados, propuesta de soluciones, desglose de repuestos consumidos (detalle_reparacion) y aprobación de presupuestos.

Asignación y Balanceo Técnico: Asignación de responsables, reasignación con trazabilidad (historial_asignacion_tecnico) y control de estados (Pendiente, En proceso, Finalizado, Entregado).

4. 📊 Gestión Administrativa y Reportes

Indicadores y Estadísticas: Análisis de demanda vehicular (marcas y modelos más atendidos), frecuencia de atención a clientes y monitoreo de retrasos operacionales.

Módulo de Reportes: Generación de reportes periódicos (diario, semanal, mensual) parametrizables en formatos descargables.

Auditoría e Incidencias: Módulo de monitoreo de actividades por usuario y control de incidencias o eventualidades del taller (incidencia).

📐 Estándar de Calidad ISO/IEC 25010 (Requisitos No Funcionales)

El sistema fue diseñado y estructurado bajo los lineamientos del estándar ISO/IEC 25010:

⚡ Rendimiento: Tiempos de respuesta inferiores a 3 segundos en consultas complejas. Soporte de concurrencia para mínimo 20 usuarios simultáneos sin degradación de rendimiento.

🔒 Seguridad y Confidencialidad: Autenticación obligatoria mediante usuario y contraseña, arquitectura de seguridad de 8 capas y transmisión de tokens JWT.

🛡️ Fiabilidad y Tolerancia a Fallos: Disponibilidad mínima del 95% del tiempo laborable, respaldo diario automatizado de base de datos y recuperación rápida ante fallas eléctricas.

🧩 Mantenibilidad y Arquitectura: Arquitectura MVC (Modelo-Vista-Controlador) con patrones de diseño orientados a la separación de responsabilidades y modularidad.

🌐 Compatibilidad y Portabilidad: Despliegue sobre navegadores modernos (Chrome, Edge), compatibilidad con entornos Windows 10+ e integración nativa con bases de datos MariaDB / MySQL.

🎨 Usabilidad: Interfaz limpia, intuitiva y adaptada a las necesidades operativas del personal administrativo, técnico y de recepción.

📊 Estructura de la Base de Datos (sena_serviteca_1)

La base de datos relacional InnoDB se compone de 19 tablas principales, 3 vistas y 4 procedimientos almacenados:

Tablas Principales: usuario, cliente, vehiculo, servicio, mantenimiento, reparacion, repuesto, detalle_reparacion, seguimiento, tecnico, recepcionista, administrador, confirmacion_notificacion, notificacion, solicitud_servicio, entrega_vehiculo.

Tablas de Auditoría y Control: historial_actividad_usuario, historial_asignacion_tecnico, incidencia.

Vistas (Views): vista_servicios, vista_seguimiento_servicios, vista_inventario_repuestos.

Procedimientos Almacenados (Stored Procedures): registrar_cliente, registrar_vehiculo, registrar_servicio, actualizar_estado_servicio.

🛠️ Tecnologías Utilizadas

Backend: PHP 8.2+ (Arquitectura MVC, Patrones de Diseño, Laravel / PHP Nativo)

Base de Datos: MySQL / MariaDB 10.4.32 (sena_serviteca_1)

Frontend: HTML5, CSS3, JavaScript ES6+, Tailwind CSS / Bootstrap

Seguridad: JWT (JSON Web Tokens), Control de acceso RBAC, Encriptación de contraseñas.

Exportación de Datos: Soporte para reportes en formato PDF y Microsoft Excel.

⚡ Instalación y Configuración Local

Clonar el repositorio:

git clone https://github.com/juanjosesotoflorez-spec/Proyecto-SenaServiControl.git
cd Proyecto-SenaServiControl


Configurar la Base de Datos:

Abre phpMyAdmin, MySQL Workbench o tu cliente MySQL preferido.

Crea la base de datos sena_serviteca_1 con cotejamiento utf8mb4_general_ci.

Importa el archivo de volcado SQL (sena_serviteca_1.sql) incluido en el repositorio.

Configurar Servidor Web Local:

Utiliza XAMPP, WAMP, Laragon o el servidor embebido de PHP:

php -S localhost:8000


✒️ Autores

Proyecto formativo desarrollado para el SENA (Servicio Nacional de Aprendizaje) por:

Juan Jose Soto Florez

Samuel Quintero Restrepo

Emmanuel Ospina Alzate

Juan Jose Areiza Sanchez
