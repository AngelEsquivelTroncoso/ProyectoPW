<?php
declare(strict_types=1);

/**
 * Registro de clientes.
 */

require_once __DIR__ . '/includes/auth.php';

const LARGO_MINIMO_PASSWORD = 8;

if (usuario_actual() !== null) {
    redirigir('index.php');
}

$errores = [];
$nombre  = '';
$correo  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validar($_POST['csrf'] ?? null)) {
        flash('error', 'La sesión expiró. Vuelve a intentarlo.');
        redirigir('registro.php');
    }

    $nombre    = trim((string) ($_POST['nombre'] ?? ''));
    $correo    = mb_strtolower(trim((string) ($_POST['correo'] ?? '')));
    $password  = (string) ($_POST['password'] ?? '');
    $confirmar = (string) ($_POST['confirmar'] ?? '');

    if (mb_strlen($nombre) < 3 || mb_strlen($nombre) > 100) {
        $errores[] = 'Escribe tu nombre completo (entre 3 y 100 caracteres).';
    }

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || mb_strlen($correo) > 150) {
        $errores[] = 'El correo electrónico no es válido.';
    }

    if (mb_strlen($password) < LARGO_MINIMO_PASSWORD) {
        $errores[] = 'La contraseña debe tener al menos ' . LARGO_MINIMO_PASSWORD . ' caracteres.';
    }

    if ($password !== $confirmar) {
        $errores[] = 'Las contraseñas no coinciden.';
    }

    if ($errores === []) {
        $pdo = db();

        $consulta = $pdo->prepare('SELECT id FROM usuarios WHERE correo = ?');
        $consulta->execute([$correo]);

        if ($consulta->fetch() !== false) {
            $errores[] = 'Ya existe una cuenta con ese correo.';
        } else {
            try {
                $alta = $pdo->prepare(
                    'INSERT INTO usuarios (nombre, correo, password_hash) VALUES (?, ?, ?)'
                );
                $alta->execute([
                    $nombre,
                    $correo,
                    password_hash($password, PASSWORD_DEFAULT),
                ]);

                iniciar_sesion_usuario([
                    'id'     => (int) $pdo->lastInsertId(),
                    'nombre' => $nombre,
                    'correo' => $correo,
                    'rol'    => 'cliente',
                ]);

                flash('exito', 'Tu cuenta fue creada. ¡Bienvenido!');
                redirigir('catalogo.php');
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $errores[] = 'Ya existe una cuenta con ese correo.';
                } else {
                    error_log('Error al registrar usuario: ' . $e->getMessage());
                    $errores[] = 'No fue posible crear la cuenta. Inténtalo más tarde.';
                }
            }
        }
    }
}

$titulo        = 'Crear cuenta | Congreso Internacional de Ingeniería en Sistemas e IoT';
$descripcion   = 'Crea tu cuenta para inscribirte al congreso y administrar tus pagos.';
$pagina_activa = 'registro';
$scripts_pie   = '<script src="js/validacion.js" defer></script>';
require __DIR__ . '/includes/header.php';
?>
<section class="contenedor seccion-acceso">
  <h1>Crear cuenta</h1>
  <p class="ayuda">Regístrate para inscribirte al congreso y dar seguimiento a tus pagos.</p>

  <?php if ($errores !== []): ?>
    <div class="aviso aviso-error" role="alert">
      <ul class="lista-errores">
        <?php foreach ($errores as $error): ?>
          <li><?= e($error) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form class="formulario-acceso" method="post" action="registro.php" novalidate>
    <?= csrf_campo() ?>

    <div class="campo">
      <label for="nombre" class="campo-obligatorio">Nombre completo</label>
      <input type="text" id="nombre" name="nombre" value="<?= e($nombre) ?>" autocomplete="name" maxlength="100" data-validar="nombre" required />
    </div>

    <div class="campo">
      <label for="correo" class="campo-obligatorio">Correo electrónico</label>
      <input type="email" id="correo" name="correo" value="<?= e($correo) ?>" autocomplete="email" maxlength="150" data-validar="correo" required />
    </div>

    <div class="campo">
      <label for="password" class="campo-obligatorio">Contraseña</label>
      <input type="password" id="password" name="password" autocomplete="new-password" minlength="8" data-validar="password" required />
      <span class="ayuda">Mínimo 8 caracteres.</span>
    </div>

    <div class="campo">
      <label for="confirmar" class="campo-obligatorio">Confirmar contraseña</label>
      <input type="password" id="confirmar" name="confirmar" autocomplete="new-password" minlength="8" data-validar="confirmar" required />
    </div>

    <button class="btn btn-principal" type="submit">Crear cuenta</button>

    <p class="acceso-pie">¿Ya tienes cuenta? <a href="login-cliente.php">Inicia sesión</a>.</p>
  </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
