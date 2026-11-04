<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/productos.php';

$titulo        = 'Inicio | Congreso Internacional de Ingeniería en Sistemas e IoT';
$descripcion   = 'Portal del Congreso Internacional de Ingeniería en Sistemas e IoT: fechas, sede, registro y pagos.';
$pagina_activa = 'inicio';
$pagina_id     = 'pagina-inicio';

$destacados = productos_activos(3);

include __DIR__ . '/includes/header.php';
?>

      <section class="hero" id="hero">
        <div class="contenedor hero-contenido">
          <h1>Congreso Internacional de Ingeniería en Sistemas e IoT</h1>
          <p class="hero-descripcion">
            Tres días para presentar investigación, compartir proyectos y
            conectar con quienes construyen la próxima generación de sistemas
            conectados.
          </p>

          <ul class="hero-datos" id="hero-datos">
            <li>
              <strong>12 al 14 de noviembre de 2026</strong><span>Fechas del evento</span>
            </li>
            <li>
              <strong>Centro de Convenciones Tecnológico</strong><span>Edificio A, Auditorio Magno</span>
            </li>
          </ul>

          <div class="hero-acciones">
            <a class="btn btn-principal" id="btn-registro-pago" href="catalogo.php">Registro y pago</a>
            <div class="metodo-pago">
              <span>Pago seguro con</span>
              <img
                class="logo-paypal"
                id="logo-paypal"
                src="https://upload.wikimedia.org/wikipedia/commons/b/b5/PayPal.svg"
                alt="Logotipo de PayPal"
                width="90"
                height="28"
              />
            </div>
          </div>
        </div>
      </section>

      <section class="seccion" id="inscripciones">
        <div class="contenedor">
          <div class="encabezado-seccion">
            <h2>Inscripciones y talleres</h2>
            <p>Elige tu modalidad de participación y asegura tu lugar.</p>
          </div>

          <?php if ($destacados === []): ?>
            <p class="nota-pago">Por el momento no hay inscripciones disponibles.</p>
          <?php else: ?>
            <div class="contenedor-precios">
              <?php foreach ($destacados as $producto): ?>
                <article class="tarjeta pricing-card">
                  <h3><?= e($producto['nombre']) ?></h3>
                  <p class="precio"><?= e(precio($producto['precio'])) ?></p>
                  <p><?= e($producto['descripcion']) ?></p>
                  <p class="nota-pago">
                    Cupos disponibles:
                    <strong><?= (int) $producto['stock'] ?></strong>
                  </p>
                </article>
              <?php endforeach; ?>
            </div>

            <p class="nota-pago">
              <a class="btn btn-secundario" href="catalogo.php">Ver catálogo completo</a>
            </p>
          <?php endif; ?>
        </div>
      </section>

      <section class="seccion" id="informacion-general">
        <div class="contenedor">
          <div class="encabezado-seccion">
            <h2>Qué encontrarás en el congreso</h2>
            <p>
              Un espacio para ponentes y asistentes del ámbito académico y
              profesional.
            </p>
          </div>
          <div class="rejilla-info">
            <article class="tarjeta">
              <h3>Para ponentes</h3>
              <p>
                Presenta tu investigación o proyecto ante un público
                especializado y publícalo en la memoria del congreso.
              </p>
            </article>
            <article class="tarjeta">
              <h3>Para participantes</h3>
              <p>
                Asiste a conferencias, talleres prácticos y sesiones de redes en
                modalidad presencial o virtual.
              </p>
            </article>
            <article class="tarjeta">
              <h3>Temas principales</h3>
              <p>
                Internet de las cosas, ciberseguridad, computación en la nube,
                inteligencia artificial en el borde y sistemas embebidos.
              </p>
            </article>
          </div>
        </div>
      </section>

<?php include __DIR__ . '/includes/footer.php'; ?>
