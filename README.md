<!-- ====================================================== -->
<!--                 SENASERVICONTROL                      -->
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
<img src="https://img.shields.io/badge/VER%20REPOSITORIO-GITHUB-181717?style=for-the-badge&logo=github&logoColor=white" />
</a>

</div>

---

## 📌 Tabla de contenidos

<details open>
<summary><strong>Explorar documentación</strong></summary>

<br>

| # | Sección |
|:---:|---|
| 01 | [📖 Descripción del proyecto](#-descripción-del-proyecto) |
| 02 | [✨ Funcionalidades verificadas](#-funcionalidades-verificadas) |
| 03 | [🛠️ Tecnologías y dependencias](#️-tecnologías-y-dependencias) |
| 04 | [💻 Requisitos previos](#-requisitos-previos) |
| 05 | [🚀 Instalación en Windows con XAMPP](#-instalación-en-windows-con-xampp) |
| 06 | [⚙️ Configuración del archivo .env](#️-configuración-del-archivo-env) |
| 07 | [🧪 Pruebas y compilación](#-pruebas-y-compilación-de-recursos) |
| 08 | [📁 Estructura del proyecto](#-estructura-del-proyecto) |
| 09 | [🗄️ Base de datos y reportes PDF](#️-base-de-datos-migraciones-y-reportes-pdf) |
| 10 | [🔒 Seguridad y colaboración](#-seguridad-limitaciones-y-colaboración) |
| 11 | [👨‍💻 Autor](#-autor) |

</details>

---

## 📖 Descripción del proyecto

<div align="center">

<img src="https://img.shields.io/badge/PROYECTO-SENA%20ADSO-00843D?style=flat-square" />
<img src="https://img.shields.io/badge/CATEGOR%C3%8DA-SISTEMA%20WEB-2563EB?style=flat-square" />
<img src="https://img.shields.io/badge/SECTOR-AUTOMOTRIZ-374151?style=flat-square" />

</div>

**SenaServiControl** es una aplicación web desarrollada para facilitar la administración y el seguimiento de los procesos de una serviteca o taller automotriz.

El sistema permite centralizar la información de clientes, vehículos, servicios y solicitudes, proporcionando herramientas para consultar el estado de los trabajos, registrar avances y generar reportes en formato PDF.

El proyecto está desarrollado con **Laravel 12, PHP 8.2, MySQL/MariaDB**, y una interfaz construida con Blade, Bootstrap 5 y Vite.

### 🎯 Objetivo general

Desarrollar un sistema web que facilite la gestión administrativa y operativa de una serviteca automotriz, permitiendo organizar la información de clientes y vehículos, administrar servicios y realizar seguimiento a los trabajos registrados.

### 💡 Alcance tecnológico

- Aplicación web desarrollada con Laravel 12.
- Arquitectura basada en el framework Laravel.
- Vistas dinámicas mediante Blade.
- Persistencia de información mediante MySQL/MariaDB.
- Interfaz web con Bootstrap 5.
- Gestión de recursos frontend mediante Vite.
- Generación de documentos PDF mediante Dompdf.

---

## ✨ Funcionalidades verificadas

Las siguientes funcionalidades se identifican en las rutas y controladores de la aplicación Laravel.

### 🔐 Autenticación y control de acceso

- Inicio de sesión mediante correo electrónico y contraseña.
- Cierre de sesión.
- Validación de cuentas activas.
- Regeneración de sesión después de iniciar sesión.
- Restricción de acceso a las rutas privadas.
- Limitación de intentos de inicio de sesión.

### 👥 Gestión de clientes

- Consulta de clientes registrados.
- Registro de clientes mediante formularios.
- Asociación de clientes con sus vehículos.
- Consulta de información relacionada con los servicios.

### 🚘 Gestión de vehículos

- Consulta de vehículos registrados.
- Registro de vehículos.
- Asociación de vehículos con sus respectivos clientes.
- Consulta de servicios relacionados con cada vehículo.

### 🔧 Gestión de servicios

- Registro de servicios automotrices.
- Asociación de servicios con vehículos y técnicos.
- Registro de costos y fechas.
- Consulta de servicios registrados.
- Asignación de servicios a técnicos.
- Aceptación de asignaciones por parte del técnico.
- Registro de avances y comentarios.
- Actualización del estado del servicio.
- Registro del historial de seguimiento.

### 📋 Gestión de solicitudes

- Consulta de solicitudes de servicio.
- Registro de nuevas solicitudes.
- Aceptación de solicitudes.

### 📊 Dashboard y estadísticas

- Consulta de servicios activos.
- Consulta de servicios entregados.
- Visualización de servicios recientes.
- Consulta de ingresos del mes para el personal autorizado.
- Consulta del número de clientes registrados para el personal autorizado.
- Restricción de información según el usuario autenticado.

### 📄 Reportes PDF

- Consulta de información de servicios.
- Generación de reportes en PDF.
- Descarga de documentos PDF.
- Vista de impresión.
- Generación de documentos en formato horizontal A4.
- Restricción de generación de PDF al rol administrador.

> **Nota:** Las funcionalidades descritas corresponden a las operaciones identificadas en los controladores y rutas del proyecto. No se afirma que exista un CRUD completo para todas las entidades ni que estén implementadas integraciones externas.

---

## 🛠️ Tecnologías y dependencias

<div align="center">

### Backend

<img src="https://skillicons.dev/icons?i=php,laravel" />

### Frontend

<img src="https://skillicons.dev/icons?i=html,css,js,bootstrap,tailwind,vite" />

### Base de datos y herramientas

<img src="https://skillicons.dev/icons?i=mysql,git,github,nodejs" />

</div>

### Backend

| Tecnología | Utilización |
|---|---|
| PHP 8.2+ | Lenguaje de programación |
| Laravel 12 | Framework backend |
| Composer 2 | Administración de dependencias PHP |
| Eloquent ORM | Interacción con la base de datos |
| Dompdf | Generación de documentos PDF |

### Frontend

| Tecnología | Utilización |
|---|---|
| Blade | Motor de plantillas de Laravel |
| HTML5 | Estructura de las páginas |
| CSS3 | Estilos de la interfaz |
| Bootstrap 5 | Componentes y diseño responsivo |
| JavaScript | Interactividad de la interfaz |
| Tailwind CSS 4 | Herramientas de estilos |
| Vite 6 | Compilación de recursos frontend |
| Axios | Cliente HTTP |

### Base de datos y servidor

| Tecnología | Utilización |
|---|---|
| MySQL | Sistema de gestión de bases de datos |
| MariaDB | Alternativa compatible para desarrollo |
| Apache | Servidor web local |
| XAMPP | Entorno de desarrollo local |

### 📦 Dependencias principales

**Composer — `composer.json`**

```text
laravel/framework
laravel/tinker
barryvdh/laravel-dompdf
```

**NPM — `package.json`**

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

Antes de instalar el proyecto, asegúrate de tener las siguientes herramientas:

| Herramienta | Versión requerida |
|---|---|
| Windows | 10 u 11 |
| XAMPP | Versión compatible con PHP 8.2+ |
| PHP | 8.2 o superior |
| Composer | 2.x |
| Node.js | Versión compatible con Vite 6 |
| npm | Incluido con Node.js |
| MySQL/MariaDB | Servidor compatible |

### Extensiones PHP necesarias

- `openssl`
- `pdo`
- `pdo_mysql`
- `mbstring`
- `tokenizer`
- `xml`
- `ctype`
- `fileinfo`
- `dom`
- `gd`

### Comprobar instalaciones

Abre PowerShell y ejecuta:

```powershell
php -v
composer -V
node -v
npm -v
```

---

## 🚀 Instalación en Windows con XAMPP

> **Importante:** Los comandos de Git se ejecutan desde la ubicación donde guardarás el repositorio. Los comandos de Laravel y NPM se ejecutan desde la carpeta `laravel/`.

### 01. Obtener el repositorio

**Ubicación: raíz del repositorio.**

Abre PowerShell:

```powershell
cd C:\xampp\htdocs

git clone https://github.com/juanjosesotoflorez-spec/Proyecto-SenaServiControl.git
```

La estructura principal quedará así:

```text
C:\xampp\htdocs\
└── Proyecto-SenaServiControl\
    ├── Documentos\
    ├── codigos_php\
    ├── css\
    ├── imagenes\
    ├── js\
    ├── laravel\
    ├── .htaccess
    ├── index.html
    └── README.md
```

### 02. Iniciar XAMPP

Abre el panel de control de XAMPP y activa:

- Apache.
- MySQL.

Comprueba que ambos servicios estén funcionando correctamente.

### 03. Crear la base de datos

**Ubicación: navegador web, phpMyAdmin.**

Accede a:

```text
http://localhost/phpmyadmin
```

Crea una base de datos local con el nombre:

```text
sena_serviteca_1
```

Utiliza una intercalación compatible con el esquema que proporcione el responsable del proyecto.

> **Importante:** Crear una base de datos vacía no es suficiente para ejecutar la aplicación. Necesitas el esquema correspondiente y autorizado.

Consulta la sección de base de datos antes de continuar.

### 04. Ingresar a la carpeta Laravel

**Ubicación: `laravel/`.**

Desde PowerShell:

```powershell
cd C:\xampp\htdocs\Proyecto-SenaServiControl\laravel
```

Comprueba que estás en la ubicación correcta:

```powershell
dir
```

Debes encontrar archivos como:

```text
composer.json
package.json
artisan
.env.example
vite.config.js
```

### 05. Instalar dependencias PHP

**Ubicación: `laravel/`.**

```powershell
composer install
```

### 06. Configurar Laravel

**Ubicación: `laravel/`.**

Crear el archivo `.env`:

```powershell
Copy-Item .env.example .env
```

Generar la clave de aplicación:

```powershell
php artisan key:generate
```

Posteriormente configura la conexión a MySQL.

### 07. Instalar dependencias frontend

**Ubicación: `laravel/`.**

```powershell
npm install
```

### 08. Compilar recursos frontend

**Ubicación: `laravel/`.**

```powershell
npm run build
```

### 09. Configurar Apache

Para ejecutar Laravel correctamente mediante Apache, el directorio público debe apuntar a:

```text
Proyecto-SenaServiControl/laravel/public
```

Se recomienda configurar un VirtualHost de Apache cuyo `DocumentRoot` sea directamente esa carpeta.

Como alternativa, el repositorio incluye un archivo `.htaccess` en su raíz que contempla la redirección hacia `laravel/public`. Si utilizas esta alternativa, comprueba que `mod_rewrite` esté habilitado y ajusta `APP_URL` a la dirección utilizada.

**Importante:** No configures Apache para publicar directamente la carpeta `laravel/`, ya que los archivos internos de Laravel no deben quedar expuestos.

### 10. Ejecutar el proyecto

Una vez configurados Apache, MySQL y `.env`, accede a la dirección configurada para tu entorno local.

Si utilizas un VirtualHost:

```text
http://senaservicontrol.local
```

La dirección anterior es un ejemplo: debes configurarla en Apache y en el archivo `hosts` de Windows.

También puedes iniciar el servidor de desarrollo de Laravel desde `laravel/`:

```powershell
php artisan serve
```

Accede desde el navegador:

```text
http://127.0.0.1:8000
```

---

## ⚙️ Configuración del archivo .env

El archivo `.env` contiene la configuración local de la aplicación.

**Ubicación:**

```text
Proyecto-SenaServiControl/
└── laravel/
    └── .env
```

### Ejemplo de configuración

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
DB_USERNAME=senaservicontrol_local
DB_PASSWORD=CAMBIAR_POR_TU_CLAVE_LOCAL

SESSION_DRIVER=file
SESSION_LIFETIME=120

CACHE_STORE=file
QUEUE_CONNECTION=sync
```

### Variables de conexión

| Variable | Descripción |
|---|---|
| `DB_CONNECTION` | Motor de base de datos |
| `DB_HOST` | Dirección del servidor MySQL |
| `DB_PORT` | Puerto de conexión |
| `DB_DATABASE` | Nombre de la base de datos |
| `DB_USERNAME` | Usuario local de MySQL |
| `DB_PASSWORD` | Contraseña local de MySQL |

### 🔐 Recomendaciones

- Utiliza un usuario de base de datos exclusivo para el proyecto.
- No compartas contraseñas reales.
- No publiques el archivo `.env`.
- No copies claves privadas al repositorio.
- Genera tu propia `APP_KEY` mediante Artisan.
- Mantén `APP_DEBUG=true` únicamente durante el desarrollo local.
- En producción utiliza `APP_DEBUG=false` y HTTPS.

El archivo `.env.example` debe contener únicamente valores de ejemplo, nunca credenciales privadas.

---

## 🧪 Pruebas y compilación de recursos

**Todos los comandos de esta sección se ejecutan desde `laravel/`.**

### Ejecutar pruebas automatizadas

```powershell
php artisan test
```

Laravel utiliza PHPUnit para organizar las pruebas unitarias y de funcionalidades.

Ubicación:

```text
laravel/
└── tests/
    ├── Unit/
    └── Feature/
```

El comando ejecuta las pruebas disponibles en esas carpetas. Su inclusión no implica que todas las funcionalidades estén cubiertas o que las pruebas hayan sido aprobadas.

### Compilación para desarrollo

```powershell
npm run dev
```

Inicia Vite en modo desarrollo.

### Compilación para producción

```powershell
npm run build
```

Genera los archivos optimizados que Laravel utiliza mediante su integración con Vite.

### Iniciar servidor de Laravel

```powershell
php artisan serve
```

### Limpiar cachés

```powershell
php artisan optimize:clear
```

---

## 📁 Estructura del proyecto

El repositorio contiene archivos en la raíz y una aplicación Laravel independiente.

```text
Proyecto-SenaServiControl/
│
├── Documentos/
├── codigos_php/
├── css/
├── imagenes/
├── js/
│
├── laravel/
│   │
│   ├── app/
│   │   ├── Enums/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   └── Requests/
│   │   └── Models/
│   │
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── public/
│   │
│   ├── resources/
│   │   ├── css/
│   │   ├── js/
│   │   └── views/
│   │
│   ├── routes/
│   │   └── web.php
│   │
│   ├── storage/
│   │
│   ├── tests/
│   │   ├── Unit/
│   │   └── Feature/
│   │
│   ├── .env.example
│   ├── artisan
│   ├── composer.json
│   ├── package.json
│   ├── phpunit.xml
│   ├── vite.config.js
│   └── README.md
│
├── .gitignore
├── .htaccess
├── index.html
└── README.md
```

### Organización de Laravel

| Directorio | Responsabilidad |
|---|---|
| `app/` | Lógica principal de la aplicación |
| `app/Http/Controllers/` | Controladores y operaciones del sistema |
| `app/Http/Requests/` | Validación de solicitudes |
| `app/Models/` | Modelos y relaciones de datos |
| `app/Enums/` | Enumeraciones utilizadas por la aplicación |
| `bootstrap/` | Inicialización del framework |
| `config/` | Configuraciones de Laravel |
| `database/` | Recursos relacionados con la persistencia |
| `public/` | Punto de entrada público |
| `resources/views/` | Vistas Blade |
| `resources/css/` | Estilos frontend |
| `resources/js/` | Código JavaScript |
| `routes/` | Definición de rutas |
| `storage/` | Archivos generados y registros |
| `tests/` | Pruebas automatizadas |

Las carpetas heredadas que están en la raíz se mantienen separadas de la aplicación Laravel. Su presencia no significa que todos sus archivos sean utilizados por el backend Laravel.

---

## 🗄️ Base de datos, migraciones y reportes PDF

### Base de datos

El proyecto utiliza MySQL/MariaDB y espera una base de datos denominada:

```text
sena_serviteca_1
```

La conexión se configura mediante las variables `DB_*` del archivo `.env`.

### Disponibilidad del esquema SQL

La documentación de `laravel/README.md` hace referencia a un archivo ubicado en:

```text
../database/sena_serviteca_1.sql
```

Sin embargo, ese archivo y su contenido no están confirmados en el listado público de archivos de la raíz del repositorio.

Por esta razón, no se debe asumir que el esquema SQL está incluido ni importar respaldos de procedencia desconocida.

### ¿Cómo proporcionar la base de datos a un desarrollador?

Si el esquema no está disponible en el repositorio, el responsable del proyecto debe proporcionar una versión de desarrollo limpia mediante un canal privado y autorizado.

Se recomienda:

1. Preparar un esquema compatible con la versión actual de la aplicación.
2. Excluir nombres, documentos, teléfonos, correos y demás datos personales de clientes.
3. No incluir contraseñas reales ni cuentas de usuarios de producción.
4. Entregar únicamente la estructura necesaria y, cuando sea indispensable, datos ficticios de prueba.
5. Documentar los requisitos de tablas y relaciones sin publicar información confidencial.
6. Configurar la base de datos en el equipo local de cada desarrollador.

### Migraciones

Antes de ejecutar migraciones, es necesario revisar los archivos existentes y comparar su estructura con la base de datos utilizada por la aplicación.

**Precaución:** No ejecutes el siguiente comando sobre una base de datos existente:

```powershell
php artisan migrate:fresh
```

Este comando puede eliminar tablas y datos.

Tampoco ejecutes migraciones sin verificar primero que correspondan al esquema esperado.

### 📄 Reportes PDF

El proyecto utiliza:

```text
barryvdh/laravel-dompdf
```

Esta dependencia permite generar documentos PDF a partir de vistas de Laravel.

El controlador `ReporteController` contempla:

- Generación de PDF descargable.
- Visualización del documento en el navegador.
- Formato de página A4 horizontal.
- Información de servicios y datos asociados.

La generación y visualización de estos documentos están restringidas al administrador.

---

## 🔒 Seguridad, limitaciones y colaboración

### 🛡️ Seguridad identificada

La aplicación utiliza mecanismos propios de Laravel y validaciones implementadas en sus controladores y solicitudes.

Entre los mecanismos identificados se encuentran:

- Middleware de autenticación para proteger rutas privadas.
- Limitación de intentos de inicio de sesión.
- Validación de cuentas activas.
- Regeneración de sesiones durante el inicio de sesión.
- Invalidación de sesiones durante el cierre de sesión.
- Protección CSRF mediante el middleware web de Laravel.
- Escape de salida en las expresiones Blade habituales.
- Consultas mediante Eloquent y Query Builder.
- Validaciones mediante Form Requests.
- Restricciones de acceso según los roles.

### 🔐 Recomendaciones de seguridad

- No publiques archivos `.env`.
- No compartas credenciales de acceso.
- No subas respaldos SQL que contengan información personal.
- No publiques claves de aplicación.
- Utiliza contraseñas seguras.
- Configura permisos mínimos para los usuarios de MySQL.
- Mantén las dependencias actualizadas.
- Desactiva el modo de depuración en producción.
- Utiliza HTTPS en servidores públicos.
- Realiza copias de seguridad protegidas y autorizadas.

**Consideración sobre contraseñas heredadas:** el código de autenticación contempla la compatibilidad con contraseñas almacenadas previamente sin hash y las actualiza a un hash cuando se inicia sesión correctamente. Es recomendable completar la transición y eliminar el mecanismo de comparación de contraseñas en texto plano.

### ⚠️ Limitaciones y aspectos pendientes de verificación

- La disponibilidad de una base de datos inicial depende de que el esquema sea proporcionado por el responsable del proyecto.
- No se debe asumir que todas las entidades disponen de operaciones completas de creación, consulta, actualización y eliminación.
- La ejecución satisfactoria de las pruebas debe comprobarse en cada entorno.
- Las integraciones externas y el despliegue en producción no se consideran funcionalidades confirmadas.
- La estructura y el contenido de las carpetas heredadas deben revisarse por separado de Laravel.

### 🤝 ¿Quieres contribuir?

Las personas interesadas en contribuir pueden utilizar el siguiente procedimiento:

**1.** Crear un Fork del repositorio.

**2.** Clonar su propia copia.

**3.** Crear una rama para el cambio:

```powershell
git checkout -b feature/nueva-funcionalidad
```

**4.** Implementar los cambios.

**5.** Ejecutar las pruebas disponibles:

```powershell
php artisan test
```

**6.** Comprobar la compilación:

```powershell
npm run build
```

**7.** Registrar los cambios:

```powershell
git add .
git commit -m "feat: descripción del cambio"
```

**8.** Subir la rama:

```powershell
git push origin feature/nueva-funcionalidad
```

**9.** Crear un Pull Request hacia el repositorio original.

> No incluyas archivos `.env`, contraseñas, datos personales ni respaldos de bases de datos en tus contribuciones.

---

## 👨‍💻 Autor

<div align="center">

<img src="https://img.shields.io/badge/DESARROLLADOR-JUAN%20JOS%C3%89%20SOTO%20FL%C3%93REZ-00843D?style=for-the-badge" />

<br><br>

**Juan José Soto Flórez**

Desarrollador en formación  
Tecnólogo en Análisis y Desarrollo de Software (ADSO)  
Servicio Nacional de Aprendizaje — SENA

<br>

<a href="https://github.com/juanjosesotoflorez-spec">
<img src="https://img.shields.io/badge/GitHub-Mi%20perfil-181717?style=for-the-badge&logo=github&logoColor=white" />
</a>

<a href="https://github.com/juanjosesotoflorez-spec/Proyecto-SenaServiControl">
<img src="https://img.shields.io/badge/Repositorio-SenaServiControl-00843D?style=for-the-badge&logo=github&logoColor=white" />
</a>

<br><br>

**📂 Repositorio del proyecto**

[Proyecto-SenaServiControl](https://github.com/juanjosesotoflorez-spec/Proyecto-SenaServiControl)

</div>

---

<div align="center">

<img width="100%" src="https://capsule-render.vercel.app/api?type=waving&color=0:006B32,50:00AF50,100:111827&height=130&section=footer" />

### 🚗 SenaServiControl

**Tecnología al servicio de la gestión automotriz.**

Desarrollado con Laravel 12 y PHP 8.2.

<sub>© 2026 SenaServiControl · Proyecto formativo SENA ADSO</sub>

</div>
