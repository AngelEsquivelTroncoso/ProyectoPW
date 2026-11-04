<?php
declare(strict_types=1);

/**
 * Listado de pedidos para el administrador.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/pedidos.php';

requiere_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validar($_POST['csrf'] ?? null)) {
        flash('error', 'La sesión expiró. Vuelve a intentarlo.');
        redirigir('pedidos.php');
    }

    $accion    = (string) ($_POST['accion'] ?? '');
    $pedido_id = (int) ($_POST['pedido_id'] ?? 0);
    $volver    = (string) ($_POST['filtro'] ?? '');

    $destino = 'pedidos.php';

    if (in_array($volver, ['pagado', 'pendiente', 'cancelado'], true)) {
        $destino .= '?estado=' . $volver;
    }

    if ($pedido_id <= 0) {
        flash('error', 'Pedido inválido.');
        redirigir($destino);
    }

    $resultado = match ($accion) {
        'confirmar' => confirmar_pedido($pedido_id),
        'cancelar'  => cancelar_pedido($pedido_id),
        default     => ['ok' => false, 'mensaje' => 'Acción no reconocida.'],
    };

    flash($resultado['ok'] ? 'exito' : 'error', $resultado['mensaje']);
    redirigir($destino);
}

$estados_validos = ['', 'pagado', 'pendiente', 'cancelado'];
$filtro          = (string) ($_GET['estado'] ?? '');

if (!in_array($filtro, $estados_validos, true)) {
    $filtro = '';
}

$sql    = 'SELECT p.id, p.total, p.estado, p.creado_en, u.nombre AS cliente, u.correo
           FROM pedidos p
           INNER JOIN usuarios u ON u.id = p.usuario_id';
$params = [];

if ($filtro !== '') {
    $sql     .= ' WHERE p.estado = ?';
    $params[] = $filtro;
}

$sql .= ' ORDER BY p.id DESC';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$pedidos = $stmt->fetchAll();

$conteos = [
    'todos'     => (int) db()->query('SELECT COUNT(*) FROM pedidos')->fetchColumn(),
    'pagado'    => (int) db()->query("SELECT COUNT(*) FROM pedidos WHERE estado = 'pagado'")->fetchColumn(),
    'pendiente' => (int) db()->query("SELECT COUNT(*) FROM pedidos WHERE estado = 'pendiente'")->fetchColumn(),
    'cancelado' => (int) db()->query("SELECT COUNT(*) FROM pedidos WHERE estado = 'cancelado'")->fetchColumn(),
];

$raiz          = '../';
$titulo        = 'Pedidos | Administración SysIoT 2026';
$descripcion   = 'Pedidos registrados en el congreso.';
$pagina_activa = 'admin';
$pagina_id     = 'pagina-admin-pedidos';

include __DIR__ . '/../includes/header.php';

$admin_activo = 'pedidos';
include __DIR__ . '/_navegacion.php';
?>
<section class="contenedor">
  <h1>Pedidos</h1>
  <p class="ayuda">Todas las ventas registradas, con acceso al recibo en PDF.</p>

  <nav class="admin-filtros" aria-label="Filtrar pedidos por estado">
    <a class="admin-filtro" href="pedidos.php"<?= $filtro === '' ? ' aria-current="page"' : '' ?>>
      Todos (<?= (int) $conteos['todos'] ?>)
    </a>
    <a class="admin-filtro" href="pedidos.php?estado=pagado"<?= $filtro === 'pagado' ? ' aria-current="page"' : '' ?>>
      Pagados (<?= (int) $conteos['pagado'] ?>)
    </a>
    <a class="admin-filtro" href="pedidos.php?estado=pendiente"<?= $filtro === 'pendiente' ? ' aria-current="page"' : '' ?>>
      Pendientes (<?= (int) $conteos['pendiente'] ?>)
    </a>
    <a class="admin-filtro" href="pedidos.php?estado=cancelado"<?= $filtro === 'cancelado' ? ' aria-current="page"' : '' ?>>
      Cancelados (<?= (int) $conteos['cancelado'] ?>)
    </a>
  </nav>

  <?php if ($pedidos === []): ?>
    <p class="admin-vacio">No hay pedidos para este filtro.</p>
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
            <th scope="col">Recibo</th>
            <th scope="col">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pedidos as $pedido): ?>
            <tr>
              <td><?= e(folio_pedido((int) $pedido['id'])) ?></td>
              <td>
                <?= e($pedido['cliente']) ?><br />
                <span class="admin-nota"><?= e($pedido['correo']) ?></span>
              </td>
              <td><?= e(precio($pedido['total'])) ?></td>
              <td>
                <span class="badge-estado" data-estado="<?= e($pedido['estado']) ?>">
                  <?= e(estado_pedido($pedido['estado'])) ?>
                </span>
              </td>
              <td><?= e(date('d/m/Y H:i', strtotime($pedido['creado_en']))) ?></td>
              <td>
                <?php if ($pedido['estado'] === 'pagado'): ?>
                  <a class="btn btn-contorno btn-pequeno" href="../recibo.php?pedido=<?= (int) $pedido['id'] ?>">
                    Ver PDF
                  </a>
                <?php else: ?>
                  <span class="admin-nota">—</span>
                <?php endif; ?>
              </td>
              <td>
                <div class="admin-acciones">
                  <?php if ($pedido['estado'] === 'pendiente'): ?>
                    <form method="post" action="pedidos.php">
                      <?= csrf_campo() ?>
                      <input type="hidden" name="accion" value="confirmar" />
                      <input type="hidden" name="pedido_id" value="<?= (int) $pedido['id'] ?>" />
                      <input type="hidden" name="filtro" value="<?= e($filtro) ?>" />
                      <button class="btn btn-principal btn-pequeno" type="submit">Confirmar</button>
                    </form>
                    <form method="post" action="pedidos.php"
                          onsubmit="return confirm('¿Cancelar este pedido pendiente?');">
                      <?= csrf_campo() ?>
                      <input type="hidden" name="accion" value="cancelar" />
                      <input type="hidden" name="pedido_id" value="<?= (int) $pedido['id'] ?>" />
                      <input type="hidden" name="filtro" value="<?= e($filtro) ?>" />
                      <button class="btn btn-contorno btn-pequeno" type="submit">Cancelar</button>
                    </form>
                  <?php elseif ($pedido['estado'] === 'pagado'): ?>
                    <form method="post" action="pedidos.php"
                          onsubmit="return confirm('¿Cancelar el pedido y devolver los cupos?');">
                      <?= csrf_campo() ?>
                      <input type="hidden" name="accion" value="cancelar" />
                      <input type="hidden" name="pedido_id" value="<?= (int) $pedido['id'] ?>" />
                      <input type="hidden" name="filtro" value="<?= e($filtro) ?>" />
                      <button class="btn btn-contorno btn-pequeno" type="submit">Cancelar y devolver cupos</button>
                    </form>
                  <?php else: ?>
                    <span class="admin-nota">—</span>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
