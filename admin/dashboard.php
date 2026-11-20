<?php
declare(strict_types=1);

/**
 * Resumen general del panel de administración.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/pedidos.php';
require_once __DIR__ . '/../includes/productos.php';

requiere_admin();

$pdo = db();

$resumen = [
    'productos'  => (int) $pdo->query('SELECT COUNT(*) FROM productos')->fetchColumn(),
    'activos'    => (int) $pdo->query('SELECT COUNT(*) FROM productos WHERE activo = 1')->fetchColumn(),
    'pedidos'    => (int) $pdo->query('SELECT COUNT(*) FROM pedidos')->fetchColumn(),
    'pagados'    => (int) $pdo->query("SELECT COUNT(*) FROM pedidos WHERE estado = 'pagado'")->fetchColumn(),
    'pendientes' => (int) $pdo->query("SELECT COUNT(*) FROM pedidos WHERE estado = 'pendiente'")->fetchColumn(),
    'ingresos'   => (float) $pdo->query(
        "SELECT COALESCE(SUM(total), 0) FROM pedidos WHERE estado = 'pagado'"
    )->fetchColumn(),
];

$ultimos = $pdo->query(
    'SELECT p.id, p.total, p.estado, p.creado_en, u.nombre AS cliente
     FROM pedidos p
     INNER JOIN usuarios u ON u.id = p.usuario_id
     ORDER BY p.id DESC
     LIMIT 6'
)->fetchAll();

$bajo_stock = $pdo->query(
    'SELECT id, nombre, stock
     FROM productos
     WHERE activo = 1 AND stock <= 5
     ORDER BY stock ASC, nombre ASC
     LIMIT 6'
)->fetchAll();

/*
 * Datos del gráfico de cupos.
 *
 * Se envían como JSON en un <script type="application/json"> para que el JS
 * los lea sin mezclar PHP dentro de JavaScript.
 */
$cupos = $pdo->query(
    'SELECT nombre, stock
     FROM productos
     WHERE activo = 1
     ORDER BY stock ASC, nombre ASC'
)->fetchAll();

$datos_grafico = [
    'etiquetas' => array_map(static fn (array $fila): string => (string) $fila['nombre'], $cupos),
    'valores'   => array_map(static fn (array $fila): int => (int) $fila['stock'], $cupos),
];

$raiz          = '../';
$titulo        = 'Resumen | Administración SysIoT 2026';
$descripcion   = 'Resumen de ventas, pedidos e inventario del congreso.';
$pagina_activa = 'admin';
$pagina_id     = 'pagina-admin-dashboard';

$scripts_pie = '<script src="../assets/vendor/chart.umd.js"></script>'
             . '<script src="../js/dashboard.js"></script>';

include __DIR__ . '/../includes/header.php';

$admin_activo = 'dashboard';
include __DIR__ . '/_navegacion.php';
?>
<section class="contenedor">
  <h1>Resumen del congreso</h1>
  <p class="ayuda">Vista general de productos, pedidos e ingresos.</p>

  <div class="admin-tarjetas">
    <article class="admin-tarjeta">
      <p class="admin-tarjeta-numero"><?= (int) $resumen['productos'] ?></p>
      <p class="admin-tarjeta-etiqueta">Productos (<?= (int) $resumen['activos'] ?> activos)</p>
    </article>
    <article class="admin-tarjeta">
      <p class="admin-tarjeta-numero"><?= (int) $resumen['pedidos'] ?></p>
      <p class="admin-tarjeta-etiqueta">Pedidos (<?= (int) $resumen['pagados'] ?> pagados)</p>
    </article>
    <article class="admin-tarjeta">
      <p class="admin-tarjeta-numero"><?= e(precio($resumen['ingresos'])) ?></p>
      <p class="admin-tarjeta-etiqueta">Ingresos cobrados</p>
    </article>
    <article class="admin-tarjeta">
      <p class="admin-tarjeta-numero"><?= (int) $resumen['pendientes'] ?></p>
      <p class="admin-tarjeta-etiqueta">Pedidos por revisar</p>
    </article>
  </div>

  <h2>Cupos disponibles por producto</h2>
  <?php if ($datos_grafico['valores'] === []): ?>
    <p class="admin-vacio">No hay productos activos que graficar.</p>
  <?php else: ?>
    <div class="admin-grafico">
      <canvas
        id="grafico-cupos"
        role="img"
        aria-label="Cupos disponibles por producto. Las barras rojas indican cinco cupos o menos."
      ></canvas>
    </div>
    <p class="ayuda">Las barras en rojo indican cinco cupos o menos.</p>
    <script type="application/json" id="datos-cupos"><?= json_encode($datos_grafico, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
  <?php endif; ?>

  <h2>Últimos pedidos</h2>
  <?php if ($ultimos === []): ?>
    <p class="admin-vacio">Todavía no hay pedidos registrados.</p>
  <?php else: ?>
    <div class="admin-tabla-contenedor">
      <table class="admin-tabla">
        <thead>
          <tr>
            <th scope="col">Folio</th>
            <th scope="col">Cliente</th>
            <th scope="col">Total</th>
            <th scope="col">Estado</th>
            <th scope="col">Fecha</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($ultimos as $pedido): ?>
            <tr>
              <td><a href="pedidos.php"><?= e(folio_pedido((int) $pedido['id'])) ?></a></td>
              <td><?= e($pedido['cliente']) ?></td>
              <td><?= e(precio($pedido['total'])) ?></td>
              <td>
                <span class="badge-estado" data-estado="<?= e($pedido['estado']) ?>">
                  <?= e(estado_pedido($pedido['estado'])) ?>
                </span>
              </td>
              <td><?= e(date('d/m/Y H:i', strtotime($pedido['creado_en']))) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <h2>Productos con pocos cupos</h2>
  <?php if ($bajo_stock === []): ?>
    <p class="admin-vacio">Todos los productos tienen más de 5 cupos.</p>
  <?php else: ?>
    <div class="admin-tabla-contenedor">
      <table class="admin-tabla">
        <thead>
          <tr>
            <th scope="col">Producto</th>
            <th scope="col">Cupos restantes</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($bajo_stock as $producto): ?>
            <tr>
              <td><?= e($producto['nombre']) ?></td>
              <td><?= (int) $producto['stock'] ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
