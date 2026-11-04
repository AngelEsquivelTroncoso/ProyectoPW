<?php
declare(strict_types=1);

/**
 * Registro y consulta de pedidos.
 */

require_once __DIR__ . '/../config/db.php';

/**
 * Registra un pedido con su detalle a partir de los ítems del carrito.
 *
 * El total y los precios unitarios se toman de los datos de la base
 * (no del navegador). No modifica el stock: eso se hace al confirmar
 * el pago.
 *
 * @param array<int, array{producto: array, cantidad: int, subtotal: float}> $items
 * @param string $estado pendiente|pagado|cancelado
 */
function crear_pedido(
    int $usuario_id,
    array $items,
    string $estado = 'pendiente',
    ?string $orden_id = null,
    ?string $captura_id = null
): int {
    if ($items === []) {
        throw new InvalidArgumentException('El pedido no tiene líneas.');
    }

    $total = 0.0;

    foreach ($items as $item) {
        $total += (float) $item['subtotal'];
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $cabecera = $pdo->prepare(
            'INSERT INTO pedidos (usuario_id, total, estado, paypal_order_id, paypal_captura_id)
             VALUES (?, ?, ?, ?, ?)'
        );

        $cabecera->execute([
            $usuario_id,
            number_format($total, 2, '.', ''),
            $estado,
            $orden_id,
            $captura_id,
        ]);

        $pedido_id = (int) $pdo->lastInsertId();

        $detalle = $pdo->prepare(
            'INSERT INTO detalle_pedido (pedido_id, producto_id, cantidad, precio_unitario)
             VALUES (?, ?, ?, ?)'
        );

        foreach ($items as $item) {
            $detalle->execute([
                $pedido_id,
                (int) $item['producto']['id'],
                (int) $item['cantidad'],
                number_format((float) $item['producto']['precio'], 2, '.', ''),
            ]);
        }

        $pdo->commit();

        return $pedido_id;
    } catch (Throwable $e) {
        $pdo->rollBack();

        throw $e;
    }
}

/**
 * Registra la venta definitiva de forma atómica.
 *
 * Bloquea los productos con SELECT ... FOR UPDATE, verifica cupos,
 * inserta el pedido en estado 'pagado' y descuenta el stock con la
 * guarda `stock >= cantidad`.
 *
 * Si al confirmar ya no hay cupos, el pago igual se registró: el pedido
 * queda como 'pendiente' para revisión manual y se devuelve un aviso.
 *
 * @param array<int, array{producto: array, cantidad: int, subtotal: float}> $items
 * @return array{ok: bool, pedido_id: ?int, estado: ?string, aviso: ?string, error: ?string}
 */
function registrar_venta(
    int $usuario_id,
    array $items,
    float $total_esperado,
    ?string $orden_id = null,
    ?string $captura_id = null
): array {
    if ($items === []) {
        return [
            'ok'        => false,
            'pedido_id' => null,
            'estado'    => null,
            'aviso'     => null,
            'error'     => 'El pedido no tiene líneas.',
        ];
    }

    $pdo = db();

    /** Deja el pedido pendiente para revisión cuando el cobro ya ocurrió. */
    $dejar_pendiente = static function (string $aviso) use ($usuario_id, $items, $orden_id, $captura_id): array {
        $pedido_id = crear_pedido($usuario_id, $items, 'pendiente', $orden_id, $captura_id);

        return [
            'ok'        => true,
            'pedido_id' => $pedido_id,
            'estado'    => 'pendiente',
            'aviso'     => $aviso,
            'error'     => null,
        ];
    };

    $pdo->beginTransaction();

    try {
        $bloqueo = $pdo->prepare(
            'SELECT id, nombre, precio, stock, activo
             FROM productos
             WHERE id = ?
             FOR UPDATE'
        );

        $lineas = [];
        $total  = 0.0;

        foreach ($items as $item) {
            $producto_id = (int) ($item['producto']['id'] ?? 0);
            $cantidad    = (int) ($item['cantidad'] ?? 0);

            if ($producto_id <= 0 || $cantidad <= 0) {
                $pdo->rollBack();

                return [
                    'ok'        => false,
                    'pedido_id' => null,
                    'estado'    => null,
                    'aviso'     => null,
                    'error'     => 'El pedido contiene una línea inválida.',
                ];
            }

            $bloqueo->execute([$producto_id]);
            $producto = $bloqueo->fetch();

            if ($producto === false || (int) $producto['activo'] !== 1) {
                $pdo->rollBack();

                return [
                    'ok'        => false,
                    'pedido_id' => null,
                    'estado'    => null,
                    'aviso'     => null,
                    'error'     => 'Un producto del pedido ya no está disponible.',
                ];
            }

            if ((int) $producto['stock'] < $cantidad) {
                $pdo->rollBack();

                return $dejar_pendiente(
                    'El pago se recibió, pero ' . $producto['nombre']
                    . ' se quedó sin cupos. Tu pedido quedó pendiente para revisión.'
                );
            }

            $precio   = (float) $producto['precio'];
            $total   += $precio * $cantidad;
            $lineas[] = ['id' => $producto_id, 'cantidad' => $cantidad, 'precio' => $precio];
        }

        if (number_format($total, 2, '.', '') !== number_format($total_esperado, 2, '.', '')) {
            $pdo->rollBack();
            error_log('Total distinto al esperado: calculado ' . $total . ', esperado ' . $total_esperado);

            return [
                'ok'        => false,
                'pedido_id' => null,
                'estado'    => null,
                'aviso'     => null,
                'error'     => 'El total del pedido cambió. Vuelve a intentarlo.',
            ];
        }

        $cabecera = $pdo->prepare(
            "INSERT INTO pedidos (usuario_id, total, estado, paypal_order_id, paypal_captura_id)
             VALUES (?, ?, 'pagado', ?, ?)"
        );
        $cabecera->execute([
            $usuario_id,
            number_format($total, 2, '.', ''),
            $orden_id,
            $captura_id,
        ]);

        $pedido_id = (int) $pdo->lastInsertId();

        $detalle   = $pdo->prepare(
            'INSERT INTO detalle_pedido (pedido_id, producto_id, cantidad, precio_unitario)
             VALUES (?, ?, ?, ?)'
        );
        $descuento = $pdo->prepare(
            'UPDATE productos SET stock = stock - ? WHERE id = ? AND stock >= ?'
        );

        foreach ($lineas as $linea) {
            $detalle->execute([
                $pedido_id,
                $linea['id'],
                $linea['cantidad'],
                number_format($linea['precio'], 2, '.', ''),
            ]);

            $descuento->execute([$linea['cantidad'], $linea['id'], $linea['cantidad']]);

            if ($descuento->rowCount() !== 1) {
                $pdo->rollBack();

                return $dejar_pendiente(
                    'El pago se recibió, pero el inventario cambió durante la compra. '
                    . 'Tu pedido quedó pendiente para revisión.'
                );
            }
        }

        $pdo->commit();

        return [
            'ok'        => true,
            'pedido_id' => $pedido_id,
            'estado'    => 'pagado',
            'aviso'     => null,
            'error'     => null,
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log('Error al registrar la venta: ' . $e->getMessage());

        return [
            'ok'        => false,
            'pedido_id' => null,
            'estado'    => null,
            'aviso'     => null,
            'error'     => 'No se pudo registrar el pedido.',
        ];
    }
}

/**
 * Confirma un pedido pendiente y descuenta los cupos.
 *
 * Se usa cuando el cobro ya ocurrió pero el stock se agotó al momento
 * de la compra. Vuelve a validar cada línea con bloqueo de fila.
 *
 * @return array{ok: bool, mensaje: string}
 */
function confirmar_pedido(int $pedido_id): array
{
    $pdo = db();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare('SELECT id, estado FROM pedidos WHERE id = ? FOR UPDATE');
        $stmt->execute([$pedido_id]);
        $pedido = $stmt->fetch();

        if ($pedido === false) {
            $pdo->rollBack();

            return ['ok' => false, 'mensaje' => 'El pedido no existe.'];
        }

        if ($pedido['estado'] !== 'pendiente') {
            $pdo->rollBack();

            return ['ok' => false, 'mensaje' => 'Solo se pueden confirmar pedidos pendientes.'];
        }

        $lineas = $pdo->prepare(
            'SELECT producto_id, cantidad FROM detalle_pedido WHERE pedido_id = ? ORDER BY id ASC'
        );
        $lineas->execute([$pedido_id]);

        $bloqueo   = $pdo->prepare('SELECT nombre, stock FROM productos WHERE id = ? FOR UPDATE');
        $descuento = $pdo->prepare('UPDATE productos SET stock = stock - ? WHERE id = ? AND stock >= ?');

        foreach ($lineas->fetchAll() as $linea) {
            $producto_id = (int) $linea['producto_id'];
            $cantidad    = (int) $linea['cantidad'];

            $bloqueo->execute([$producto_id]);
            $producto = $bloqueo->fetch();

            if ($producto === false) {
                $pdo->rollBack();

                return ['ok' => false, 'mensaje' => 'Uno de los productos del pedido ya no existe.'];
            }

            if ((int) $producto['stock'] < $cantidad) {
                $pdo->rollBack();

                return [
                    'ok'      => false,
                    'mensaje' => 'Sin cupos suficientes de ' . $producto['nombre'] . '.',
                ];
            }

            $descuento->execute([$cantidad, $producto_id, $cantidad]);

            if ($descuento->rowCount() !== 1) {
                $pdo->rollBack();

                return [
                    'ok'      => false,
                    'mensaje' => 'No se pudo descontar el stock de ' . $producto['nombre'] . '.',
                ];
            }
        }

        $actualizar = $pdo->prepare(
            "UPDATE pedidos SET estado = 'pagado' WHERE id = ? AND estado = 'pendiente'"
        );
        $actualizar->execute([$pedido_id]);

        if ($actualizar->rowCount() !== 1) {
            $pdo->rollBack();

            return ['ok' => false, 'mensaje' => 'El pedido cambió de estado. Recarga la página.'];
        }

        $pdo->commit();

        return ['ok' => true, 'mensaje' => 'Pedido confirmado y cupos descontados.'];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log('Error al confirmar pedido: ' . $e->getMessage());

        return ['ok' => false, 'mensaje' => 'No se pudo confirmar el pedido.'];
    }
}

/**
 * Cancela un pedido. Si estaba pagado, devuelve los cupos al inventario.
 *
 * @return array{ok: bool, mensaje: string}
 */
function cancelar_pedido(int $pedido_id): array
{
    $pdo = db();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare('SELECT id, estado FROM pedidos WHERE id = ? FOR UPDATE');
        $stmt->execute([$pedido_id]);
        $pedido = $stmt->fetch();

        if ($pedido === false) {
            $pdo->rollBack();

            return ['ok' => false, 'mensaje' => 'El pedido no existe.'];
        }

        if ($pedido['estado'] === 'cancelado') {
            $pdo->rollBack();

            return ['ok' => false, 'mensaje' => 'El pedido ya está cancelado.'];
        }

        $estaba_pagado = $pedido['estado'] === 'pagado';

        if ($estaba_pagado) {
            $lineas = $pdo->prepare(
                'SELECT producto_id, cantidad FROM detalle_pedido WHERE pedido_id = ?'
            );
            $lineas->execute([$pedido_id]);

            $devolver = $pdo->prepare('UPDATE productos SET stock = stock + ? WHERE id = ?');

            foreach ($lineas->fetchAll() as $linea) {
                $devolver->execute([
                    (int) $linea['cantidad'],
                    (int) $linea['producto_id'],
                ]);
            }
        }

        $actualizar = $pdo->prepare(
            "UPDATE pedidos SET estado = 'cancelado' WHERE id = ? AND estado <> 'cancelado'"
        );
        $actualizar->execute([$pedido_id]);

        if ($actualizar->rowCount() !== 1) {
            $pdo->rollBack();

            return ['ok' => false, 'mensaje' => 'El pedido cambió de estado. Recarga la página.'];
        }

        $pdo->commit();

        return [
            'ok'      => true,
            'mensaje' => $estaba_pagado
                ? 'Pedido cancelado y cupos devueltos.'
                : 'Pedido cancelado.',
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log('Error al cancelar pedido: ' . $e->getMessage());

        return ['ok' => false, 'mensaje' => 'No se pudo cancelar el pedido.'];
    }
}

/**
 * Folio legible de un pedido.
 */
function folio_pedido(int $pedido_id): string
{
    return 'SYS-2026-' . str_pad((string) $pedido_id, 4, '0', STR_PAD_LEFT);
}

/**
 * Etiqueta legible del estado de un pedido.
 */
function estado_pedido(string $estado): string
{
    return match ($estado) {
        'pagado'    => 'Pagado',
        'pendiente' => 'Pendiente',
        'cancelado' => 'Cancelado',
        default     => ucfirst($estado),
    };
}

/**
 * Devuelve un pedido por su id, o null si no existe.
 *
 * @return array<string, mixed>|null
 */
function pedido_por_id(int $pedido_id): ?array
{
    $stmt = db()->prepare('SELECT * FROM pedidos WHERE id = ? LIMIT 1');
    $stmt->execute([$pedido_id]);

    $pedido = $stmt->fetch();

    return $pedido === false ? null : $pedido;
}

/**
 * Devuelve un pedido con los datos del cliente, o null si no existe.
 *
 * @return array<string, mixed>|null
 */
function pedido_con_cliente(int $pedido_id): ?array
{
    $stmt = db()->prepare(
        'SELECT p.*, u.nombre AS cliente_nombre, u.correo AS cliente_correo
         FROM pedidos p
         INNER JOIN usuarios u ON u.id = p.usuario_id
         WHERE p.id = ?
         LIMIT 1'
    );

    $stmt->execute([$pedido_id]);
    $pedido = $stmt->fetch();

    return $pedido === false ? null : $pedido;
}

/**
 * Devuelve las líneas de un pedido con el nombre del producto.
 *
 * @return array<int, array<string, mixed>>
 */
function detalle_de_pedido(int $pedido_id): array
{
    $stmt = db()->prepare(
        'SELECT d.*, p.nombre
         FROM detalle_pedido d
         INNER JOIN productos p ON p.id = d.producto_id
         WHERE d.pedido_id = ?
         ORDER BY d.id ASC'
    );

    $stmt->execute([$pedido_id]);

    return $stmt->fetchAll();
}
