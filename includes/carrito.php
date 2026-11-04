<?php
declare(strict_types=1);

/**
 * Carrito de compras almacenado en la sesión.
 *
 * Estructura: $_SESSION['carrito'] = [producto_id => cantidad].
 * Los precios y el stock SIEMPRE se leen de la base de datos.
 */

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/funciones.php';
require_once __DIR__ . '/productos.php';

/**
 * Devuelve el carrito crudo: [producto_id => cantidad].
 *
 * @return array<int, int>
 */
function carrito(): array
{
    return $_SESSION['carrito'] ?? [];
}

/**
 * Suma de unidades en el carrito.
 */
function carrito_cantidad_total(): int
{
    return array_sum(carrito());
}

/**
 * Agrega unidades de un producto validando que haya cupo.
 *
 * @return array{ok: bool, mensaje: string}
 */
function carrito_agregar(int $producto_id, int $cantidad = 1): array
{
    $producto = producto_por_id($producto_id);

    if ($producto === null || (int) $producto['activo'] !== 1) {
        return ['ok' => false, 'mensaje' => 'El producto no está disponible.'];
    }

    $cantidad = max(1, $cantidad);
    $actual   = (int) (carrito()[$producto_id] ?? 0);
    $nueva    = $actual + $cantidad;
    $stock    = (int) $producto['stock'];

    if ($nueva > $stock) {
        return [
            'ok'      => false,
            'mensaje' => 'Solo hay ' . $stock . ' cupos disponibles de ' . $producto['nombre'] . '.',
        ];
    }

    $_SESSION['carrito'][$producto_id] = $nueva;

    return ['ok' => true, 'mensaje' => $producto['nombre'] . ' se agregó al carrito.'];
}

/**
 * Fija la cantidad de un producto. Si es 0 o menor, lo elimina.
 *
 * @return array{ok: bool, mensaje: string}
 */
function carrito_actualizar(int $producto_id, int $cantidad): array
{
    if ($cantidad <= 0) {
        return carrito_quitar($producto_id);
    }

    $producto = producto_por_id($producto_id);

    if ($producto === null || (int) $producto['activo'] !== 1) {
        unset($_SESSION['carrito'][$producto_id]);

        return ['ok' => false, 'mensaje' => 'El producto ya no está disponible y se quitó del carrito.'];
    }

    if ($cantidad > (int) $producto['stock']) {
        return [
            'ok'      => false,
            'mensaje' => 'Solo hay ' . (int) $producto['stock'] . ' cupos disponibles de ' . $producto['nombre'] . '.',
        ];
    }

    $_SESSION['carrito'][$producto_id] = $cantidad;

    return ['ok' => true, 'mensaje' => 'Cantidad actualizada.'];
}

/**
 * Quita un producto del carrito.
 *
 * @return array{ok: bool, mensaje: string}
 */
function carrito_quitar(int $producto_id): array
{
    if (!isset($_SESSION['carrito'][$producto_id])) {
        return ['ok' => false, 'mensaje' => 'Ese producto no estaba en el carrito.'];
    }

    unset($_SESSION['carrito'][$producto_id]);

    return ['ok' => true, 'mensaje' => 'Producto eliminado del carrito.'];
}

/**
 * Vacía el carrito completo.
 *
 * @return array{ok: bool, mensaje: string}
 */
function carrito_vaciar(): array
{
    $_SESSION['carrito'] = [];

    return ['ok' => true, 'mensaje' => 'El carrito quedó vacío.'];
}

/**
 * Devuelve los ítems con datos actuales de la base de datos.
 *
 * Descarta productos eliminados o inactivos.
 *
 * @return array<int, array{producto: array, cantidad: int, subtotal: float}>
 */
function carrito_items(): array
{
    $items = [];

    foreach (carrito() as $producto_id => $cantidad) {
        $producto = producto_por_id((int) $producto_id);

        if ($producto === null || (int) $producto['activo'] !== 1) {
            unset($_SESSION['carrito'][$producto_id]);
            continue;
        }

        $cantidad = (int) $cantidad;

        $items[] = [
            'producto' => $producto,
            'cantidad' => $cantidad,
            'subtotal' => (float) $producto['precio'] * $cantidad,
        ];
    }

    return $items;
}

/**
 * Total del carrito calculado con los precios de la base de datos.
 */
function carrito_total(): float
{
    $total = 0.0;

    foreach (carrito_items() as $item) {
        $total += $item['subtotal'];
    }

    return $total;
}
