<?php
declare(strict_types=1);

/**
 * Acceso al panel de administración.
 */

require_once __DIR__ . '/../includes/auth.php';

if (es_admin()) {
    redirigir('dashboard.php');
}

$errores = [];
$correo  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo   = trim((string) ($_POST['correo'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!csrf_validar($_POST['csrf'] ?? null)) {
        $errores[] = 'La sesión expiró. Vuelve a intentarlo.';
    } elseif ($correo === '' || $password === '') {
        $errores[] = 'Escribe tu correo y contraseña.';
    } else {
        $stmt = db()->prepare(
            "SELECT id, nombre, correo, password_hash, rol
             FROM usuarios
             WHERE correo = ? AND rol = 'admin'
             LIMIT 1"
        );
        $stmt->execute([$correo]);
        $usuario = $stmt->fetch();

        if ($usuario === false || !password_verify($password, $usuario['password_hash'])) {
            $errores[] = 'Correo o contraseña incorrectos.';
        } else {
            iniciar_sesion_usuario($usuario);
            flash('exito', 'Bienvenido, ' . $usuario['nombre'] . '.');
            redirigir('dashboard.php');
        }
    }
}

$raiz          = '../';
$titulo        = 'Acceso administrador | Congreso SysIoT 2026';
$descripcion   = 'Acceso al panel de administración del congreso.';
$pagina_activa = 'admin';
$pagina_id     = 'pagina-admin-login';
$scripts_pie   = '<script src="../js/validacion.js" defer></script>';

include __DIR__ . '/../includes/header.php';
?>
<section class="seccion-acceso contenedor">
  <div class="formulario-acceso">
    <h1>Acceso administrador</h1>
    <p class="ayuda">Este panel es solo para el equipo organizador.</p>

    <?php if ($errores !== []): ?>
      <ul class="lista-errores">
        <?php foreach ($errores as $error): ?>
          <li><?= e($error) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <form method="post" action="login.php" novalidate>
      <?= csrf_campo() ?>

      <div class="campo">
        <label for="correo" class="campo-obligatorio">Correo</label>
        <input
          type="email"
          id="correo"
          name="correo"
          value="<?= e($correo) ?>"
          autocomplete="username"
          data-validar="correo"
          required
        />
      </div>

      <div class="campo">
        <label for="password" class="campo-obligatorio">Contraseña</label>
        <input
          type="password"
          id="password"
          name="password"
          autocomplete="current-password"
          data-validar="clave-acceso"
          required
        />
      </div>

      <button class="btn btn-principal btn-bloque" type="submit">Entrar al panel</button>
    </form>

    <p class="acceso-pie"><a href="../login-cliente.php">Soy cliente</a></p>
  </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
