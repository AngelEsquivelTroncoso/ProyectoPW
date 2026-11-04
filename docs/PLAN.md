# Plan — Proyecto PHP (Cliente + Administrador)

**Apertura:** domingo 1 de noviembre de 2026, 00:00
**Cierre:** martes 3 de noviembre de 2026, 12:59

---

## 1. Objetivo y alcance

Convertir el sitio estático del congreso (HTML + CSS) en una aplicación **PHP + MySQL** con dos sistemas:

| Sistema | Usuario | Función |
|---|---|---|
| **Cliente** | Visitante / inscrito | Catálogo, carrito, pago con PayPal, recibo PDF. |
| **Administrador** | Admin | Ve y administra productos (stock/cupos), pedidos y ventas. |

**Supuesto de trabajo:** el "producto" son las **inscripciones y talleres del congreso** (Ponente $1,500 / Participante $800 / talleres), porque el sitio ya los vende en `pagos.html`. El **inventario = cupos disponibles** de cada producto.

> Si el curso espera una tienda de productos genéricos, el modelo es el mismo: solo cambian los datos semilla.

### Requisitos → dónde se cumplen

| Requisito | Implementación |
|---|---|
| Base de datos | MySQL/MariaDB administrada con **phpMyAdmin** (tablas `usuarios`, `productos`, `pedidos`, `detalle_pedido`) |
| Variables de sesión | `$_SESSION['usuario']`, `$_SESSION['carrito']`, `$_SESSION['admin']`, mensajes flash |
| Vender con PayPal | SDK JS de PayPal (sandbox) + captura en servidor |
| Disminución de inventario | `UPDATE productos SET stock = stock - ?` dentro de una transacción al confirmar el pago |
| Recibo en PDF | FPDF generado al capturar el pago, guardado en `/recibos/` |
| Admin ve todo | Panel con tabla de productos (datos + stock) y tabla de pedidos/ventas |
| Publicar en servidor | Despliegue en LAMPP (`htdocs`) y opción de hosting gratuito |

---

## 2. Arquitectura

**Stack:** PHP 8 (nativo, sin framework, coherente con el curso), MySQL/MariaDB, **phpMyAdmin** (gestión visual de la BD), Apache (LAMPP), CSS existente.

> **phpMyAdmin es solo la herramienta de administración.** La app PHP no pasa por él: se conecta directo con **PDO** a `localhost`. phpMyAdmin sirve para crear/importar tablas y revisar datos.

```
ProyectoPW/
├─ index.php                  ← home (antes index.html)
├─ catalogo.php               ← productos desde BD (antes pagos.html)
├─ carrito.php                ← carrito en sesión
├─ checkout.php               ← resumen + botón PayPal
├─ pago-exitoso.php           ← captura + genera recibo
├─ recibo.php                 ← visor/descarga del PDF
├─ styles.css                 ← se reutiliza tal cual
├─ config/
│  └─ db.php                  ← conexión PDO (credenciales fuera del repo idealmente)
├─ includes/
│  ├─ auth.php                ← helpers de sesión y roles
│  ├─ header.php / footer.php ← layout compartido
│  └─ carrito.php             ← funciones del carrito (add/remove/total)
├─ admin/
│  ├─ login.php
│  ├─ dashboard.php
│  ├─ productos.php           ← tabla + alta/edición/baja
│  └─ pedidos.php             ← tabla de ventas
├─ api/
│  └─ paypal/
│     ├─ crear-orden.php
│     └─ capturar-orden.php
├─ lib/fpdf/                  ← librería PDF (sin composer)
├─ recibos/                   ← PDFs generados (protegida)
└─ sql/
   └─ schema.sql              ← creación de BD + datos semilla
```

**Regla de oro:** toda página PHP nueva reutiliza `header.php` / `footer.php` y `styles.css`. No se rediseña nada; solo se convierte el HTML en plantillas PHP.

---

## 3. Base de datos (con phpMyAdmin)

### 3.1 Crear la base con phpMyAdmin

1. Arrancar LAMPP: `sudo /opt/lampp/lampp start`.
2. Abrir **http://localhost/phpmyadmin**.
3. Ingresar con usuario `root` y contraseña vacía (por defecto en LAMPP).
4. Crear la base: pestaña **Bases de datos** → nombre `proyecto_pw` → cotejamiento `utf8mb4_unicode_ci` → **Crear**.
5. Seleccionar `proyecto_pw` → pestaña **Importar** → elegir `sql/schema.sql` → **Continuar**. (Alternativa: pegar el SQL en la pestaña **SQL** y ejecutar.)
6. Verificar en **Estructura** que existen las 4 tablas y en **Examinar** que están los datos semilla.

> `sql/schema.sql` es la fuente de verdad. phpMyAdmin solo lo ejecuta. Así la base se puede reinstalar en cualquier servidor sin hacer clic a mano.

**Credenciales de conexión para `config/db.php` (LAMPP por defecto):**

| Parámetro | Valor |
|---|---|
| Host | `localhost` (o `127.0.0.1`) |
| Base | `proyecto_pw` |
| Usuario | `root` |
| Contraseña | *(vacía)* |

### 3.2 Esquema (`sql/schema.sql`)

```sql
CREATE DATABASE proyecto_pw CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE proyecto_pw;

CREATE TABLE usuarios (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  nombre        VARCHAR(100) NOT NULL,
  correo        VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  rol           ENUM('cliente','admin') NOT NULL DEFAULT 'cliente',
  creado_en     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE productos (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  nombre      VARCHAR(150) NOT NULL,
  descripcion TEXT,
  precio      DECIMAL(10,2) NOT NULL,
  stock       INT NOT NULL DEFAULT 0,      -- cupos disponibles
  activo      TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE pedidos (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id        INT NOT NULL,
  total             DECIMAL(10,2) NOT NULL,
  estado            ENUM('pendiente','pagado','cancelado') DEFAULT 'pendiente',
  paypal_order_id   VARCHAR(100),
  paypal_captura_id VARCHAR(100),
  creado_en         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

CREATE TABLE detalle_pedido (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  pedido_id       INT NOT NULL,
  producto_id     INT NOT NULL,
  cantidad        INT NOT NULL,
  precio_unitario DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (pedido_id)   REFERENCES pedidos(id),
  FOREIGN KEY (producto_id) REFERENCES productos(id)
);
```

**Datos semilla:** 1 admin (`admin@congreso.test`), 2–4 productos (Ponente, Participante, Taller ESP32, Taller Seguridad) con `stock` inicial.

---

## 4. Sistema cliente (flujo)

1. **Catálogo** (`catalogo.php`): consulta `productos WHERE activo = 1`, muestra precio y cupos.
2. **Carrito** (`carrito.php`): `$_SESSION['carrito'] = [producto_id => cantidad]`. Valida contra `stock`.
3. **Checkout** (`checkout.php`): exige sesión de cliente; muestra total; renderiza botón PayPal.
4. **Pago**:
   - `crear-orden.php`: crea la orden en PayPal con el total real (nunca confiar en el monto del cliente).
   - `capturar-orden.php`: captura, verifica estado `COMPLETED`, y en una **transacción SQL**:
     - crea `pedido` + `detalle_pedido`,
     - **descuenta stock** con `UPDATE ... WHERE stock >= cantidad` (evita sobreventa),
     - marca `pagado`,
     - genera el **PDF** en `/recibos/`,
     - vacía el carrito.
5. **Recibo** (`recibo.php`): muestra el PDF solo al dueño del pedido o al admin.

---

## 5. Sistema administrador

- **Login** (`admin/login.php`): verifica `password_verify()` y `rol = 'admin'`; guarda `$_SESSION['admin']`.
- **Dashboard** (`admin/dashboard.php`): conteos (productos, pedidos pagados, ingresos totales).
- **Productos** (`admin/productos.php`): tabla con `id, nombre, precio, stock, activo` + alta/edición/baja.
- **Pedidos** (`admin/pedidos.php`): tabla de ventas con cliente, total, estado, fecha y enlace al PDF.
- **Protección:** cada archivo de `admin/` incluye `auth.php`, que redirige al login si no hay sesión de admin.

---

## 6. Integración PayPal (sandbox)

1. Crear app en **developer.paypal.com** → obtener `client_id` (sandbox).
2. Incluir el SDK JS en `checkout.php`.
3. Botón → `createOrder` (server) → `onApprove` → `capture` (server).
4. Verificar en servidor el monto y el estado antes de dar por pagado.
5. **Modo simulación** (`?demo=1`): un botón alterno que registra el pedido sin red, para que la demo funcione aunque el sandbox falle. Esto es clave para la calificación.

> Seguridad: el `client_secret` y el `client_id` van en un archivo de configuración **no versionado**; nunca en el HTML.

---

## 7. Recibo PDF

- Librería **FPDF** (un solo directorio, sin Composer).
- Contenido: logo/texto, folio (`SYS-2026-000X`), datos del cliente, detalle de productos, cantidades, total, fecha y estado del pago.
- Se guarda en `recibos/pedido-{id}.pdf` y se sirve con validación de sesión.

---

## 8. Cronograma (3 días)

### Día 1 — domingo 1 nov (base + cliente)
- [ ] Escribir `sql/schema.sql` e importarlo con **phpMyAdmin** (crear BD + datos semilla).
- [ ] `config/db.php` (PDO) + `includes/auth.php` + layout.
- [ ] Convertir `index.html` → `index.php` y `pagos.html` → `catalogo.php` (consulta real a BD).
- [ ] Carrito en sesión (`add`, `remove`, `total`) y página `carrito.php`.

### Día 2 — lunes 2 nov (pago + PDF + inventario)
- [ ] `checkout.php` con botón PayPal sandbox.
- [ ] `api/paypal/crear-orden.php` y `capturar-orden.php`.
- [ ] Transacción de pedido + **descuento de stock**.
- [ ] Generar **PDF** con FPDF y `recibo.php`.
- [ ] Modo simulación de pago.

### Día 3 — martes 3 nov (admin + despliegue, cierre 12:59)
- [ ] `admin/login.php`, `dashboard.php`, `productos.php`, `pedidos.php`.
- [ ] Proteger `admin/` con sesión y rol.
- [ ] Pruebas de flujo completo (cliente paga → stock baja → admin ve la venta → PDF).
- [ ] Desplegar en LAMPP (`/opt/lampp/htdocs/ProyectoPW`) y, si se pide, subir a hosting gratuito.
- [ ] Checklist final y entrega.

> El día 3 es corto (cierra a las 12:59). Por eso el núcleo (cliente + pago + PDF) debe quedar cerrado el día 2; el admin se construye el día 3 temprano.

---

## 9. Checklist de entrega

- [ ] Base de datos creada en **phpMyAdmin** e importable desde `schema.sql`.
- [ ] Cliente: catálogo, carrito, checkout, pago, recibo PDF.
- [ ] Inventario se descuenta correctamente (sin stock negativo).
- [ ] Admin: login, tabla de productos con datos, tabla de ventas.
- [ ] Sesiones usadas en cliente y admin.
- [ ] PayPal operativo (o modo simulación documentado).
- [ ] Publicado y accesible por URL.
- [ ] `README` breve con credenciales de prueba y pasos de instalación.

---

## 10. Riesgos y mitigación

| Riesgo | Mitigación |
|---|---|
| Sandbox de PayPal lento o caído | Modo simulación `?demo=1` |
| Sobreventa de cupos | `UPDATE ... WHERE stock >= cantidad` + transacción |
| Pérdida de tiempo en estilos | Reutilizar `styles.css` sin rediseñar |
| Día 3 muy corto | Núcleo cerrado el día 2 |
| Credenciales expuestas | `config/` fuera del control de versiones |
| Hosting sin phpMyAdmin | Importar `schema.sql` desde el panel del hosting (mismo archivo) |
