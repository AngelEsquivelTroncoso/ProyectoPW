# Plan de ejecución por fases — Proyecto PW

**Ejecutor:** agente (yo)
**Base:** `PLAN.md` (alcance, stack, BD y requisitos)
**Objetivo:** construir la app PHP + MariaDB del congreso, con sistema cliente y administrador, pago PayPal, recibo PDF e inventario.

> Este plan no se organiza por días. Se organiza por **fases con dependencia estricta**: cada fase solo empieza cuando la anterior está verificada.
> Al cerrar cada fase: ejecuto la verificación, reporto resultado y continúo.

---

## Convenciones obligatorias (aplican a todas las fases)

- **PHP 8 nativo**, sin framework.
- **PDO con consultas preparadas** (`prepare` + `execute`) en toda la app. Cero concatenación de SQL con datos del usuario.
- **Escape de salida** con `htmlspecialchars($v, ENT_QUOTES, 'UTF-8')` en todo dato que venga de BD o del usuario.
- **Sesiones** centralizadas: arranque seguro en `includes/session.php` (cookie `HttpOnly`, `SameSite=Lax`, `session_regenerate_id` al iniciar sesión).
- **CSRF**: token por sesión en todos los formularios `POST`.
- **Contraseñas**: `password_hash()` / `password_verify()` (nunca texto plano).
- **Reutilización de diseño**: se conserva `styles.css` y la estructura visual del sitio actual.
- **Idioma de la UI**: español. **Código y comentarios**: español (coherente con el proyecto).
- **Rutas**: el proyecto vive en `htdocs/ProyectoPW`, URL base `http://localhost/ProyectoPW`.

---

## Fase 0 — Preparación y verificación del entorno

**Objetivo:** confirmar que todo lo necesario existe antes de escribir código.

**Acciones**
- Verificar LAMPP (Apache + MariaDB) encendido.
- Verificar que la BD `proyecto_pw` responde y tiene las 4 tablas (`sql/schema.sql` ya importado).
- Crear la estructura de carpetas vacía: `config/`, `includes/`, `admin/`, `api/paypal/`, `lib/fpdf/`, `recibos/`.
- Crear `.gitignore` (si aplica) y `README.md` inicial.

**Verificación**
- `mysql -u root -e "USE proyecto_pw; SHOW TABLES;"` devuelve las 4 tablas.
- `http://localhost/ProyectoPW/` responde.

**Entregable:** entorno listo y carpetas creadas.

---

## Fase 1 — Núcleo compartido

**Objetivo:** dejar la base técnica que usará todo el resto.

**Archivos a crear**
| Archivo | Contenido |
|---|---|
| `config/db.php` | Conexión PDO a `proyecto_pw` (host `localhost`, user `root`, pass vacía), modo excepción, `utf8mb4`. |
| `includes/session.php` | Arranque seguro de sesión + helpers (`mensaje_flash`, `csrf_token`, `csrf_validar`). |
| `includes/auth.php` | `usuario_actual()`, `es_admin()`, `requiere_login()`, `requiere_admin()`. |
| `includes/header.php` | Header/nav del sitio en PHP (reutiliza clases de `styles.css`). |
| `includes/footer.php` | Footer del sitio en PHP. |
| `includes/funciones.php` | Helpers: `e()` (escape), `redirigir()`, formateo de precio. |

**Verificación**
- Script temporal que hace `SELECT 1` vía PDO y muestra "conexión OK".
- `header.php` + `footer.php` renderizan sin errores en una página de prueba.

**Entregable:** conexión a BD y layout compartido funcionando.

---

## Fase 2 — Catálogo cliente (lectura de productos)

**Objetivo:** mostrar los productos reales desde la base.

**Archivos**
- `index.php` (desde `index.html`): home con productos destacados.
- `catalogo.php` (desde `pagos.html`): lista `productos WHERE activo = 1`, muestra precio y `stock` (cupos).
- Ajustar `includes/header.php` para que el menú apunte a `.php`.

**Verificación**
- `catalogo.php` lista los 4 productos semilla con precio y stock.
- Si un producto tiene `activo = 0`, no aparece.

**Entregable:** catálogo dinámico.

---

## Fase 3 — Carrito en sesión

**Objetivo:** seleccionar productos y cantidades sin base de datos todavía.

**Archivos**
- `includes/carrito.php`: `carrito_agregar()`, `carrito_quitar()`, `carrito_actualizar()`, `carrito_items()`, `carrito_total()`.
- `carrito.php`: tabla de ítems, subtotal, botones +/- y vaciar.
- Botones "Agregar al carrito" en `catalogo.php`.

**Reglas**
- `$_SESSION['carrito'] = [producto_id => cantidad]`.
- Validar que la cantidad no supere `stock`.
- El total se calcula **siempre desde la BD**, nunca desde el navegador.

**Verificación**
- Agregar/quitar/modificar ítems persiste entre recargas.
- El total coincide con la suma de precios de la BD.

**Entregable:** carrito funcional en sesión.

---

## Fase 4 — Autenticación de cliente

**Objetivo:** cuentas de cliente para asociar pedidos.

**Archivos**
- `registro.php`: alta de cliente (`nombre`, `correo`, `password`).
- `login-cliente.php`: inicio de sesión.
- `logout.php`: cierre de sesión.
- `includes/auth.php`: completar `usuario_actual()` / `requiere_login()`.

**Reglas**
- Correo único (validado con `filter_var`).
- Contraseña con `password_hash()`.
- `session_regenerate_id(true)` al iniciar sesión.

**Verificación**
- Registrar un cliente nuevo y hacer login.
- Login con credenciales inválidas muestra error.
- `logout.php` destruye la sesión.

**Entregable:** login de cliente funcionando (credencial semilla `cliente@congreso.test / cliente123` disponible).

---

## Fase 5 — Checkout y órdenes de PayPal

**Objetivo:** iniciar el pago de forma segura.

**Archivos**
- `checkout.php`: exige login, muestra resumen y botón PayPal.
- `config/paypal.php`: `client_id` / `client_secret` (sandbox), fuera de la UI.
- `api/paypal/crear-orden.php`: crea la orden con el **total real del servidor**.
- `api/paypal/capturar-orden.php`: captura y valida estado.

**Reglas**
- El monto enviado a PayPal sale de `carrito_total()` del servidor.
- `crear-orden.php` y `capturar-orden.php` responden JSON y exigen sesión.
- **Modo simulación** `?demo=1`: registra el pedido sin red, para demo/calificación.

**Verificación**
- Con sandbox: orden creada y capturada en estado `COMPLETED`.
- Con `?demo=1`: el pedido se registra sin PayPal.
- Con carrito vacío: no se puede ir a checkout.

**Entregable:** pago (real o simulado) que produce un pedido pendiente de confirmar.

---

## Fase 6 — Pedido, inventario y confirmación

**Objetivo:** cerrar la venta y descontar cupos de forma atómica.

**Archivos**
- `pago-exitoso.php`: confirma el pedido y muestra resultado.
- Lógica de cierre (en `api/paypal/capturar-orden.php` o helper en `includes/pedidos.php`).

**Reglas (transacción SQL obligatoria)**
1. `BEGIN`.
2. Insertar `pedidos` (usuario, total, estado `pagado`, ids de PayPal).
3. Insertar `detalle_pedido` por cada ítem.
4. Por cada ítem: `UPDATE productos SET stock = stock - ? WHERE id = ? AND stock >= ?`.
   - Si `rowCount() === 0` → `ROLLBACK` y error "sin cupos".
5. `COMMIT`.
6. Vaciar carrito.

**Verificación**
- Tras pagar: stock baja exactamente la cantidad comprada.
- Intentar comprar más que el stock → falla sin dejar datos a medias.
- El pedido aparece con estado `pagado`.

**Entregable:** venta cerrada con inventario consistente.

---

## Fase 7 — Recibo en PDF

**Objetivo:** generar el comprobante.

**Archivos**
- `lib/fpdf/`: librería FPDF.
- `includes/pdf.php`: `recibo_pdf($pedido_id)` genera el PDF en memoria (FPDF).
- `recibo.php`: sirve el PDF validando sesión (dueño o admin).

**Contenido del PDF**
- Encabezado del congreso, folio (`SYS-2026-000X`), fecha.
- Datos del cliente, detalle (producto, cantidad, precio), total.
- Estado del pago y leyenda de comprobante.

**Verificación**
- El PDF se genera en `recibos/pedido-{id}.pdf` y abre correctamente.
- Un cliente ajeno no puede ver el recibo de otro.
- El admin sí puede verlo.

**Entregable:** recibo PDF descargable.

---

## Fase 8 — Sistema administrador

**Objetivo:** panel completo de gestión.

**Archivos**
- `admin/login.php`: acceso con `rol = 'admin'`.
- `admin/dashboard.php`: conteos (productos, pedidos pagados, ingresos).
- `admin/productos.php`: tabla con `id, nombre, precio, stock, activo` + alta/edición/baja.
- `admin/pedidos.php`: tabla de ventas (cliente, total, estado, fecha, enlace al PDF).
- `admin/includes/` o reutilización de `includes/auth.php` con `requiere_admin()`.

**Reglas**
- Toda página de `admin/` empieza con `requiere_admin()`.
- Las acciones de escritura usan `POST` + CSRF.

**Verificación**
- Admin ve todos los productos y todas las ventas.
- Editar stock desde el panel se refleja en el catálogo.
- Un cliente no puede entrar a `admin/`.

**Entregable:** panel administrador operativo.

---

## Fase 9 — Seguridad y endurecimiento

**Objetivo:** cerrar los huecos típicos antes de publicar.

**Acciones**
- Revisar que **todo** SQL use `prepare` (buscar concatenaciones).
- Revisar escape de salida en todas las vistas.
- CSRF en todos los `POST`.
- `config/` y `recibos/` protegidos (deny directo o fuera del webroot).
- Validar tipos y rangos en servidor (`(int)`, `filter_var`, longitud).
- Manejo de errores: `display_errors` apagado en producción, `error_log` activo.

**Verificación**
- Intentar inyectar SQL en un campo → no ejecuta.
- Intentar XSS (`<script>`) en un campo → se muestra escapado.
- Enviar `POST` sin token CSRF → se rechaza.

**Entregable:** app endurecida.

---

## Fase 10 — Pruebas de flujo completo (QA)

**Objetivo:** validar el recorrido de punta a punta.

**Casos**
1. Ver catálogo → agregar al carrito → total correcto.
2. Checkout sin login → redirige a login.
3. Login → checkout → pago (sandbox y `?demo=1`) → recibo PDF.
4. Stock baja tras la compra.
5. Intento de sobreventa → se bloquea.
6. Admin ve la venta y el PDF.
7. Cliente no accede a `admin/`.
8. Recibo de otro cliente → prohibido.

**Entregable:** tabla de resultados con todos los casos en verde.

---

## Fase 11 — Despliegue y documentación

**Objetivo:** publicar y dejar instrucciones.

**Acciones**
- Confirmar funcionamiento en LAMPP (`http://localhost/ProyectoPW`).
- Si el curso exige hosting: exportar BD desde phpMyAdmin, importar `schema.sql` en el hosting, subir archivos, ajustar `config/db.php`.
- `README.md`: instalación, credenciales de prueba, flujo, modo simulación.

**Verificación**
- URL pública (o local) responde y permite comprar.

**Entregable:** app publicada + README.

---

## Fase 12 — Cierre

**Objetivo:** entregar con checklist completo.

- [ ] BD reproducible desde `sql/schema.sql`.
- [ ] Catálogo, carrito, checkout, pago y PDF funcionando.
- [ ] Inventario descuenta sin negativos.
- [ ] Admin: login, productos, pedidos.
- [ ] Sesiones en cliente y admin.
- [ ] PayPal operativo o modo simulación documentado.
- [ ] Seguridad revisada (SQL, XSS, CSRF).
- [ ] Publicado y accesible.
- [ ] README actualizado.

---

## Orden de dependencias (resumen)

```
F0 entorno
 └─ F1 núcleo compartido
     ├─ F2 catálogo
     │   └─ F3 carrito
     │       └─ F4 auth cliente
     │           └─ F5 checkout + PayPal
     │               └─ F6 pedido + inventario
     │                   └─ F7 PDF
     └─ F8 admin (depende de F6 para ver pedidos)
         └─ F9 seguridad
             └─ F10 QA
                 └─ F11 despliegue
                     └─ F12 cierre
```

---

## Política de avance

- No paso a la siguiente fase sin que la verificación de la actual pase.
- Si una fase requiere una decisión (p. ej. hosting), pregunto antes de asumir.
- Reporto al cerrar cada fase: qué se creó, qué se verificó y qué sigue.

---

## Correcciones posteriores (fases A a D)

**Fase A — inconsistencias críticas**
- [x] El aviso de stock agotado se muestra con `flash('info', …)` y `pago-exitoso.php` titula "Pago recibido, pedido en revisión".
- [x] `confirmar_pedido()` y `cancelar_pedido()` en `includes/pedidos.php`.
- [x] `admin/pedidos.php` permite confirmar y cancelar (con devolución de cupos) desde la tabla.

**Fase B — contenido interactivo**
- [x] `js/programa.js` filtra por día, tipo y sala, con conteo, mensaje de vacío y estado en la URL.
- [x] `js/ponencia-detalle.js` resuelve las 6 ponencias con `?id=` y estado "no encontrada".
- [x] Seis PDFs reales en `memoria/`.
- [x] `PAYPAL_DEMO_HABILITADO` / `PAYPAL_DEMO` permiten apagar el modo demostración.

**Fase C — consistencia**
- [x] `.badge-ponencia` definido en `styles.css`.
- [x] `recibo.php` solo entrega PDF de pedidos `pagado` (403 en otro estado).
- [x] `recibos/` eliminado (el PDF se genera en memoria).
- [x] `programa`, `ponencias`, `ponencia-detalle` y `memoria` pasan a `.php` y comparten header/footer.
- [x] `PLAN.md` archivado en `docs/`.

**Fase D — QA ampliado**
- [x] Script `/tmp/opencode/qa-faseD.sh`: 18 PASS, 0 FAIL.
- [x] Verificados aviso de revisión, confirmar, cancelar con devolución, recibo bloqueado y PDF.
- [x] Filtros (4 de 12 con `dia-2`) y detalle (`p-005`) con Chrome headless.
- [x] Datos reales preservados (2 pedidos, stock 39/120/25/24).
