<?php
declare(strict_types=1);

/**
 * Gestión de productos: alta, edición y baja lógica.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/productos.php';

requiere_admin();

$errores  = [];
$editando = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validar($_POST['csrf'] ?? null)) {
        flash('error', 'La sesión expiró. Vuelve a intentarlo.');
        redirigir('productos.php');
    }

    $accion = (string) ($_POST['accion'] ?? '');

    if ($accion === 'alternar') {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0 && producto_alternar_activo($id)) {
            flash('exito', 'El estado del producto cambió.');
        } else {
            flash('error', 'No se pudo actualizar el producto.');
        }

        redirigir('productos.php');
    }

    if ($accion === 'guardar') {
        $id          = ($_POST['id'] ?? '') !== '' ? (int) $_POST['id'] : null;
        $nombre      = trim((string) ($_POST['nombre'] ?? ''));
        $descripcion = trim((string) ($_POST['descripcion'] ?? ''));
        $precio      = (float) ($_POST['precio'] ?? 0);
        $stock       = (int) ($_POST['stock'] ?? 0);

        if (mb_strlen($nombre) < 3 || mb_strlen($nombre) > 150) {
            $errores[] = 'El nombre debe tener entre 3 y 150 caracteres.';
        }

        if (mb_strlen($descripcion) > 2000) {
            $errores[] = 'La descripción no puede superar los 2000 caracteres.';
        }

        if ($precio <= 0) {
            $errores[] = 'El precio debe ser mayor que cero.';
        }

        if ($stock < 0) {
            $errores[] = 'El stock no puede ser negativo.';
        }

        if ($errores === []) {
            producto_guardar($id, $nombre, $descripcion, $precio, $stock);
            flash('exito', $id === null ? 'Producto creado.' : 'Producto actualizado.');
            redirigir('productos.php');
        }

        $editando = [
            'id'          => $id,
            'nombre'      => $nombre,
            'descripcion' => $descripcion,
            'precio'      => $precio,
            'stock'       => $stock,
            'activo'      => 1,
        ];
    }
}

$editar_id = (int) ($_GET['editar'] ?? 0);

if ($editar_id > 0 && $editando === null) {
    $editando = producto_por_id($editar_id);

    if ($editando === null) {
        flash('error', 'El producto no existe.');
        redirigir('productos.php');
    }
}

$productos = productos_todos();

$raiz          = '../';
$titulo        = 'Productos | Administración SysIoT 2026';
$descripcion   = 'Alta, edición y baja de productos del congreso.';
$pagina_activa = 'admin';
$pagina_id     = 'pagina-admin-productos';

include __DIR__ . '/../includes/header.php';

$admin_activo = 'productos';
include __DIR__ . '/_navegacion.php';
?>
<section class="contenedor">
  <h1>Productos</h1>
  <p class="ayuda">Las inscripciones y talleres que se venden en el catálogo.</p>

  <?php if ($errores !== []): ?>
    <ul class="lista-errores">
      <?php foreach ($errores as $error): ?>
        <li><?= e($error) ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <div class="admin-tabla-contenedor">
    <table class="admin-tabla">
      <thead>
        <tr>
          <th scope="col">ID</th>
          <th scope="col">Producto</th>
          <th scope="col">Precio</th>
          <th scope="col">Cupos</th>
          <th scope="col">Estado</th>
          <th scope="col">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($productos as $producto): ?>
          <tr>
            <td><?= (int) $producto['id'] ?></td>
            <td>
              <strong><?= e($producto['nombre']) ?></strong>
              <?php if (!empty($producto['descripcion'])): ?>
                <br /><span class="admin-nota"><?= e($producto['descripcion']) ?></span>
              <?php endif; ?>
            </td>
            <td><?= e(precio($producto['precio'])) ?></td>
            <td><?= (int) $producto['stock'] ?></td>
            <td>
              <span class="badge-estado" data-estado="<?= (int) $producto['activo'] === 1 ? 'pagado' : 'cancelado' ?>">
                <?= (int) $producto['activo'] === 1 ? 'Activo' : 'Inactivo' ?>
              </span>
            </td>
            <td>
              <div class="admin-acciones">
                <a class="btn btn-contorno btn-pequeno" href="productos.php?editar=<?= (int) $producto['id'] ?>">Editar</a>
                <form method="post" action="productos.php">
                  <?= csrf_campo() ?>
                  <input type="hidden" name="accion" value="alternar" />
                  <input type="hidden" name="id" value="<?= (int) $producto['id'] ?>" />
                  <button class="btn btn-secundario btn-pequeno" type="submit">
                    <?= (int) $producto['activo'] === 1 ? 'Desactivar' : 'Activar' ?>
                  </button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <h2 id="formulario-producto">
    <?= $editando === null ? 'Nuevo producto' : 'Editar producto #' . (int) $editando['id'] ?>
  </h2>

  <form class="admin-form" method="post" action="productos.php" novalidate>
    <?= csrf_campo() ?>
    <input type="hidden" name="accion" value="guardar" />
    <input type="hidden" name="id" value="<?= $editando === null ? '' : (int) $editando['id'] ?>" />

    <div class="campo">
      <label for="nombre" class="campo-obligatorio">Nombre</label>
      <input
        type="text"
        id="nombre"
        name="nombre"
        maxlength="150"
        value="<?= e($editando['nombre'] ?? '') ?>"
        required
      />
    </div>

    <div class="campo">
      <label for="descripcion">Descripción</label>
      <textarea id="descripcion" name="descripcion" maxlength="2000" rows="4"><?= e($editando['descripcion'] ?? '') ?></textarea>
    </div>

    <div class="admin-form-fila">
      <div class="campo">
        <label for="precio" class="campo-obligatorio">Precio (MXN)</label>
        <input
          type="number"
          id="precio"
          name="precio"
          min="0.01"
          step="0.01"
          value="<?= e((string) ($editando['precio'] ?? '')) ?>"
          required
        />
      </div>

      <div class="campo">
        <label for="stock" class="campo-obligatorio">Cupos</label>
        <input
          type="number"
          id="stock"
          name="stock"
          min="0"
          step="1"
          value="<?= e((string) ($editando['stock'] ?? 0)) ?>"
          required
        />
      </div>
    </div>

    <div class="admin-acciones">
      <button class="btn btn-principal" type="submit">
        <?= $editando === null ? 'Crear producto' : 'Guardar cambios' ?>
      </button>
      <?php if ($editando !== null): ?>
        <a class="btn btn-contorno" href="productos.php">Cancelar edición</a>
      <?php endif; ?>
    </div>
  </form>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
