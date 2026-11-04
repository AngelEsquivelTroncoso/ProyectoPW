<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/errores.php';

/**
 * Conexión a la base de datos del proyecto.
 *
 * Devuelve una instancia única de PDO configurada para lanzar excepciones.
 * Ajusta las constantes si cambias de servidor o de credenciales.
 */

const DB_HOST    = 'localhost';
const DB_NAME    = 'proyecto_pw';
const DB_USER    = 'root';
const DB_PASS    = '';
const DB_CHARSET = 'utf8mb4';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_NAME,
        DB_CHARSET
    );

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        error_log('Error de conexión a la base de datos: ' . $e->getMessage());

        http_response_code(500);
        exit('No fue posible conectar con la base de datos.');
    }

    return $pdo;
}
