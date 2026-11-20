<?php
declare(strict_types=1);

/**
 * Inicio de sesión de clientes.
 */

require_once __DIR__ . '/includes/auth.php';

$volver = destino_seguro((string) ($_POST['volver'] ?? $_GET['volver'] ?? 'index.php'));

if (usuario_actual() !== null) {
    redirigir($volver);
}

$error  = '';
$correo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validar($_POST['csrf'] ?? null)) {
        flash('error', 'La sesión expiró. Vuelve a intentarlo.');
        redirigir('login-cliente.php');
    }

    $correo   = mb_strtolower(trim((string) ($_POST['correo'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    if ($correo === '' || $password === '') {
        $error = 'Escribe tu correo y tu contraseña.';
    } else {
        $consulta = db()->prepare(
            'SELECT id, nombre, correo, password_hash, rol FROM usuarios WHERE correo = ?'
        );
        $consulta->execute([$correo]);
        $usuario = $consulta->fetch();

        if ($usuario === false || !password_verify($password, $usuario['password_hash'])) {
            $error = 'Correo o contraseña incorrectos.';
        } else {
            iniciar_sesion_usuario($usuario);
            flash('exito', 'Bienvenido de nuevo, ' . $usuario['nombre'] . '.');
            redirigir($volver);
        }
    }
}

$titulo        = 'Iniciar sesión | Congreso Internacional de Ingeniería en Sistemas e IoT';
$descripcion   = 'Inicia sesión para continuar con tu inscripción al congreso.';
$pagina_activa = 'login';
$scripts_pie   = '<script src="js/validacion.js" defer></script>';
require __DIR__ . '/includes/header.php';
?>
<section class="contenedor seccion-acceso">
  <h1>Iniciar sesión</h1>
  <p class="ayuda">Accede para completar tu inscripción y consultar tus pagos.</p>

  <?php if ($error !== ''): ?>
    <div class="aviso aviso-error" role="alert"><?= e($error) ?></div>
  <?php endif; ?>

  <form class="formulario-acceso" method="post" action="login-cliente.php" novalidate>
    <?= csrf_campo() ?>
    <input type="hidden" name="volver" value="<?= e($volver) ?>" />

    <div class="campo">
      <label for="correo" class="campo-obligatorio">Correo electrónico</label>
      <input type="email" id="correo" name="correo" value="<?= e($correo) ?>" autocomplete="email" maxlength="150" data-validar="correo" required />
    </div>

    <div class="campo">
      <label for="password" class="campo-obligatorio">Contraseña</label>
      <input type="password" id="password" name="password" autocomplete="current-password" data-validar="clave-acceso" required />
    </div>

    <button class="btn btn-principal" type="submit">Entrar</button>

    <p class="acceso-pie">¿Aún no tienes cuenta? <a href="registro.php">Crea una</a>.</p>
  </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
