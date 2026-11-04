<?php
declare(strict_types=1);

/**
 * Manejo central de excepciones no capturadas.
 *
 * Registra el detalle en el log y responde con un mensaje genérico,
 * sin exponer rutas, consultas ni trazas al usuario.
 */

/**
 * Indica si la petición espera una respuesta JSON.
 */
function peticion_espera_json(): bool
{
    $acepta = $_SERVER['HTTP_ACCEPT'] ?? '';
    $ruta   = $_SERVER['REQUEST_URI'] ?? '';

    return str_contains($acepta, 'application/json')
        || str_contains($ruta, '/api/');
}

/**
 * Manejador global de excepciones.
 */
function manejar_excepcion(Throwable $e): void
{
    error_log(sprintf(
        'Excepción no controlada: %s: %s en %s:%d',
        $e::class,
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    ));

    if (!headers_sent()) {
        http_response_code(500);
        header(
            'Content-Type: '
            . (peticion_espera_json() ? 'application/json' : 'text/html')
            . '; charset=utf-8'
        );
    }

    if (peticion_espera_json()) {
        echo json_encode(
            ['ok' => false, 'error' => 'Ocurrió un error inesperado. Intenta de nuevo.'],
            JSON_UNESCAPED_UNICODE
        );

        return;
    }

    echo '<!doctype html><html lang="es"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>Error del servidor</title></head><body>'
        . '<main><h1>Algo salió mal</h1>'
        . '<p>Ocurrió un error inesperado. Intenta de nuevo en unos momentos.</p>'
        . '</main></body></html>';
}

set_exception_handler('manejar_excepcion');
