<?php
declare(strict_types=1);

/**
 * Funciones auxiliares de uso general.
 */

/**
 * Escapa un valor para insertarlo en HTML.
 */
function e(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Redirige a una ruta y detiene la ejecución.
 */
function redirigir(string $ruta): never
{
    header('Location: ' . $ruta);
    exit;
}

/**
 * Formatea un precio en pesos mexicanos.
 * Omite los decimales cuando el monto es entero.
 */
function precio(float|int|string $valor): string
{
    $numero    = (float) $valor;
    $decimales = $numero == floor($numero) ? 0 : 2;

    return '$' . number_format($numero, $decimales, '.', ',') . ' MXN';
}

/**
 * Devuelve una ruta relativa a la raíz del sitio según la carpeta actual.
 */
function url(string $ruta): string
{
    return ($GLOBALS['raiz'] ?? '') . $ruta;
}

/**
 * Valida un destino de redirección para evitar saltos a otros dominios.
 *
 * Solo acepta rutas relativas del propio sitio; cualquier intento de
 * redirigir a una URL absoluta o fuera de la carpeta se descarta.
 */
function destino_seguro(string $destino, string $por_defecto = 'index.php'): string
{
    $destino = trim($destino);

    if (
        $destino === ''
        || preg_match('#^(?:[a-z][a-z0-9+.-]*:)?//#i', $destino) === 1
        || str_contains($destino, '..')
        || str_starts_with($destino, '/')
        || str_contains($destino, '\\')
    ) {
        return $por_defecto;
    }

    return $destino;
}

/**
 * Envía una respuesta JSON y termina la ejecución.
 *
 * @param array<string, mixed> $datos
 */
function json_salida(array $datos, int $codigo = 200): never
{
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}
