<?php
declare(strict_types=1);

/**
 * Entrega el recibo PDF de un pedido.
 *
 * Solo el dueño del pedido o un administrador pueden descargarlo.
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/pedidos.php';
require_once __DIR__ . '/includes/pdf.php';

$pedido_id = (int) ($_GET['pedido'] ?? 0);

if (usuario_actual() === null) {
    flash('error', 'Inicia sesión para descargar tu recibo.');
    redirigir('login-cliente.php?volver=' . urlencode('recibo.php?pedido=' . $pedido_id));
}

$pedido      = $pedido_id > 0 ? pedido_por_id($pedido_id) : null;
$usuario     = usuario_actual();
$propietario = $pedido !== null && (int) $pedido['usuario_id'] === (int) $usuario['id'];

if ($pedido === null || (!$propietario && !es_admin())) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Recibo no encontrado.');
}

if ($pedido['estado'] !== 'pagado') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit('El recibo estará disponible cuando el pedido esté pagado.');
}

$pdf = recibo_pdf($pedido_id);

if ($pdf === null) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    exit('No se pudo generar el recibo.');
}

$nombre    = 'recibo-' . folio_pedido($pedido_id) . '.pdf';
$descargar = isset($_GET['descargar']);

header('Content-Type: application/pdf');
header(
    'Content-Disposition: '
    . ($descargar ? 'attachment' : 'inline')
    . '; filename="' . $nombre . '"'
);
header('Content-Length: ' . strlen($pdf));
header('Cache-Control: private, max-age=0, must-revalidate');
header('X-Content-Type-Options: nosniff');

echo $pdf;
exit;
