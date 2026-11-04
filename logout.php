<?php
declare(strict_types=1);

/**
 * Cierre de sesión.
 */

require_once __DIR__ . '/includes/auth.php';

cerrar_sesion();

redirigir('index.php');
