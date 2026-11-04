<?php
declare(strict_types=1);

/**
 * Consultas de productos (inscripciones y talleres).
 */

require_once __DIR__ . '/../config/db.php';

/**
 * Devuelve los productos activos, del más caro al más barato.
 *
 * @param int $limite 0 = todos
 * @return array<int, array{id: int, nombre: string, descripcion: string, precio: string, stock: int}>
 */
function productos_activos(int $limite = 0): array
{
    $sql = 'SELECT id, nombre, descripcion, precio, stock
            FROM productos
            WHERE activo = 1
            ORDER BY precio DESC, nombre ASC';

    if ($limite > 0) {
        $sql .= ' LIMIT ' . (int) $limite;
    }

    return db()->query($sql)->fetchAll();
}

/**
 * Devuelve un producto por su id, o null si no existe.
 *
 * @return array{id: int, nombre: string, descripcion: string, precio: string, stock: int, activo: int}|null
 */
function producto_por_id(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT id, nombre, descripcion, precio, stock, activo
         FROM productos
         WHERE id = :id'
    );
    $stmt->execute([':id' => $id]);

    $producto = $stmt->fetch();

    return $producto ?: null;
}

/**
 * Devuelve todos los productos, activos e inactivos, para el panel admin.
 *
 * @return array<int, array<string, mixed>>
 */
function productos_todos(): array
{
    return db()->query(
        'SELECT id, nombre, descripcion, precio, stock, activo
         FROM productos
         ORDER BY id ASC'
    )->fetchAll();
}

/**
 * Crea un producto o actualiza uno existente.
 *
 * @param int|null $id null para crear, id para actualizar
 * @return int id del producto guardado
 */
function producto_guardar(
    ?int $id,
    string $nombre,
    string $descripcion,
    float $precio,
    int $stock
): int {
    $pdo = db();

    if ($id === null) {
        $stmt = $pdo->prepare(
            'INSERT INTO productos (nombre, descripcion, precio, stock, activo)
             VALUES (?, ?, ?, ?, 1)'
        );
        $stmt->execute([
            $nombre,
            $descripcion,
            number_format($precio, 2, '.', ''),
            $stock,
        ]);

        return (int) $pdo->lastInsertId();
    }

    $stmt = $pdo->prepare(
        'UPDATE productos
         SET nombre = ?, descripcion = ?, precio = ?, stock = ?
         WHERE id = ?'
    );
    $stmt->execute([
        $nombre,
        $descripcion,
        number_format($precio, 2, '.', ''),
        $stock,
        $id,
    ]);

    return $id;
}

/**
 * Activa o desactiva un producto (baja lógica: conserva el historial).
 */
function producto_alternar_activo(int $id): bool
{
    $stmt = db()->prepare(
        'UPDATE productos SET activo = IF(activo = 1, 0, 1) WHERE id = ?'
    );
    $stmt->execute([$id]);

    return $stmt->rowCount() === 1;
}
