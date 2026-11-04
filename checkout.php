<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/carrito.php';
require_once __DIR__ . '/config/paypal.php';

if (usuario_actual() === null) {
    flash('error', 'Inicia sesión para continuar con el pago.');
    redirigir('login-cliente.php?volver=' . urlencode('checkout.php'));
}

$items = carrito_items();

if ($items === []) {
    flash('info', 'Tu carrito está vacío.');
    redirigir('catalogo.php');
}

$total        = carrito_total();
$paypal_listo = paypal_configurado();

$sin_cupo = [];

foreach ($items as $item) {
    if ((int) $item['cantidad'] > (int) $item['producto']['stock']) {
        $sin_cupo[] = $item['producto']['nombre'];
    }
}

$titulo        = 'Pago | Congreso Internacional de Ingeniería en Sistemas e IoT';
$descripcion   = 'Confirma tu inscripción y realiza el pago seguro.';
$pagina_activa = 'carrito';
$pagina_id     = 'pagina-checkout';

include __DIR__ . '/includes/header.php';
?>

      <section class="cabecera-pagina">
        <div class="contenedor">
          <h1>Confirmar pago</h1>
          <p>Revisa tu pedido y elige cómo pagar.</p>
        </div>
      </section>

      <section class="seccion">
        <div class="contenedor">
          <div class="checkout-grid">
            <div class="tarjeta">
              <h2>Tu pedido</h2>
              <table class="tabla-carrito">
                <thead>
                  <tr>
                    <th scope="col">Producto</th>
                    <th scope="col">Cantidad</th>
                    <th scope="col">Subtotal</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($items as $item): ?>
                    <tr>
                      <td data-etiqueta="Producto"><?= e($item['producto']['nombre']) ?></td>
                      <td data-etiqueta="Cantidad"><?= (int) $item['cantidad'] ?></td>
                      <td data-etiqueta="Subtotal"><?= e(precio($item['subtotal'])) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
              <p class="total-carrito">
                Total a pagar: <strong><?= e(precio($total)) ?></strong>
              </p>
              <a class="btn btn-contorno btn-pequeno" href="carrito.php">Volver al carrito</a>
            </div>

            <div class="tarjeta checkout-pago">
              <h2>Método de pago</h2>

              <?php if ($sin_cupo !== []): ?>
                <p class="aviso aviso-error" role="alert">
                  Ya no hay cupos suficientes de: <?= e(implode(', ', $sin_cupo)) ?>.
                  Ajusta tu carrito antes de pagar.
                </p>
              <?php else: ?>
                <?php if ($paypal_listo): ?>
                  <div id="paypal-botones" class="paypal-botones"></div>
                <?php else: ?>
                  <p class="aviso aviso-info">
                    El pago con PayPal no está configurado en este servidor.<?php if (paypal_demo_habilitado()): ?>
                    Puedes recorrer el flujo completo con el modo de demostración.<?php endif; ?>
                  </p>
                <?php endif; ?>

                <?php if (paypal_demo_habilitado()): ?>
                  <div class="modo-demo">
                    <p class="ayuda">
                      Demostración para clase: registra el pedido sin cobrar.
                    </p>
                    <button id="boton-demo" class="btn btn-contorno btn-bloque" type="button">
                      Simular pago aprobado
                    </button>
                  </div>
                <?php endif; ?>
              <?php endif; ?>

              <p id="estado-pago" class="estado-pago" role="status" aria-live="polite"></p>
            </div>
          </div>
        </div>
      </section>

<?php if ($paypal_listo && $sin_cupo === []): ?>
  <script src="https://www.paypal.com/sdk/js?client-id=<?= rawurlencode(paypal_client_id()) ?>&currency=<?= rawurlencode(PAYPAL_MONEDA) ?>&intent=capture"></script>
<?php endif; ?>

<?php if ($sin_cupo === []): ?>
  <script>
    (() => {
      const csrf = <?= json_encode(csrf_token()) ?>;
      const estado = document.getElementById('estado-pago');
      const botonDemo = document.getElementById('boton-demo');

      const mostrar = (texto, tipo) => {
        estado.textContent = texto;
        estado.dataset.tipo = tipo || '';
      };

      async function crearOrden(demo) {
        const datos = new FormData();
        datos.append('csrf', csrf);
        if (demo) datos.append('demo', '1');

        const respuesta = await fetch('api/paypal/crear-orden.php', {
          method: 'POST',
          body: datos,
          credentials: 'same-origin',
        });
        const json = await respuesta.json();

        if (!json.ok) throw new Error(json.error || 'No se pudo crear la orden.');
        return json.id;
      }

      async function capturar(ordenId) {
        const datos = new FormData();
        datos.append('csrf', csrf);
        datos.append('order_id', ordenId);

        const respuesta = await fetch('api/paypal/capturar-orden.php', {
          method: 'POST',
          body: datos,
          credentials: 'same-origin',
        });
        const json = await respuesta.json();

        if (!json.ok) throw new Error(json.error || 'No se pudo confirmar el pago.');
        window.location.href = json.redirigir;
      }

      if (botonDemo) {
        botonDemo.addEventListener('click', async () => {
          botonDemo.disabled = true;
          mostrar('Procesando pago de demostración…', 'info');
          try {
            const orden = await crearOrden(true);
            await capturar(orden);
          } catch (error) {
            botonDemo.disabled = false;
            mostrar(error.message, 'error');
          }
        });
      }

      if (window.paypal && document.getElementById('paypal-botones')) {
        window.paypal.Buttons({
          createOrder: () => crearOrden(false),
          onApprove: async (datos) => {
            try {
              await capturar(datos.orderID);
            } catch (error) {
              mostrar(error.message, 'error');
            }
          },
          onError: () => mostrar('PayPal rechazó la operación. Intenta de nuevo.', 'error'),
        }).render('#paypal-botones');
      }
    })();
  </script>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
