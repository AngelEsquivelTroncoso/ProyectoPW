<?php
declare(strict_types=1);

/**
 * Crea una orden de PayPal (o una orden simulada en modo demo).
 *
 * El monto se calcula en el servidor a partir del carrito; el navegador
 * nunca envía precios.
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/carrito.php';
require_once __DIR__ . '/../../includes/paypal.php';
require_once __DIR__ . '/../../includes/funciones.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_salida(['ok' => false, 'error' => 'Método no permitido.'], 405);
}

if (usuario_actual() === null) {
    json_salida(['ok' => false, 'error' => 'Inicia sesión para continuar.'], 401);
}

if (!csrf_validar($_POST['csrf'] ?? null)) {
    json_salida(['ok' => false, 'error' => 'La sesión expiró. Recarga la página.'], 403);
}

$items = carrito_items();

if ($items === []) {
    json_salida(['ok' => false, 'error' => 'Tu carrito está vacío.'], 400);
}

foreach ($items as $item) {
    if ((int) $item['cantidad'] > (int) $item['producto']['stock']) {
        json_salida([
            'ok'    => false,
            'error' => 'No hay cupos suficientes de ' . $item['producto']['nombre'] . '.',
        ], 409);
    }
}

$total = carrito_total();
$demo  = ($_POST['demo'] ?? '') === '1';

if ($demo && !paypal_demo_habilitado()) {
    json_salida(['ok' => false, 'error' => 'El modo de demostración está deshabilitado.'], 403);
}

if (!$demo && !paypal_configurado()) {
    json_salida([
        'ok'    => false,
        'error' => 'PayPal no está configurado en este servidor.',
    ], 503);
}

if ($demo) {
    $orden_id = 'DEMO-' . strtoupper(bin2hex(random_bytes(6)));
} else {
    $referencia = 'SYS-' . date('YmdHis') . '-' . bin2hex(random_bytes(4));
    $creacion   = paypal_crear_orden($total, $referencia);

    if (!$creacion['ok']) {
        json_salida(['ok' => false, 'error' => $creacion['error']], 502);
    }

    $orden_id = (string) $creacion['id'];
}

// Instantánea del pedido en el servidor: la captura no confía en el cliente.
$_SESSION['orden_pendiente'] = [
    'order_id' => $orden_id,
    'total'    => $total,
    'items'    => $items,
    'demo'     => $demo,
    'creado'   => time(),
];

json_salida([
    'ok'   => true,
    'id'   => $orden_id,
    'demo' => $demo,
]);
