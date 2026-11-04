<?php
declare(strict_types=1);

/**
 * Arranque seguro de la sesión y utilidades relacionadas.
 */

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    $seguro = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $seguro,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

/**
 * Guarda un mensaje para mostrarlo en la siguiente petición.
 *
 * @param string $tipo    error|exito|info
 * @param string $mensaje Texto visible para el usuario.
 */
function flash(string $tipo, string $mensaje): void
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

/**
 * Devuelve los mensajes pendientes y los elimina de la sesión.
 *
 * @return array<int, array{tipo: string, mensaje: string}>
 */
function flashes(): array
{
    $mensajes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);

    return $mensajes;
}

/**
 * Devuelve el token CSRF de la sesión (lo crea si no existe).
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf'];
}

/**
 * Campo oculto listo para insertar dentro de un formulario.
 */
function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="'
        . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Valida el token CSRF recibido por POST.
 */
function csrf_validar(?string $token): bool
{
    return is_string($token)
        && $token !== ''
        && !empty($_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $token);
}
