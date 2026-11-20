# Proyecto PW — Congreso SysIoT 2026

Sitio web del **Congreso Internacional de Ingeniería en Sistemas e IoT** con dos sistemas sobre la misma base de datos:

- **Sistema cliente:** catálogo de inscripciones y talleres, carrito, cuentas de usuario, pago con PayPal (o modo demostración) y recibo en PDF.
- **Sistema administrador:** métricas del congreso, alta/edición/baja de productos y listado de ventas con acceso al recibo.

Construido con **PHP 8 nativo + MariaDB/MySQL + Apache**, reutilizando el diseño original (`styles.css`).

**Repositorio público:** <https://github.com/AngelEsquivelTroncoso/ProyectoPW>

---

## Requisitos

| Componente | Versión usada | Notas |
|---|---|---|
| PHP | 8.2.12 | Extensiones `pdo_mysql`, `curl`, `iconv` |
| MariaDB / MySQL | 10.4.32 | Motor InnoDB |
| Apache | XAMPP / LAMPP | `mod_headers` y `AllowOverride All` |
| phpMyAdmin | Incluido en LAMPP | Opcional, para importar la base |

No hay Composer, ni `npm`, ni paso de build. FPDF ya está incluido en `lib/fpdf/`.

---

## Instalación local (LAMPP)

### 1. Copiar el proyecto

La carpeta debe quedar dentro del webroot de Apache:

```
/opt/lampp/htdocs/ProyectoPW
```

### 2. Iniciar los servicios

```bash
sudo /opt/lampp/lampp start
```

### 3. Crear la base de datos

**Opción A — phpMyAdmin**

1. Abrir `http://localhost/phpmyadmin`.
2. Entrar con usuario `root` y contraseña vacía (valor por defecto de LAMPP).
3. Pestaña **Importar** → seleccionar `sql/schema.sql` → **Continuar**.

**Opción B — terminal**

```bash
/opt/lampp/bin/mysql -u root < sql/schema.sql
```

> `sql/schema.sql` usa `DROP TABLE IF EXISTS`. **Reimportarlo borra todos los pedidos y usuarios registrados.** Úsalo solo para reiniciar la base.

### 4. Configurar la conexión

Editar `config/db.php` si tus credenciales no son las de LAMPP:

```php
const DB_HOST = 'localhost';
const DB_NAME = 'proyecto_pw';
const DB_USER = 'root';
const DB_PASS = '';
```

### 5. Abrir el sitio

```
http://localhost/ProyectoPW/
```

> No uses Live Server ni abras los `.php` con doble clic: PHP necesita Apache. Si Apache está apagado verás el código fuente o un error de descarga.

---

## Credenciales de prueba

| Rol | Correo | Contraseña | Acceso |
|---|---|---|---|
| Administrador | `admin@congreso.test` | `admin123` | `http://localhost/ProyectoPW/admin/login.php` |
| Cliente | `cliente@congreso.test` | `cliente123` | `http://localhost/ProyectoPW/login-cliente.php` |

También puedes crear un cliente nuevo desde `registro.php`.

---

## Flujo de uso

### Cliente

1. **Catálogo** (`catalogo.php`): elegir inscripción o taller y la cantidad. El stock mostrado son los cupos disponibles.
2. **Carrito** (`carrito.php`): modificar cantidades o quitar productos. El total se calcula con los precios de la base, nunca con lo que envía el navegador.
3. **Iniciar sesión o registrarse**: el checkout exige cuenta.
4. **Checkout** (`checkout.php`): revisar el resumen y pagar con PayPal o usar el botón de demostración.
5. **Confirmación** (`pago-exitoso.php`): folio, estado y detalle.
6. **Recibo PDF** (`recibo.php?pedido=N`): se abre en el navegador; con `?descargar=1` se descarga.

### Administrador

1. Entrar por `admin/login.php`.
2. **Dashboard**: productos, pedidos, ingresos y alertas de stock bajo.
3. **Productos**: crear, editar, desactivar y reactivar. La baja es **lógica** (`activo = 0`) para no romper el historial de ventas.
4. **Pedidos**: filtrar por estado y abrir el recibo de cualquier venta.

---

## Modo demostración (sin PayPal)

En `checkout.php` aparece el botón **“Simular pago aprobado”**. Sirve para presentar el proyecto sin credenciales ni cobros reales.

Para desactivarlo en un entorno real, define la variable de entorno `PAYPAL_DEMO=0` o cambia `PAYPAL_DEMO_HABILITADO` a `false` en `config/paypal.php`. Con el modo apagado, el botón no se muestra y la API rechaza cualquier solicitud de demostración con 403.

Qué hace exactamente:

- Genera una orden ficticia `DEMO-…` y una captura `DEMO-CAP-…`.
- Registra el pedido como **pagado**.
- Descuenta stock con la misma transacción que un pago real.
- Permite generar el PDF.

El modo demostración no contacta a PayPal.

---

## Configurar PayPal Sandbox

1. Crear una app en <https://developer.paypal.com> (modo Sandbox).
2. Copiar **Client ID** y **Secret**.
3. Editarlos en `config/paypal.php`:

```php
const PAYPAL_CLIENT_ID     = 'TU_CLIENT_ID';
const PAYPAL_CLIENT_SECRET = 'TU_SECRET';
const PAYPAL_SANDBOX       = true;
```

También se aceptan las variables de entorno `PAYPAL_CLIENT_ID` y `PAYPAL_CLIENT_SECRET`, que tienen prioridad sobre las constantes.

Con credenciales configuradas, `checkout.php` carga el SDK de PayPal y muestra los botones reales. Para producción:

```php
const PAYPAL_SANDBOX = false; // y usar credenciales Live
```

**Seguridad del monto:** el importe siempre se recalcula en el servidor. La orden se crea con el total de la base y, al capturar, se compara contra la instantánea guardada en sesión. Si no coincide, se rechaza.

---

## Estructura del proyecto

```
ProyectoPW/
├── index.php                 Inicio con productos destacados
├── catalogo.php              Catálogo de inscripciones y talleres
├── carrito.php               Carrito en sesión
├── checkout.php              Resumen y botones de pago
├── pago-exitoso.php          Confirmación del pedido
├── recibo.php                Recibo PDF (dueño o admin)
├── registro.php              Alta de cliente
├── login-cliente.php         Inicio de sesión de cliente
├── logout.php                Cierre de sesión
├── styles.css                Diseño compartido
├── programa.php              Agenda con filtros
├── ponencias.php             Listado de ponencias
├── ponencia-detalle.php      Detalle de una ponencia
├── memoria.php               Documentos de ediciones anteriores
│
├── config/
│   ├── db.php                Conexión PDO
│   └── paypal.php            Credenciales y helpers de PayPal
│
├── includes/
│   ├── session.php           Sesión segura, flash y CSRF
│   ├── auth.php              Sesiones y control de acceso
│   ├── funciones.php         Escape, precios, redirecciones, JSON
│   ├── productos.php         Consultas de productos
│   ├── carrito.php           Lógica del carrito
│   ├── pedidos.php           Venta atómica y consultas de pedidos
│   ├── paypal.php            Cliente HTTP de PayPal
│   ├── pdf.php               Generación del recibo con FPDF
│   ├── errores.php           Manejador global de excepciones
│   ├── header.php            Cabecera y navegación
│   └── footer.php            Pie de página
│
├── admin/
│   ├── login.php             Acceso de administrador
│   ├── dashboard.php         Métricas
│   ├── productos.php         Gestión de productos
│   ├── pedidos.php           Ventas
│   └── _navegacion.php       Subnavegación del panel
│
├── api/paypal/
│   ├── crear-orden.php       Crea la orden (JSON)
│   └── capturar-orden.php    Captura y registra la venta (JSON)
│
├── js/
│   ├── validacion.js         Validación de formularios (regex)
│   ├── inicio.js             Carrusel del hero (jQuery + Slick)
│   ├── programa.js           Filtros de la agenda
│   ├── memoria.js            Filtros de la memoria
│   ├── ponencia-detalle.js   Detalle según ?id=
│   └── dashboard.js          Gráfico de cupos (Chart.js)
│
├── assets/vendor/            jQuery 3.7.1, Slick 1.8.1, Chart.js 4.4.6
├── memoria/                  Documentos PDF de la memoria
├── lib/fpdf/                 FPDF 1.9
├── sql/schema.sql            Esquema + datos semilla
└── docs/                     Planes de trabajo (no necesarios en producción)
```

Las páginas `programa.php`, `ponencias.php`, `memoria.php` y `ponencia-detalle.php` son contenido informativo, ahora con filtros de agenda, filtros de la memoria, detalle por `?id=` y PDFs reales.

---

## JavaScript del lado del cliente (Unidad 3)

Todo el JavaScript es nativo, salvo tres librerías servidas **localmente** desde `assets/vendor/` para que el sitio funcione sin conexión a internet.

> Las librerías de front-end no viven en `lib/`: ese directorio está cerrado por `.htaccess` (`Require all denied`) y el navegador recibiría un 403.

| Librería | Versión | Archivo | Para qué |
|---|---|---|---|
| jQuery | 3.7.1 | `assets/vendor/jquery.min.js` | Base del carrusel |
| Slick | 1.8.1 | `assets/vendor/slick.min.js` + `slick.css` | Carrusel del hero |
| Chart.js | 4.4.6 | `assets/vendor/chart.umd.js` | Gráfico del panel |

### Validación de formularios

`js/validacion.js` es un módulo declarativo: cada campo se marca en el HTML con `data-validar="<regla>"` y el módulo aplica la regla, muestra el mensaje y detiene el envío. Cubre los cinco formularios del sitio: `registro.php`, `login-cliente.php`, `admin/login.php`, `admin/productos.php` y `carrito.php`.

| Campo | Regla | Validación en el servidor |
|---|---|---|
| `nombre` | letras, espacios, guion y punto, 3 a 100 | `registro.php:31` |
| `correo` | expresión regular de correo, máximo 150 | `registro.php:35` |
| `password` | mínimo 8 caracteres | `registro.php:39` |
| `confirmar` | igual a `password` | `registro.php:43` |
| `precio` | decimal mayor que cero, hasta dos decimales | `admin/productos.php` |
| `stock` | entero igual o mayor que cero | `admin/productos.php` |
| `cantidad` | entero dentro de `min`/`max` del campo | `carrito.php` |

Las reglas replican la validación del servidor, que **sigue siendo la autoridad**: desactivar JavaScript no permite guardar datos inválidos. Los formularios conservan `novalidate` para que los globos nativos no compitan con los mensajes propios; los errores usan `role="alert"`, `aria-invalid` y `aria-describedby`.

### Uso del DOM

| # | Archivo | Qué hace | API del DOM |
|---|---|---|---|
| 1 | `js/programa.js` | Filtra la agenda por día, tipo y sala | `querySelectorAll`, `dataset`, `hidden`, `createElement`, `after`, `history.replaceState` |
| 2 | `js/ponencia-detalle.js` | Rellena la ficha desde `?id=` | `getElementById`, `textContent`, `innerHTML`, `setAttribute`, `document.title` |
| 3 | `js/memoria.js` | Filtra la memoria por edición y categoría | `querySelectorAll`, `dataset`, `hidden`, `createElement`, `after` |
| 4 | `js/validacion.js` | Inserta y retira los mensajes de error de cada campo | `createElement`, `insertAdjacentElement`, `setAttribute`, `classList` |

Los tres primeros se apoyan en atributos `data-*` que el servidor ya imprime, así que el filtrado no necesita volver a consultar la base de datos.

---

## Seguridad implementada

- **SQL**: 100 % consultas preparadas con PDO; sin concatenar datos del usuario.
- **Contraseñas**: `password_hash()` / `password_verify()` (bcrypt).
- **Sesiones**: `HttpOnly`, `SameSite=Lax`, `use_strict_mode`, `use_only_cookies` y `session_regenerate_id()` al iniciar sesión.
- **CSRF**: token por sesión en todos los formularios `POST` y en la API.
- **XSS**: toda salida pasa por `e()` (`htmlspecialchars`) o por conversión a entero.
- **Control de acceso**: `requiere_login()` y `requiere_admin()`; un cliente no entra al panel ni ve recibos ajenos (404), y el PDF solo existe para pedidos `pagado` (403 en otro estado).
- **Inventario**: transacción con `SELECT … FOR UPDATE` y `UPDATE … WHERE stock >= ?`; el stock nunca queda negativo.
- **Archivos sensibles**: `.htaccess` bloquea `config/`, `includes/`, `lib/`, `sql/` y extensiones `.sql`, `.md`, `.log`, `.bak`, `.zip`, `.ini`.
- **Cabeceras**: `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`; se oculta `X-Powered-By`.
- **Errores**: `display_errors = Off`; se registra el detalle y se responde un 500 genérico.

**Pendiente opcional:** Content-Security-Policy. No se añadió porque el SDK de PayPal y el script de `checkout.php` la bloquearían sin ajustes adicionales.

---

## Despliegue en un hosting

1. **Subir archivos** por FTP o el administrador de archivos del hosting. La carpeta `docs/` (planes de trabajo) no es necesaria en producción.
2. **Crear la base** desde cPanel y un usuario con permisos sobre ella.
3. **Importar** `sql/schema-produccion.sql` con phpMyAdmin. Ya contiene la estructura y el catálogo con cupos completos; no crea la base, así que funciona con cualquier nombre que asigne el hosting.
4. **Crear el administrador**: editar `sql/admin-inicial.sql` y cambiar `admin@tu-dominio.mx` por un correo real; importarlo. La contraseña inicial se entrega aparte y se puede regenerar con `password_hash()`.
5. **Editar** `config/db.php` con el host, nombre, usuario y contraseña del hosting.
6. **Verificar Apache**: `AllowOverride All` para que funcionen los `.htaccess` y `mod_headers` para las cabeceras.
7. **HTTPS**: activar el certificado. La cookie de sesión se marca `Secure` automáticamente cuando hay HTTPS.
8. **PayPal**: cambiar a credenciales Live y `PAYPAL_SANDBOX = false`.
9. **Apagar el modo demo**: en `config/paypal.php`, `PAYPAL_DEMO_HABILITADO = false`. En producción no debe existir el botón de pago simulado.
10. **Permisos**: archivos `644`, carpetas `755`. El recibo se genera en memoria, sin escritura en disco.

---

## Pruebas realizadas

Se ejecutó un QA de punta a punta con `curl`, sesiones separadas por rol y verificación directa en MariaDB:

- Catálogo, carrito y total correcto.
- Checkout sin sesión redirige al login.
- Compra demo completa: pedido pagado, stock descontado (40 → 38), recibo PDF válido.
- Sobreventa rechazada y bloqueo cuando el stock cambia antes de pagar.
- Admin ve el folio, el estado y descarga el PDF.
- Cliente bloqueado del panel y de recibos ajenos.
- CSRF inválido rechazado con 403.
- Configuración real de PayPal: pendiente de credenciales sandbox.

---

## Limitaciones conocidas

- Las páginas informativas (`programa.php`, `ponencias.php`, `ponencia-detalle.php`, `memoria.php`) comparten header y footer, pero su contenido no se genera desde la base de datos.
- No se aplica Content-Security-Policy: el SDK de PayPal y el script inline de `checkout.php` la bloquearían.
- La integración real con PayPal quedó probada solo en modo demostración; falta cargar credenciales sandbox/live.

---

## Solución de problemas

| Síntoma | Causa probable | Solución |
|---|---|---|
| 403 en todas las páginas | `AllowOverride` desactivado | Activar `AllowOverride All` en Apache |
| 500 al abrir cualquier página | Error de PHP | Revisar `/opt/lampp/logs/php_error_log` |
| “No fue posible conectar con la base de datos” | Credenciales incorrectas | Revisar `config/db.php` |
| No aparecen los botones de PayPal | Faltan credenciales | Usar el modo demostración o configurar `config/paypal.php` |
| El sitio muestra el HTML viejo | Caché del navegador | Recargar con `Ctrl + F5` |
| Se perdió el diseño | Falta `styles.css` o la ruta es incorrecta | Verificar que el archivo esté en la raíz |

---

## Reiniciar la base de datos

```bash
/opt/lampp/bin/mysql -u root < sql/schema.sql
```

Esto elimina pedidos, usuarios creados y restaura el stock inicial (`40/120/25/25`).
