<?php
declare(strict_types=1);

/**
 * Autenticación y control de acceso.
 */

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/funciones.php';
require_once __DIR__ . '/../config/db.php';

/**
 * Datos del usuario en sesión, o null si no hay sesión iniciada.
 *
 * @return array{id: int, nombre: string, correo: string, rol: string}|null
 */
function usuario_actual(): ?array
{
    return $_SESSION['usuario'] ?? null;
}

/**
 * Indica si el usuario en sesión es administrador.
 */
function es_admin(): bool
{
    return ($_SESSION['usuario']['rol'] ?? null) === 'admin';
}

/**
 * Exige una sesión iniciada (cliente o administrador).
 */
function requiere_login(string $ruta_login = 'login-cliente.php'): void
{
    if (usuario_actual() === null) {
        flash('error', 'Inicia sesión para continuar.');
        redirigir(url($ruta_login));
    }
}

/**
 * Exige una sesión con rol de administrador.
 */
function requiere_admin(string $ruta_login = 'login.php'): void
{
    if (!es_admin()) {
        flash('error', 'Acceso restringido al administrador.');
        redirigir(url($ruta_login));
    }
}

/**
 * Registra al usuario en la sesión tras un login correcto.
 *
 * @param array{id: int|string, nombre: string, correo: string, rol: string} $usuario
 */
function iniciar_sesion_usuario(array $usuario): void
{
    session_regenerate_id(true);

    $_SESSION['usuario'] = [
        'id'     => (int) $usuario['id'],
        'nombre' => $usuario['nombre'],
        'correo' => $usuario['correo'],
        'rol'    => $usuario['rol'],
    ];
}

/**
 * Cierra la sesión y elimina la cookie.
 */
function cerrar_sesion(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}
