<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/pedidos.php';

if (usuario_actual() === null) {
    flash('error', 'Inicia sesión para ver tu pedido.');
    redirigir('login-cliente.php?volver=' . urlencode('pago-exitoso.php'));
}

$pedido_id = (int) ($_GET['pedido'] ?? 0);
$pedido    = $pedido_id > 0 ? pedido_por_id($pedido_id) : null;
$usuario   = usuario_actual();

$es_propietario = $pedido !== null && (int) $pedido['usuario_id'] === (int) $usuario['id'];

if ($pedido === null || (!$es_propietario && !es_admin())) {
    http_response_code(404);
    $titulo = 'Pedido no encontrado';
    include __DIR__ . '/includes/header.php';
    ?>
      <section class="seccion">
        <div class="contenedor">
          <div class="tarjeta carrito-vacio">
            <h1>Pedido no encontrado</h1>
            <p>No existe un pedido con ese número o no tienes acceso a él.</p>
            <a class="btn btn-principal" href="catalogo.php">Volver al catálogo</a>
          </div>
        </div>
      </section>
    <?php
    include __DIR__ . '/includes/footer.php';
    exit;
}

$detalle = detalle_de_pedido($pedido_id);

$etiquetas = [
    'pendiente' => 'Pendiente de confirmación',
    'pagado'    => 'Pagado',
    'cancelado' => 'Cancelado',
];
$estado = $etiquetas[$pedido['estado']] ?? $pedido['estado'];
$folio  = folio_pedido($pedido_id);

$en_revision = $pedido['estado'] === 'pendiente';

$titulo = ($en_revision ? 'Pago recibido, pedido en revisión' : 'Pago recibido')
    . ' | Congreso Internacional de Ingeniería en Sistemas e IoT';
$descripcion   = 'Confirmación del pedido del congreso.';
$pagina_activa = 'carrito';
$pagina_id     = 'pagina-pago-exitoso';

include __DIR__ . '/includes/header.php';
?>

      <section class="cabecera-pagina">
        <div class="contenedor">
          <h1><?= $en_revision ? 'Pago recibido, pedido en revisión' : 'Pago recibido' ?></h1>
          <?php if ($en_revision): ?>
            <p>Gracias, <?= e($usuario['nombre']) ?>. Recibimos tu pago, pero el pedido necesita revisión.</p>
          <?php else: ?>
            <p>Gracias, <?= e($usuario['nombre']) ?>. Tu pedido quedó registrado.</p>
          <?php endif; ?>
        </div>
      </section>

      <section class="seccion">
        <div class="contenedor">
          <div class="tarjeta pedido-confirmado">
            <dl class="datos-pedido">
              <div>
                <dt>Folio</dt>
                <dd><?= e($folio) ?></dd>
              </div>
              <div>
                <dt>Estado</dt>
                <dd><?= e($estado) ?></dd>
              </div>
              <div>
                <dt>Fecha</dt>
                <dd><?= e(date('d/m/Y H:i', strtotime((string) $pedido['creado_en']))) ?></dd>
              </div>
              <div>
                <dt>Total</dt>
                <dd><strong><?= e(precio($pedido['total'])) ?></strong></dd>
              </div>
            </dl>

            <table class="tabla-carrito">
              <thead>
                <tr>
                  <th scope="col">Producto</th>
                  <th scope="col">Cantidad</th>
                  <th scope="col">Precio unitario</th>
                  <th scope="col">Subtotal</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($detalle as $linea): ?>
                  <tr>
                    <td data-etiqueta="Producto"><?= e($linea['nombre']) ?></td>
                    <td data-etiqueta="Cantidad"><?= (int) $linea['cantidad'] ?></td>
                    <td data-etiqueta="Precio unitario"><?= e(precio($linea['precio_unitario'])) ?></td>
                    <td data-etiqueta="Subtotal"><?= e(precio((float) $linea['precio_unitario'] * (int) $linea['cantidad'])) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>

            <div class="acciones-formulario">
              <?php if ($pedido['estado'] === 'pagado'): ?>
                <a class="btn btn-principal" href="recibo.php?pedido=<?= (int) $pedido_id ?>">
                  Descargar recibo (PDF)
                </a>
              <?php elseif ($en_revision): ?>
                <p class="ayuda">
                  El recibo estará disponible cuando el pedido quede confirmado.
                </p>
              <?php endif; ?>
              <a class="btn btn-secundario" href="catalogo.php">Volver al catálogo</a>
            </div>
          </div>
        </div>
      </section>

<?php include __DIR__ . '/includes/footer.php'; ?>
