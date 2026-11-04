<?php
declare(strict_types=1);

/**
 * Captura una orden aprobada y registra el pedido.
 *
 * Solo acepta la orden que el propio servidor guardó en la sesión al
 * crearla, y verifica monto y moneda contra esa instantánea.
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/carrito.php';
require_once __DIR__ . '/../../includes/paypal.php';
require_once __DIR__ . '/../../includes/pedidos.php';
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

$pendiente = $_SESSION['orden_pendiente'] ?? null;

if (!is_array($pendiente) || empty($pendiente['order_id'])) {
    json_salida(['ok' => false, 'error' => 'No hay una orden pendiente de pago.'], 409);
}

if (time() - (int) ($pendiente['creado'] ?? 0) > 7200) {
    unset($_SESSION['orden_pendiente']);
    json_salida(['ok' => false, 'error' => 'La orden expiró. Vuelve a intentarlo.'], 409);
}

$orden_id    = (string) ($_POST['order_id'] ?? '');
$orden_sesion = (string) $pendiente['order_id'];

if ($orden_id === '' || !hash_equals($orden_sesion, $orden_id)) {
    json_salida(['ok' => false, 'error' => 'La orden no coincide con la sesión.'], 409);
}

$demo = (bool) ($pendiente['demo'] ?? false);

if ($demo) {
    $captura_id = 'DEMO-CAP-' . strtoupper(bin2hex(random_bytes(6)));
} else {
    $captura = paypal_capturar_orden($orden_id);

    if (!$captura['ok']) {
        json_salida(['ok' => false, 'error' => $captura['error']], 502);
    }

    if ($captura['estado'] !== 'COMPLETED') {
        json_salida([
            'ok'    => false,
            'error' => 'El pago no quedó completado. Estado: ' . ($captura['estado'] ?? 'desconocido') . '.',
        ], 409);
    }

    if ($captura['monto'] !== null && (float) $captura['monto'] !== (float) $pendiente['total']) {
        error_log('PayPal monto distinto: esperado ' . $pendiente['total'] . ', recibido ' . $captura['monto']);
        json_salida(['ok' => false, 'error' => 'El monto capturado no coincide con el total del pedido.'], 409);
    }

    if ($captura['moneda'] !== null && $captura['moneda'] !== PAYPAL_MONEDA) {
        json_salida(['ok' => false, 'error' => 'La moneda del pago no coincide.'], 409);
    }

    if ($captura['captura_id'] === null) {
        json_salida(['ok' => false, 'error' => 'PayPal no devolvió el identificador de captura.'], 502);
    }

    $captura_id = $captura['captura_id'];
}

$usuario = usuario_actual();

$venta = registrar_venta(
    (int) $usuario['id'],
    $pendiente['items'],
    (float) $pendiente['total'],
    $orden_id,
    $captura_id
);

if (!$venta['ok']) {
    json_salida([
        'ok'    => false,
        'error' => $venta['error']
            . ' El pago se recibió; contacta al organizador con tu comprobante.',
    ], 500);
}

carrito_vaciar();
unset($_SESSION['orden_pendiente']);

if ($venta['aviso'] !== null && $venta['aviso'] !== '') {
    flash('info', (string) $venta['aviso']);
}

json_salida([
    'ok'        => true,
    'pedido'    => $venta['pedido_id'],
    'estado'    => $venta['estado'],
    'aviso'     => $venta['aviso'],
    'redirigir' => 'pago-exitoso.php?pedido=' . $venta['pedido_id'],
]);
