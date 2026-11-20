# Plan — Unidad 3: Programación del lado del cliente

**Proyecto:** Congreso SysIoT 2026 (`/opt/lampp/htdocs/ProyectoPW`)
**Alcance:** únicamente JavaScript del lado del cliente. **Fuera de alcance:** publicación en servidor y GitHub.
**Base técnica:** PHP 8 nativo + MariaDB + Apache (LAMPP). Sin Composer, sin npm, sin build.
**Librerías elegidas:** jQuery 3.7.1 + Slick 1.8.1 (carrusel) y **Chart.js 4** (librería libre).

---

## 1. Diagnóstico inicial

| Criterio de la rúbrica | Estado real hoy | Acción |
|---|---|---|
| Validación de **todos** los formularios con expresión regular | **No existe**: los 4 formularios llevan `novalidate` y no hay JS; solo valida el servidor | Fase 1 |
| Flyer / carrusel con JS (puede ser jQuery) | **No existe**: cero jQuery en el proyecto | Fase 2 |
| 3 usos del DOM | **2**: `programa.js` (filtros de agenda) y `ponencia-detalle.js` (detalle dinámico) | Fase 4 |
| Librería JS libre | **No existe** | Fase 3 (Chart.js) |

### Formularios del proyecto (inventario)

| Archivo | Campos | Estado |
|---|---|---|
| `registro.php` | nombre, correo, password, confirmar | `novalidate`, sin JS |
| `login-cliente.php` | correo, password | `novalidate`, sin JS |
| `admin/login.php` | correo, password | `novalidate`, sin JS |
| `admin/productos.php` | nombre, descripción, precio, stock | `novalidate`, sin JS |
| `carrito.php` | cantidad (uno por producto) | sin JS |
| `programa.php` / `memoria.php` | 3 `<select>` de filtro | solo `programa.php` tiene JS |

> Los dos formularios de filtros no requieren validación: son `select` cerrados, sin entrada libre.

---

## 2. Fase 1 — Validación del lado del cliente (obligatoria)

**Entregable:** `js/validacion.js`, incluido en las 5 páginas con formulario.

### Diseño

Un solo archivo **declarativo**, no cinco scripts distintos. Los campos se marcan en el HTML con un atributo:

```html
<input type="text" id="nombre" name="nombre" data-validar="nombre" ... />
```

El módulo, en un `DOMContentLoaded`, recorre `[data-validar]` de cada formulario, aplica la regla correspondiente y detiene el envío con `preventDefault()` si algo falla.

### Reglas (espejo de la validación del servidor)

Deben coincidir con lo que ya valida PHP, o el usuario recibe un mensaje distinto en cada lado:

| Campo | Regla cliente | Origen en el servidor |
|---|---|---|
| `nombre` | `/^[\p{L}\s'.-]{3,100}$/u` | `registro.php:31` (`mb_strlen` 3–100) |
| `correo` | `/^[\w.+-]+@[\w-]+(\.[\w-]{2,})+$/` | `registro.php:35` (`FILTER_VALIDATE_EMAIL`) |
| `password` | mínimo 8 caracteres | `registro.php:39` (`LARGO_MINIMO_PASSWORD`) |
| `confirmar` | igual a `#password` | `registro.php` (comparación) |
| `precio` | `/^\d+(\.\d{1,2})?$/` y `> 0` | `admin/productos.php` |
| `stock` | `/^\d+$/` | `admin/productos.php` |
| `cantidad` | `/^\d+$/` y `0 ≤ n ≤ max` | `carrito.php` |

> `\p{L}` acepta acentos sin listar `áéíóúñ` a mano. Es más seguro que una clase manual.

### Comportamiento

1. **Al enviar:** valida todos los campos; si hay error, `preventDefault()`, marca el primero y le da foco.
2. **Al salir del campo (`blur`):** valida ese campo; al escribir (`input`) limpia el error si ya es válido.
3. **Error visible:** `<p class="campo-error" role="alert">` insertado con `createElement` después del input, más `aria-invalid="true"` en el campo.
4. **`novalidate` se mantiene.** Es deliberado: evita que los globos nativos del navegador compitan con nuestros mensajes. La validación nativa (`required`, `type="email"`) queda como red de seguridad; el JS es la capa de UX.
5. **El servidor sigue siendo la autoridad.** No se toca ninguna validación PHP: si alguien salta el JS, la app se comporta igual que hoy.

### Ajustes por formulario

- `registro.php`: además del regex, comparar `password` con `confirmar` en tiempo real.
- `admin/productos.php`: `precio` y `stock` usan el mismo módulo con sus reglas propias.
- `carrito.php`: cada formulario de cantidad toma su límite del atributo `max` que ya genera el HTML. No se guarda en JS.

### Verificación

Matriz manual por formulario: vacío, formato inválido (correo sin `@`, precio negativo, letras en stock, contraseñas distintas), dato válido, y envío con el JS desactivado para confirmar que el servidor sigue validando.

---

## 3. Fase 2 — Carrusel con jQuery

**Decisión:** jQuery 3.7.1 + Slick 1.8.1, **servidos localmente** (no CDN).

| Opción | Ventaja | Costo |
|---|---|---|
| CDN | cero archivos en el repo | depende de internet; si falla en la demo, el criterio vale 0 |
| **Local (elegida)** | funciona sin red, la demo nunca falla | ~90 KB en el repo |

### ⚠️ Restricción detectada

`lib/.htaccess` **bloquea todo acceso HTTP** a `lib/` (`Require all denied`), que es donde vive FPDF. Si se copia jQuery ahí, el navegador recibe **403** y el carrusel no carga.

**Solución:** crear `assets/vendor/` para las librerías de front-end (`jquery.min.js`, `slick.min.js`, `slick.css`, `slick-theme.css`) y dejar `lib/` para lo que solo se usa en el servidor.

### Ubicación

Carrusel de **3 diapositivas en el hero de `index.php`** (`.hero`, línea 17). Es lo primero que ve quien revisa y no depende de datos de la BD ni duplica contenido.

Contenido sugerido: (1) el congreso y sus fechas, (2) ponentes y ejes temáticos, (3) inscripciones con llamada a la acción al catálogo.

### Integración

`includes/footer.php:25` ya imprime `$scripts_pie`. Se aprovecha:

```php
// index.php, antes del include del header
$scripts_pie = '<script src="assets/vendor/jquery.min.js"></script>'
             . '<script src="assets/vendor/slick.min.js"></script>'
             . '<script src="js/inicio.js"></script>';
```

El orden importa: jQuery **antes** que Slick. `js/inicio.js` inicializa el carrusel (`$('.hero-slider').slick({...})`) y no hace nada si el plugin no cargó, para no romper la página.

### Cuidados

- `autoplay` con `pauseOnHover` y `pauseOnFocus`; respetar `prefers-reduced-motion`.
- CSS del carrusel al final de `styles.css`, con las variables de color que ya existen (`--primario`, etc.).
- Revisar en móvil: `responsive` con `breakpoint: 768` (el sitio ya es responsive con 7 `@media`).

---

## 4. Fase 3 — Librería JS libre: Chart.js

**Ubicación:** `admin/dashboard.php`.

El dashboard ya calcula productos, pedidos, ingresos y pendientes (`admin/dashboard.php:22-72`), pero todo son números sueltos.

**Gráfico elegido: cupos disponibles por producto** (barras horizontales). El plan original decía «ingresos por día», pero la base tiene **0 pedidos**: ese gráfico habría salido completamente vacío en la revisión. Los cupos existen siempre, y el color avisa cuando un producto baja de cinco.

- Requiere **una consulta extra**: `SELECT nombre, stock FROM productos WHERE activo = 1 ORDER BY stock ASC`.
- Los datos se imprimen en `<script type="application/json" id="datos-cupos">` con `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT` y se leen con `JSON.parse`. **Nunca** concatenar PHP dentro de JS: rompe comillas y abre la puerta a inyección.
- Archivos: `assets/vendor/chart.umd.js` + `js/dashboard.js`.
- Cargar solo en esa página, vía `$scripts_pie`.

---

## 5. Fase 4 — Tercer uso del DOM

`memoria.php` tiene **filtros muertos**: el formulario `#filtros-memoria` (línea 26) y el comentario «solo diseño» lo confirman. No existe ningún JS que los escuche, así que hoy son controles que no hacen nada.

**Entregable:** `js/memoria.js`, con el mismo patrón que `programa.js`:

- Lee `#filtro-edicion` y `#filtro-categoria`.
- Filtra `#lista-documentos li` por `data-edicion` y `data-categoria`.
- Oculta los documentos que no coinciden (`hidden`), muestra un aviso `role="status"` cuando no hay resultados y actualiza la URL con `history.replaceState`.

Se resuelve el criterio **y** se arregla una funcionalidad rota: el tercer uso no es un adorno, es deuda saldada.

### Tabla de evidencia para la revisión

| # | Archivo | Qué hace | APIs del DOM |
|---|---|---|---|
| 1 | `js/programa.js` | Filtra la agenda por día, tipo y sala | `querySelectorAll`, `dataset`, `hidden`, `createElement`, `agenda.after`, `history.replaceState` |
| 2 | `js/ponencia-detalle.js` | Rellena la ficha de ponencia desde `?id=` | `getElementById`, `textContent`, `innerHTML`, `setAttribute`, `dataset`, `document.title` |
| 3 | `js/memoria.js` (nuevo) | Filtra la memoria por edición y categoría | `querySelectorAll`, `dataset`, `hidden`, `createElement` |
| + | `js/validacion.js` (Fase 1) | Inserta mensajes de error junto a cada campo | `createElement`, `appendChild`, `setAttribute`, `classList` |

---

## 6. Orden de ejecución

| # | Fase | Archivos nuevos | Archivos tocados |
|---|---|---|---|
| 1 | Validación cliente (obligatoria) | `js/validacion.js` | `registro.php`, `login-cliente.php`, `admin/login.php`, `admin/productos.php`, `carrito.php`, `styles.css` |
| 2 | Carrusel jQuery | `js/inicio.js`, `assets/vendor/*` | `index.php`, `styles.css` |
| 3 | Chart.js | `js/dashboard.js`, `assets/vendor/chart.umd.js` | `admin/dashboard.php` |
| 4 | `memoria.js` (3er DOM) | `js/memoria.js` | `memoria.php` |

Las cuatro fases son independientes entre sí. La 1 va primero porque es la única marcada como obligatoria.

### Cierre

1. Recorrido de 2 minutos cubriendo los 4 criterios: validación fallando y pasando, carrusel, gráfico y filtros de memoria.
2. Sección «Unidad 3» en `README.md` con la tabla de usos del DOM.
3. Commits por unidad de trabajo: `feat(validacion)`, `feat(carrusel)`, `feat(dashboard-chart)`, `feat(memoria-filtros)`. Sin push.

---

## 7. Restricciones que no se tocan

- Validación PHP existente (el servidor sigue siendo la autoridad).
- `api/paypal/`, `includes/pdf.php` y `lib/fpdf/` (pago y recibo fuera de alcance).
- `config/` y credenciales.
- El `.htaccess` raíz, salvo que se agregue `assets/` explícitamente a alguna regla.
