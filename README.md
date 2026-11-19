 
<!-- ====================================================== -->
<!--                  SENASERVICONTROL                      -->
<!-- ====================================================== -->

<div align="center">

<img width="100%" src="https://capsule-render.vercel.app/api?type=waving&color=0:006B32,50:00AF50,100:111827&height=230&section=header&text=SenaServiControl&fontSize=48&fontColor=FFFFFF&fontAlignY=38&desc=GESTI%C3%93N%20INTELIGENTE%20PARA%20SERVICIOS%20AUTOMOTRICES&descAlignY=60&descSize=15&animation=fadeIn" />

<br>

### 🚗 Sistema web de gestión administrativa y operativa para una serviteca automotriz

**Centralización · Organización · Seguimiento · Control**

<br>

[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com/)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-MariaDB-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com/)
[![Vite](https://img.shields.io/badge/Vite-6-646CFF?style=for-the-badge&logo=vite&logoColor=white)](https://vite.dev/)

<br>

<a href="https://github.com/juanjosesotoflorez-spec/Proyecto-SenaServiControl">
<img src="https://img.shields.io/badge/REPOSITORIO%20OFICIAL-GITHUB-181717?style=for-the-badge&logo=github&logoColor=white" />
</a>

</div>

---

## 📌 Tabla de contenidos

<details open>
<summary><strong>📖 Explorar documentación del proyecto</strong></summary>

<br>

| # | Sección |
|:---:|---|
| 01 | [📖 Descripción del proyecto](#-descripción-del-proyecto) |
| 02 | [✨ Funcionalidades principales](#-funcionalidades-principales) |
| 03 | [🛠️ Tecnologías y dependencias](#️-tecnologías-y-dependencias) |
| 04 | [💻 Requisitos previos](#-requisitos-previos) |
| 05 | [🚀 Instalación en entorno local (Windows / XAMPP)](#-instalación-en-entorno-local-windows--xampp) |
| 06 | [⚙️ Configuración del archivo .env](#️-configuración-del-archivo-env) |
| 07 | [🧪 Pruebas y compilación](#-pruebas-y-compilación-de-recursos) |
| 08 | [📁 Estructura del proyecto](#-estructura-del-proyecto) |
| 09 | [🗄️ Base de datos y reportes PDF](#️-base-de-datos-migraciones-y-reportes-pdf) |
| 10 | [🔒 Seguridad y colaboración](#-seguridad-buenas-prácticas-y-colaboración) |
| 11 | [👨‍💻 Equipo de desarrollo](#-equipo-de-desarrollo) |

</details>

---

## 📖 Descripción del proyecto

<div align="center">

<img src="https://img.shields.io/badge/PROYECTO-SENA%20ADSO-00843D?style=flat-square" />
<img src="https://img.shields.io/badge/CATEGOR%C3%8DA-SISTEMA%20WEB-2563EB?style=flat-square" />
<img src="https://img.shields.io/badge/SECTOR-AUTOMOTRIZ-374151?style=flat-square" />

</div>

**SenaServiControl** es una aplicación web diseñada para digitalizar, centralizar y optimizar la gestión administrativa y operativa de una serviteca o taller automotriz.

El sistema articula el flujo completo de atención: desde el registro de clientes y vehículos hasta la apertura de órdenes de mantenimiento y reparación, asignación de técnicos, seguimiento en tiempo real de avances y generación de reportes ejecutivos en PDF.

La aplicación principal está construida sobre **Laravel 12, PHP 8.2+ y MySQL/MariaDB**, integrando una interfaz responsiva desarrollada con Blade, Bootstrap 5 y Vite.

### 🎯 Objetivo general

Proveer una plataforma web robusta y centralizada que permita organizar la información de clientes y vehículos, controlar el ciclo de vida de los servicios automotrices y facilitar la trazabilidad operativa para administradores, recepcionistas, técnicos y clientes.

### 💡 Alcance tecnológico

- Arquitectura web bajo el patrón **Modelo-Vista-Controlador (MVC)** con **Laravel 12**.
- Control de acceso por roles (**Administrador, Recepcionista, Técnico y Cliente**).
- Vistas dinámicas renderizadas mediante el motor de plantillas **Blade**.
- Persistencia relacional sobre **MySQL / MariaDB** con Eloquent ORM, vistas SQL y procedimientos almacenados.
- Interfaz responsiva con **Bootstrap 5** y compilación de activos mediante **Vite 6**.
- Generación de reportes administrativos en formato **PDF** con **Dompdf**.

---

## ✨ Funcionalidades principales

### 🔐 Autenticación y control de acceso

- Inicio y cierre de sesión seguro mediante correo electrónico y contraseña cifrada.
- Validación de estado activo de las cuentas de usuario.
- Regeneración del identificador de sesión al autenticarse e invalidación al salir.
- Protección de rutas privadas mediante middlewares y control de permisos por rol.
- Limitación de intentos fallidos de inicio de sesión (*rate limiting*).

### 👥 Gestión de clientes

- Consulta y listado de clientes registrados.
- Registro y actualización de datos de contacto mediante formularios validados.
- Asociación directa entre el expediente del cliente y sus vehículos.
- Historial consolidado de servicios asociados a cada propietario.

### 🚘 Gestión de vehículos

- Registro de vehículos con validación de placa única.
- Vinculación relacional entre cada vehículo y su propietario.
- Consulta de ficha técnica e historial de intervenciones por vehículo.

### 🔧 Gestión de servicios y seguimiento

- Apertura y registro de órdenes de servicio automotriz.
- Asignación y reasignación de técnicos responsables.
- Aceptación de órdenes asignadas por parte del personal técnico.
- Registro cronológico de avances, observaciones y actualización de estados.
- Gestión de solicitudes de servicio y control de entrega vehicular.

### 📊 Panel administrativo y estadísticas

- Visualización de métricas de servicios activos y entregados.
- Consulta de órdenes recientes y actividad operativa del taller.
- Indicadores de ingresos y clientes registrados restringidos al personal autorizado.

### 📄 Reportes PDF

- Generación y descarga de reportes operativos en formato PDF (A4 horizontal).
- Vista previa optimizada para impresión y auditoría.
- Acceso restringido exclusivamente al rol **Administrador**.

---

## 🛠️ Tecnologías y dependencias

<div align="center">

### ⚙️ Backend

<img src="https://skillicons.dev/icons?i=php,laravel" />

<br><br>

### 🎨 Frontend

<img src="https://skillicons.dev/icons?i=html,css,js,bootstrap,tailwind,vite" />

<br><br>

### 🗄️ Base de datos y herramientas

<img src="https://skillicons.dev/icons?i=mysql,git,github,nodejs" />

</div>

### Backend

| Tecnología | Función en el sistema |
|---|---|
| **PHP 8.2+** | Lenguaje principal del servidor |
| **Laravel 12** | Framework backend bajo arquitectura MVC |
| **Composer 2** | Gestor de dependencias PHP |
| **Eloquent ORM** | Mapeo objeto-relacional y consultas parametrizadas |
| **Dompdf** | Renderizado y exportación de documentos PDF |

### Frontend

| Tecnología | Función en el sistema |
|---|---|
| **Blade** | Motor de plantillas del lado del servidor |
| **HTML5 / CSS3** | Estructura semántica y estilos personalizados |
| **Bootstrap 5** | Sistema de rejilla y componentes responsivos |
| **Tailwind CSS 4** | Utilidades de estilo complementarias |
| **JavaScript / Axios** | Interactividad en cliente y peticiones HTTP |
| **Vite 6** | Empaquetado y optimización de activos frontend |

### Base de datos y servidor

| Tecnología | Función en el sistema |
|---|---|
| **MySQL / MariaDB** | Sistema de gestión de base de datos relacional |
| **Apache (XAMPP)** | Servidor web y entorno de ejecución local |

### 📦 Dependencias principales

**Composer (`composer.json`):**

```text
laravel/framework
laravel/tinker
barryvdh/laravel-dompdf
```

**NPM (`package.json`):**

```text
bootstrap
tailwindcss
@tailwindcss/vite
vite
laravel-vite-plugin
axios
concurrently
```

---

## 💻 Requisitos previos

Para desplegar el proyecto en un entorno local de desarrollo se requiere:

| Herramienta | Versión recomendada |
|---|---|
| **Sistema Operativo** | Windows 10 / 11, Linux o macOS |
| **Servidor Local** | XAMPP (compatible con PHP 8.2+) |
| **PHP** | 8.2 o superior |
| **Composer** | 2.x |
| **Node.js y npm** | Versión LTS compatible con Vite 6 |
| **Base de Datos** | MySQL 8.0+ o MariaDB 10.4+ |

### Extensiones PHP requeridas

`openssl`, `pdo`, `pdo_mysql`, `mbstring`, `tokenizer`, `xml`, `ctype`, `fileinfo`, `dom`, `gd`.

### Verificación de entorno

Ejecutar en una terminal (PowerShell, CMD o Bash):

```bash
php -v
composer -V
node -v
npm -v
```

---

## 🚀 Instalación en entorno local (Windows / XAMPP)

> **Nota:** Los comandos de Git se ejecutan desde la raíz del repositorio, mientras que los comandos de Composer, Artisan y NPM deben ejecutarse dentro del subdirectorio `laravel/`.

### 01. Clonar el repositorio

```bash
cd C:\xampp\htdocs
git clone https://github.com/juanjosesotoflorez-spec/Proyecto-SenaServiControl.git
cd Proyecto-SenaServiControl
```

### 02. Iniciar servicios locales

Abrir el panel de control de **XAMPP** e iniciar los módulos **Apache** y **MySQL**.

### 03. Crear la base de datos

1. Acceder a `http://localhost/phpmyadmin`.
2. Crear una nueva base de datos con el nombre `sena_serviteca_1` y cotejamiento `utf8mb4_general_ci`.
3. Importar el esquema SQL estructural proporcionado por el equipo de desarrollo (ver sección [Base de datos](#️-base-de-datos-migraciones-y-reportes-pdf)).

### 04. Instalar dependencias de Laravel

Ingresar a la carpeta de la aplicación Laravel e instalar los paquetes PHP:

```bash
cd laravel
composer install
```

### 05. Configurar variables de entorno y clave de aplicación

```bash
copy .env.example .env
php artisan key:generate
```

*(En Linux/macOS utilizar `cp .env.example .env`).*

### 06. Instalar dependencias frontend y compilar activos

```bash
npm install
npm run build
```

### 07. Configuración del servidor web y ejecución

**Opción A — Servidor integrado de desarrollo (recomendado para pruebas rápidas):**

```bash
php artisan serve
```

La aplicación estará disponible en `http://127.0.0.1:8000`.

**Opción B — VirtualHost en Apache (XAMPP):**

Apuntar el `DocumentRoot` del VirtualHost directamente a la carpeta pública de Laravel:

```text
.../Proyecto-SenaServiControl/laravel/public
```

> **Advertencia de seguridad:** Nunca se debe exponer la carpeta raíz `laravel/` ni el archivo `.env` como directorio público de Apache. El único punto de entrada HTTP permitido debe ser `laravel/public/`.

---

## ⚙️ Configuración del archivo .env

El archivo `laravel/.env` almacena las credenciales locales de cada entorno y **está excluido del control de versiones** mediante `.gitignore`.

### Plantilla de referencia (`laravel/.env`)

```env
APP_NAME=SenaServiControl
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

APP_LOCALE=es
APP_FALLBACK_LOCALE=es

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sena_serviteca_1
DB_USERNAME=usuario_local_mysql
DB_PASSWORD=contrasena_local_segura

SESSION_DRIVER=file
SESSION_LIFETIME=120

CACHE_STORE=file
QUEUE_CONNECTION=sync
```

### Envío del formulario de contacto

El formulario envía los mensajes a `senaserviteca@gmail.com` mediante SMTP de Gmail. Copia estas variables en `laravel/.env` y configura una **contraseña de aplicación de Google** (no la contraseña normal de la cuenta):

```env
MAIL_MAILER=smtp
MAIL_SCHEME=null
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=senaserviteca@gmail.com
MAIL_PASSWORD=CONTRASENA_DE_APLICACION
MAIL_FROM_ADDRESS="${MAIL_USERNAME}"
MAIL_FROM_NAME="${APP_NAME}"
CONTACT_MAIL_ADDRESS=senaserviteca@gmail.com
```

La cuenta debe tener activada la verificación en dos pasos para generar una contraseña de aplicación. Después de guardar `.env`, ejecuta desde `laravel/`:

```bash
php artisan config:clear
```

No publiques ni compartas la contraseña de aplicación. Si SMTP no está configurado, el formulario mostrará un error y no indicará falsamente que el mensaje se envió.

### Parámetros de base de datos

| Variable | Descripción |
|---|---|
| `DB_CONNECTION` | Controlador de base de datos (`mysql`) |
| `DB_HOST` | Host del servidor de base de datos (`127.0.0.1`) |
| `DB_PORT` | Puerto del servicio MySQL (`3306`) |
| `DB_DATABASE` | Nombre de la base de datos (`sena_serviteca_1`) |
| `DB_USERNAME` | Usuario local con permisos sobre la base de datos |
| `DB_PASSWORD` | Contraseña local del motor MySQL |

---

## 🧪 Pruebas y compilación de recursos

Todos los comandos siguientes se ejecutan dentro del directorio `laravel/`:

```bash
# Ejecutar suite de pruebas automatizadas (PHPUnit)
php artisan test

# Compilar recursos frontend en modo desarrollo (hot-reload)
npm run dev

# Compilar y minificar recursos para producción
npm run build

# Limpiar cachés de configuración, rutas y vistas
php artisan optimize:clear
```

---

## 📁 Estructura del proyecto

El repositorio mantiene una separación clara entre la portada estática inicial, los archivos heredados de referencia y la aplicación principal desarrollada en **Laravel**:

```text
Proyecto-SenaServiControl/
│
├── codigos_php/                # Módulos PHP procedurales de referencia heredada
├── css/                        # Hojas de estilo de la portada estática
├── imagenes/                   # Recursos gráficos e identidad visual
├── js/                         # Scripts de la portada estática
│
├── laravel/                    # Aplicación principal Laravel 12
│   ├── app/
│   │   ├── Enums/              # Enumeraciones nativas PHP (estados del servicio)
│   │   ├── Http/
│   │   │   ├── Controllers/    # Controladores HTTP (Clientes, Vehículos, Servicios, Reportes)
│   │   │   ├── Middleware/     # Filtros de autenticación y control de acceso por rol
│   │   │   └── Requests/       # Form Requests para validación e integridad de datos
│   │   └── Models/             # Modelos Eloquent y relaciones con MySQL
│   ├── bootstrap/              # Arranque del framework y registro de middlewares
│   ├── config/                 # Archivos de configuración (app, auth, database, dompdf)
│   ├── database/               # Seeders y fábricas de datos de prueba
│   ├── public/                 # ÚNICO punto de entrada público HTTP (index.php)
│   ├── resources/
│   │   ├── css/                # Estilos fuente compilados con Vite
│   │   ├── js/                 # Scripts fuente compilados con Vite
│   │   └── views/              # Plantillas Blade (layouts, módulos y reportes PDF)
│   ├── routes/
│   │   └── web.php             # Definición de rutas web protegidas
│   ├── storage/                # Logs, sesiones y archivos temporales generados
│   ├── tests/                  # Pruebas unitarias y funcionales (Unit / Feature)
│   ├── .env.example            # Plantilla pública de variables de entorno
│   ├── artisan                 # Interfaz de línea de comandos de Laravel
│   ├── composer.json           # Dependencias PHP de Laravel
│   └── package.json            # Dependencias frontend de Node/Vite
│
├── .gitignore                  # Exclusión de archivos sensibles (.env, vendor, node_modules)
├── .htaccess                   # Reglas de enrutamiento en servidor Apache
├── index.html                  # Página de presentación pública inicial
└── README.md                   # Documentación técnica del repositorio
```

---

## 🗄️ Base de datos, migraciones y reportes PDF

### Esquema relacional (`sena_serviteca_1`)

El sistema opera sobre una base de datos relacional en MySQL/MariaDB compuesta por **19 tablas**, **3 vistas SQL** (`vista_servicios`, `vista_seguimiento_servicios`, `vista_inventario_repuestos`) y **4 procedimientos almacenados** (`registrar_cliente`, `registrar_vehiculo`, `registrar_servicio`, `actualizar_estado_servicio`).

> **Política de privacidad de datos:** Por seguridad y protección de datos personales, este repositorio público **no incluye** respaldos SQL con registros reales de clientes, contraseñas ni información sensible. Para entornos de desarrollo, se debe solicitar el esquema estructural limpio (`DDL`) con datos ficticios de prueba a través de los canales internos del equipo.

### Precaución con migraciones

Dado que la base de datos utiliza un esquema relacional predefinido con procedimientos almacenados y vistas SQL, **no se debe ejecutar** `php artisan migrate:fresh` sobre una instancia activa, ya que eliminaría las tablas, vistas y rutinas almacenadas.

### Exportación de reportes PDF

Implementada mediante `barryvdh/laravel-dompdf` en `ReporteController`, permitiendo al administrador generar y descargar reportes consolidados en formato **A4 horizontal** directamente desde vistas Blade.

---

## 🔒 Seguridad, buenas prácticas y colaboración

### 🛡️ Mecanismos de seguridad implementados

- **Aislamiento de entrada:** Todas las peticiones HTTP a Laravel se canalizan exclusivamente a través de `laravel/public/index.php`.
- **Protección CSRF:** Validación automática de tokens `@csrf` en todos los formularios POST, PUT y DELETE.
- **Prevención XSS:** Escapado automático de salida en plantillas Blade mediante `{{ }}`.
- **Prevención de Inyección SQL:** Uso de consultas parametrizadas mediante Eloquent ORM y Query Builder.
- **Cifrado de credenciales:** Almacenamiento de contraseñas mediante algoritmos de hash seguros (`BCrypt` / `Hash::make`).
- **Validación estricta:** Desacoplamiento de reglas de negocio y unicidad mediante clases `FormRequest`.
- **Control de sesiones:** Regeneración de ID de sesión al iniciar sesión, invalidación al cerrar sesión y limitación de intentos de acceso.

### ⚠️ Reglas obligatorias para colaboradores del repositorio

Para mantener la integridad y seguridad de este repositorio público, está estrictamente prohibido subir mediante `git push`:

1. Archivos `.env` con contraseñas o claves `APP_KEY` reales.
2. Volcados `.sql` con datos personales reales (cédulas, teléfonos, correos o contraseñas).
3. Directorios de dependencias o cachés (`vendor/`, `node_modules/`, `storage/*.log`).

### 🤝 Flujo de contribución (Git Flow)

1. Hacer un **Fork** del repositorio o clonar el proyecto base.
2. Crear una rama específica desde `main` para la funcionalidad a desarrollar:

```bash
git checkout -b feature/nombre-funcionalidad
```

3. Verificar pruebas y compilación antes de confirmar cambios:

```bash
php artisan test
npm run build
```

4. Registrar los cambios con commits descriptivos y abrir un **Pull Request** hacia `main`:

```bash
git add .
git commit -m "feat: descripción clara del cambio"
git push origin feature/nombre-funcionalidad
```

---

## 👨‍💻 Equipo de desarrollo

Proyecto formativo desarrollado en el marco del programa **Tecnólogo en Análisis y Desarrollo de Software (ADSO)** — **Servicio Nacional de Aprendizaje (SENA)**.

- **Juan José Soto Flórez** — *Módulos 1 y 2: Autenticación, Seguridad, Clientes, Vehículos y Seguimiento* · [GitHub](https://github.com/juanjosesotoflorez-spec)
- **Samuel Quintero Restrepo** — *Módulos 3 y 4: Gestión de Servicios, Taller, Repuestos y Analítica Administrativa*
- **Emmanuel Ospina Alzate** — *Módulo 5: Búsquedas Multicriterio, Reportes Exportables e Historial Consolidado*

---

<div align="center">

### 🚗 SenaServiControl

**Tecnología al servicio de la gestión automotriz.**

Desarrollado con Laravel 12 y PHP 8.2+.

<br>

<img src="https://capsule-render.vercel.app/api?type=waving&color=0:006B32,50:00AF50,100:111827&height=100&section=footer" />

© 2026 SenaServiControl · Proyecto Formativo SENA ADSO

</div>
