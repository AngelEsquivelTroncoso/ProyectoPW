<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/productos.php';

$titulo        = 'Catálogo | Congreso Internacional de Ingeniería en Sistemas e IoT';
$descripcion   = 'Inscripciones y talleres disponibles del Congreso Internacional de Ingeniería en Sistemas e IoT.';
$pagina_activa = 'catalogo';
$pagina_id     = 'pagina-catalogo';

$productos = productos_activos();

include __DIR__ . '/includes/header.php';
?>

      <section class="cabecera-pagina">
        <div class="contenedor">
          <h1>Catálogo</h1>
          <p>Elige tu tipo de inscripción o taller y continúa con el pago.</p>
        </div>
      </section>

      <section class="seccion">
        <div class="contenedor">
          <?php if ($productos === []): ?>
            <p class="nota-pago">Por el momento no hay productos disponibles.</p>
          <?php else: ?>
            <div class="contenedor-precios" id="contenedor-precios">
              <?php foreach ($productos as $producto): ?>
                <article class="tarjeta pricing-card" id="producto-<?= (int) $producto['id'] ?>">
                  <h2><?= e($producto['nombre']) ?></h2>
                  <p class="precio"><?= e(precio($producto['precio'])) ?></p>
                  <p><?= e($producto['descripcion']) ?></p>

                  <?php if ((int) $producto['stock'] > 0): ?>
                    <p class="nota-pago">
                      <span class="badge"><?= (int) $producto['stock'] ?> cupos disponibles</span>
                    </p>

                    <form method="post" action="carrito.php" class="form-agregar">
                      <?= csrf_campo() ?>
                      <input type="hidden" name="accion" value="agregar">
                      <input type="hidden" name="producto_id" value="<?= (int) $producto['id'] ?>">
                      <label class="solo-lectores" for="cantidad-<?= (int) $producto['id'] ?>">
                        Cantidad de <?= e($producto['nombre']) ?>
                      </label>
                      <input
                        id="cantidad-<?= (int) $producto['id'] ?>"
                        class="campo-cantidad"
                        type="number"
                        name="cantidad"
                        value="1"
                        min="1"
                        max="<?= (int) $producto['stock'] ?>"
                        inputmode="numeric"
                      >
                      <button class="btn btn-principal btn-bloque" type="submit">
                        Agregar al carrito
                      </button>
                    </form>
                  <?php else: ?>
                    <p class="nota-pago">
                      <span class="badge badge-receso">Agotado</span>
                    </p>
                  <?php endif; ?>
                </article>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <p class="nota-pago">
            ¿Aún no tienes cuenta?
            <a href="registro.php">Crea tu cuenta para inscribirte</a>.
          </p>
        </div>
      </section>

<?php include __DIR__ . '/includes/footer.php'; ?>
