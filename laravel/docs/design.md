# Design System y arquitectura de interfaz

## 1. Información general

**SenaServiControl** utiliza un lenguaje visual institucional y operativo para administrar servicios automotrices. El diseño prioriza:

- claridad al consultar estados y datos críticos;
- contraste suficiente para entornos de taller;
- consistencia entre módulos;
- adaptación a escritorio, tableta y móvil;
- uso de componentes reutilizables mediante Blade y Bootstrap 5.

El sistema visual combina el verde institucional con superficies neutras y estados semánticos. La implementación principal se encuentra en:

- `resources/css/app.css`
- `resources/views/layouts/app.blade.php`
- `resources/views/layouts/partials/navigation.blade.php`
- `resources/views/components/service-status.blade.php`
- `resources/js/app.js`

## 2. Principios de diseño

### Claridad operativa

Los estados, acciones principales y errores deben diferenciarse visualmente sin depender solamente del texto o el color.

### Consistencia institucional

Los módulos administrativos usan la misma barra lateral, barra superior, tipografía, superficies, botones y tablas.

### Adaptabilidad

El panel usa CSS de Bootstrap 5, Tailwind CSS 4 y media queries personalizadas. En dispositivos menores de `992px`, la navegación lateral se transforma en un menú offcanvas.

## 3. Paleta de colores

| Rol | Token o color | Uso |
|---|---|---|
| Primary Dark | `#1B4D3E` | Barra lateral, encabezados de tablas y títulos principales |
| Accent Green | `#2E7D32` | Acciones principales, enlaces activos y foco |
| Hover Green | `#236327` | Estado hover y presionado de acciones primarias |
| Soft Mint | `#E8F3EE` | Fondo suave de avisos e indicadores |
| Canvas | `#F4F6F4` | Fondo general del panel |
| Surface | `#FFFFFF` | Tarjetas, formularios, tablas y modales |
| Zebra Row | `#F8FAF9` | Filas alternadas |
| Border | `#DCE3DF` | Divisores, campos y bordes de tarjetas |
| Text Primary | `#111111` | Títulos y contenido prioritario |
| Text Secondary | `#4B5563` | Etiquetas y textos descriptivos |

Los valores institucionales están definidos como variables CSS dentro de `:root` en `resources/css/app.css`.

```css
--green-900: #1B4D3E;
--green-700: #2E7D32;
--canvas: #F4F6F4;
--paper: #FFFFFF;
--line: #DCE3DF;
```

## 4. Estados semánticos

| Estado | Fondo | Texto | Uso |
|---|---|---|---|
| Pendiente | `#FEF3C7` | `#92400E` | Orden creada o esperando asignación |
| En proceso | `#DBEAFE` | `#1E40AF` | Intervención técnica activa |
| Finalizado | `#DCFCE7` | `#166534` | Trabajo técnico completado |
| Entregado | `#F3F4F6` | `#374151` | Ciclo cerrado y vehículo entregado |
| Incidencia o error | `#FEE2E2` | `#991B1B` | Validaciones y errores |

La clase visual de los estados se centraliza en `EstadoServicioEnum::badgeClass()` y se complementa con clases semánticas en el componente Blade `service-status`.

## 5. Tipografía

- Tipografía principal: **Inter**, con fallbacks de sistema.
- Tipografía monoespaciada: **JetBrains Mono** o `Consolas`, para placas, números de orden y valores numéricos.

| Nivel | Tamaño | Peso |
|---|---:|---:|
| H1 del módulo | `24px` | 700 |
| H2 de tarjeta o modal | `18px` | 600 |
| Texto general | `15px` | 400 |
| Encabezado de tabla y etiquetas | `12px` | 600 |

La tipografía se carga mediante Google Fonts y también cuenta con fallbacks locales para mantener legibilidad si no hay conexión externa.

## 6. Arquitectura del layout

### Layout autenticado

El layout de panel se define en `resources/views/layouts/app.blade.php` y contiene:

1. **Sidebar**
   - ancho fijo de `260px` en escritorio;
   - fondo `#1B4D3E`;
   - navegación por rol;
   - ítem activo con fondo `#2E7D32`;
   - borde blanco izquierdo de `3px`;
   - reemplazo por offcanvas en móvil.

2. **Topbar**
   - altura mínima de `64px`;
   - fondo blanco;
   - borde inferior `#DCE3DF`;
   - título de la ruta;
   - búsqueda rápida;
   - menú de perfil y cierre de sesión.

3. **Área de contenido**
   - fondo `#F4F6F4`;
   - padding de `24px` en escritorio;
   - padding reducido en móvil;
   - ancho máximo fluido.

La navegación visible se genera en `resources/views/layouts/partials/navigation.blade.php` según el rol autenticado:

- Administrador;
- Recepcionista;
- Técnico;
- Cliente.

### Landing pública

La página pública se conserva en `index.html` y contiene:

- inicio o hero;
- nosotros;
- servicios;
- contacto;
- enlaces a inicio de sesión y registro Laravel.

En Laravel puede mostrarse mediante la ruta `/inicio`.

## 7. Componentes

### Tarjetas KPI

Las tarjetas del dashboard usan:

- fondo `#FFFFFF`;
- borde `#DCE3DF`;
- radio de `10px`;
- sombra mínima;
- cifra principal monoespaciada;
- etiqueta y explicación secundaria.

### Tablas

Las tablas usan `table-responsive`, encabezado verde oscuro y filas zebra:

- fondo del encabezado: `#1B4D3E`;
- texto del encabezado: `#FFFFFF`;
- letra: `12px`, `600`, mayúscula;
- filas alternadas: `#F8FAF9`;
- hover: `#F0F7F4`;
- padding: `12px 16px`.

Los identificadores de órdenes, placas y cifras deben conservarse con números tabulares cuando sea posible.

### Formularios

- grid de dos columnas en escritorio;
- una columna en móvil;
- inputs con radio de `8px`;
- borde `#DCE3DF`;
- borde verde al enfocar;
- anillo de foco con `rgba(46, 125, 50, 0.15)`;
- errores bajo el campo en `#991B1B`;
- validación HTML y validación Laravel en `FormRequest`.

### Modales

Los modales de seguimiento técnico y salida de vehículos usan Bootstrap 5:

- fondo oscuro semitransparente;
- diálogo centrado;
- tarjeta blanca;
- encabezado con fondo suave;
- botones de cancelar y confirmar claramente separados;
- cierre mediante botón, teclado o fondo según Bootstrap;
- el contenido debe permanecer dentro del viewport.

La implementación usa `data-bs-toggle="modal"` y `bootstrap.Modal`. Los scripts auxiliares están en `resources/js/app.js`.

### Estados y badges

Los estados deben usarse mediante el componente Blade `<x-service-status :status="$estado" />`. No deben cambiarse individualmente por cada módulo.

Las clases semánticas existentes son compatibles con las propiedades visuales:

- Pendiente: `status-pending`;
- En proceso: `status-progress`;
- Finalizado: `status-finished`;
- Entregado: `status-delivered`.

## 8. Responsive

### Escritorio

- Sidebar fijo de `260px`;
- tablas desplazables horizontalmente;
- tarjetas en rejillas Bootstrap.

### Tabletas y móviles

- Sidebar reemplazado por offcanvas;
- Topbar reorganizado;
- búsqueda ocupando una segunda fila;
- formularios de una columna;
- botones principales con ancho completo cuando sea necesario.

El punto principal de adaptación comienza en `991.98px`, con ajustes adicionales menores a `560px`.

## 9. Iconografía

Se usa Font Awesome 6.7.2 mediante el layout principal.

Ejemplos:

- resumen: `chart-line`;
- servicios: `screwdriver-wrench`;
- solicitudes: `inbox`;
- vehículos: `car`;
- clientes: `users`;
- reportes: `file-pdf`;
- administración: `user-shield`.

Los iconos deben mantener una etiqueta textual visible o un `aria-label` equivalente.

## 10. Accesibilidad

- conservar niveles de encabezado claros;
- usar `aria-label`, `aria-current` y `aria-controls` en navegación y modales;
- incluir `aria-live` en avisos relevantes;
- mantener foco visible;
- no transmitir información únicamente mediante color;
- usar contraste suficiente;
- mantener textos alternativos en imágenes informativas;
- evitar saltos de navegación mediante el enlace «Saltar al contenido».

## 11. Reportes PDF

Los reportes generados con Dompdf conservan una versión reducida del sistema visual:

- formato A4 horizontal para reportes consolidados;
- encabezado institucional;
- fecha y usuario emisor;
- tabla con bordes `#CBD5E1`;
- fila de totales con fondo `#E8F3EE`;
- bloques finales de firmas o observaciones;
- sin dependencias visuales de Bootstrap o JavaScript.

Los archivos principales se encuentran en `resources/views/reportes/`.

## 12. Archivos relacionados

- Estilos: `resources/css/app.css`
- JavaScript: `resources/js/app.js`
- Layout: `resources/views/layouts/app.blade.php`
- Navegación: `resources/views/layouts/partials/navigation.blade.php`
- Estado de servicio: `resources/views/components/service-status.blade.php`
- Servicios: `resources/views/servicios/`
- Dashboard: `resources/views/dashboard/index.blade.php`
- Reportes: `resources/views/reportes/`
- Rutas: `routes/web.php`
- Documentación técnica: `docs/`

