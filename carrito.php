<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/carrito.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validar($_POST['csrf'] ?? null)) {
        flash('error', 'La sesión expiró. Vuelve a intentarlo.');
        redirigir('carrito.php');
    }

    $accion   = (string) ($_POST['accion'] ?? '');
    $producto = (int) ($_POST['producto_id'] ?? 0);
    $cantidad = (int) ($_POST['cantidad'] ?? 1);

    $resultado = ['ok' => false, 'mensaje' => 'Acción no reconocida.'];

    switch ($accion) {
        case 'agregar':
            $resultado = carrito_agregar($producto, $cantidad);
            break;
        case 'actualizar':
            $resultado = carrito_actualizar($producto, $cantidad);
            break;
        case 'quitar':
            $resultado = carrito_quitar($producto);
            break;
        case 'vaciar':
            $resultado = carrito_vaciar();
            break;
    }

    flash($resultado['ok'] ? 'exito' : 'error', $resultado['mensaje']);
    redirigir('carrito.php');
}

$items         = carrito_items();
$total         = carrito_total();
$titulo        = 'Carrito | Congreso Internacional de Ingeniería en Sistemas e IoT';
$descripcion   = 'Revisa las inscripciones y talleres que has agregado al carrito.';
$pagina_activa = 'carrito';
$pagina_id     = 'pagina-carrito';

include __DIR__ . '/includes/header.php';
?>

      <section class="cabecera-pagina">
        <div class="contenedor">
          <h1>Tu carrito</h1>
          <p>Revisa las cantidades antes de continuar con el pago.</p>
        </div>
      </section>

      <section class="seccion">
        <div class="contenedor">
          <?php if ($items === []): ?>
            <div class="tarjeta carrito-vacio">
              <h2>Tu carrito está vacío</h2>
              <p>Explora el catálogo y agrega tu inscripción o taller.</p>
              <a class="btn btn-principal" href="catalogo.php">Ver catálogo</a>
            </div>
          <?php else: ?>
            <div class="tarjeta tabla-carrito-contenedor">
              <table class="tabla-carrito">
                <thead>
                  <tr>
                    <th scope="col">Producto</th>
                    <th scope="col">Precio</th>
                    <th scope="col">Cantidad</th>
                    <th scope="col">Subtotal</th>
                    <th scope="col"><span class="solo-lectores">Acciones</span></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($items as $item): ?>
                    <?php $producto = $item['producto']; ?>
                    <tr>
                      <td data-etiqueta="Producto">
                        <strong><?= e($producto['nombre']) ?></strong>
                      </td>
                      <td data-etiqueta="Precio"><?= e(precio($producto['precio'])) ?></td>
                      <td data-etiqueta="Cantidad">
                        <form method="post" action="carrito.php" class="form-cantidad">
                          <?= csrf_campo() ?>
                          <input type="hidden" name="accion" value="actualizar">
                          <input type="hidden" name="producto_id" value="<?= (int) $producto['id'] ?>">
                          <label class="solo-lectores" for="cantidad-<?= (int) $producto['id'] ?>">
                            Cantidad de <?= e($producto['nombre']) ?>
                          </label>
                          <input
                            id="cantidad-<?= (int) $producto['id'] ?>"
                            class="campo-cantidad"
                            type="number"
                            name="cantidad"
                            value="<?= (int) $item['cantidad'] ?>"
                            min="0"
                            max="<?= (int) $producto['stock'] ?>"
                            inputmode="numeric"
                          >
                          <button class="btn btn-contorno btn-pequeno" type="submit">Actualizar</button>
                        </form>
                      </td>
                      <td data-etiqueta="Subtotal"><?= e(precio($item['subtotal'])) ?></td>
                      <td data-etiqueta="Acciones">
                        <form method="post" action="carrito.php">
                          <?= csrf_campo() ?>
                          <input type="hidden" name="accion" value="quitar">
                          <input type="hidden" name="producto_id" value="<?= (int) $producto['id'] ?>">
                          <button class="btn btn-contorno btn-pequeno" type="submit">
                            Quitar<span class="solo-lectores"> <?= e($producto['nombre']) ?></span>
                          </button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>

            <div class="tarjeta resumen-carrito">
              <p class="total-carrito">
                Total: <strong><?= e(precio($total)) ?></strong>
              </p>
              <div class="acciones-formulario">
                <a class="btn btn-principal" href="checkout.php">Continuar al pago</a>
                <a class="btn btn-secundario" href="catalogo.php">Seguir comprando</a>
                <form method="post" action="carrito.php">
                  <?= csrf_campo() ?>
                  <input type="hidden" name="accion" value="vaciar">
                  <button class="btn btn-contorno" type="submit">Vaciar carrito</button>
                </form>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </section>

<?php include __DIR__ . '/includes/footer.php'; ?>
