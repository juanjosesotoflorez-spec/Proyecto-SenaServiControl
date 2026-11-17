 
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

