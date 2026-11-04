<?php
declare(strict_types=1);

/**
 * Encabezado compartido del sitio.
 *
 * La página que lo incluye puede definir, antes del include:
 *   $titulo         — título de la pestaña
 *   $descripcion    — meta description
 *   $pagina_activa  — clave del enlace activo (inicio, programa, ...)
 *   $pagina_id      — id del <body> (opcional)
 *   $raiz           — prefijo relativo a la raíz, por ejemplo '../'
 */

require_once __DIR__ . '/auth.php';

$raiz = $raiz ?? '';
$GLOBALS['raiz'] = $raiz;

$titulo         = $titulo ?? 'Congreso Internacional de Ingeniería en Sistemas e IoT';
$descripcion    = $descripcion ?? 'Portal del Congreso Internacional de Ingeniería en Sistemas e IoT: fechas, sede, registro y pagos.';
$pagina_activa  = $pagina_activa ?? '';
$pagina_id      = $pagina_id ?? '';
$usuario        = usuario_actual();
$en_carrito     = array_sum($_SESSION['carrito'] ?? []);

$activo = static fn (string $clave): string => $pagina_activa === $clave
    ? ' aria-current="page"'
    : '';
?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="<?= e($descripcion) ?>" />
    <title><?= e($titulo) ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600&family=Space+Grotesk:wght@500;700&display=swap"
      rel="stylesheet"
    />

    <link rel="stylesheet" href="<?= e($raiz) ?>styles.css" />
  </head>
  <body<?= $pagina_id !== '' ? ' id="' . e($pagina_id) . '"' : '' ?>>
    <a class="saltar-contenido" href="#contenido-principal">Saltar al contenido</a>

    <header class="sitio-encabezado" id="sitio-encabezado">
      <div class="contenedor barra-nav">
        <a class="logo" href="<?= e($raiz) ?>index.php">Congreso SysIoT 2026</a>
        <nav aria-label="Navegación principal">
          <ul class="menu-principal" id="menu-principal">
            <li><a href="<?= e($raiz) ?>index.php"<?= $activo('inicio') ?>>Inicio</a></li>
            <li><a href="<?= e($raiz) ?>programa.php"<?= $activo('programa') ?>>Programa</a></li>
            <li><a href="<?= e($raiz) ?>ponencias.php"<?= $activo('ponencias') ?>>Ponencias</a></li>
            <li><a href="<?= e($raiz) ?>memoria.php"<?= $activo('memoria') ?>>Memoria</a></li>
            <li><a href="<?= e($raiz) ?>catalogo.php"<?= $activo('catalogo') ?>>Catálogo</a></li>
            <li>
              <a href="<?= e($raiz) ?>carrito.php"<?= $activo('carrito') ?>>
                Carrito<?php if ($en_carrito > 0): ?> <span class="contador-carrito"><?= (int) $en_carrito ?></span><?php endif; ?>
              </a>
            </li>
            <?php if ($usuario !== null): ?>
              <?php if (es_admin()): ?>
                <li><a href="<?= e($raiz) ?>admin/dashboard.php">Admin</a></li>
              <?php endif; ?>
              <li><a href="<?= e($raiz) ?>logout.php">Salir (<?= e($usuario['nombre']) ?>)</a></li>
            <?php else: ?>
              <li><a href="<?= e($raiz) ?>login-cliente.php"<?= $activo('login') ?>>Iniciar sesión</a></li>
              <li><a class="enlace-registro" href="<?= e($raiz) ?>registro.php"<?= $activo('registro') ?>>Registro</a></li>
            <?php endif; ?>
          </ul>
        </nav>
      </div>
    </header>

    <main id="contenido-principal">
      <?php foreach (flashes() as $mensaje): ?>
        <div class="contenedor">
          <p class="aviso aviso-<?= e($mensaje['tipo']) ?>" role="status"><?= e($mensaje['mensaje']) ?></p>
        </div>
      <?php endforeach; ?>
