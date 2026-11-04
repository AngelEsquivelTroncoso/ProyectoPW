<?php
declare(strict_types=1);

/**
 * Subnavegación del panel de administración.
 *
 * La página que lo incluye define $admin_activo antes del include.
 */

$admin_activo = $admin_activo ?? '';

$enlaces_admin = [
    'dashboard' => ['dashboard.php', 'Resumen'],
    'productos' => ['productos.php', 'Productos'],
    'pedidos'   => ['pedidos.php', 'Pedidos'],
];
?>
<nav class="admin-subnav contenedor" aria-label="Secciones del panel">
  <ul>
    <?php foreach ($enlaces_admin as $clave => [$url, $texto]): ?>
      <li>
        <a href="<?= e($url) ?>"<?= $admin_activo === $clave ? ' aria-current="page"' : '' ?>>
          <?= e($texto) ?>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
</nav>
