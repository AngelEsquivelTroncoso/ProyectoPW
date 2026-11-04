<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$titulo        = 'Detalle de ponencia | Congreso Internacional de Ingeniería en Sistemas e IoT';
$descripcion   = 'Detalle de una ponencia del congreso.';
$pagina_activa = 'ponencias';
$pagina_id     = 'pagina-ponencia-detalle';
$scripts_pie   = '<script src="js/ponencia-detalle.js" defer></script>';

include __DIR__ . '/includes/header.php';
?>
  <section class="seccion">
    <div class="contenedor">
      <p class="migas"><a href="ponencias.php">Ponencias</a> / <span id="miga-detalle">Detalle</span></p>

      <!-- Vista de detalle de ejemplo (después se llenará con datos según el id de la URL) -->
      <article
        class="detalle-ponencia"
        id="detalle-ponencia"
        data-id="p-001"
      >
        <div class="tarjeta detalle-principal">
          <span class="badge" id="detalle-area-badge">Seguridad y redes</span>
          <h1 id="detalle-titulo">Redes LoRaWAN para riego inteligente</h1>
          <h2>Resumen</h2>
          <p id="detalle-resumen">
            Se presenta el diseño de una red de sensores de humedad de suelo
            basada en LoRaWAN para parcelas de cultivo. El sistema envía
            lecturas cada 15 minutos y activa válvulas de riego solo cuando
            la humedad cae por debajo de un umbral configurable.
          </p>
          <p id="detalle-resumen-2">
            En seis meses de pruebas, el consumo de agua disminuyó frente al
            riego programado por horario. Se discuten las decisiones de
            arquitectura, el consumo energético de los nodos y los retos de
            cobertura.
          </p>
          <h2>Conclusiones</h2>
          <p id="detalle-conclusiones">
            La combinación de sensores económicos y comunicación de largo
            alcance es viable para pequeños productores. Como trabajo futuro
            se plantea integrar predicción de lluvia.
          </p>
          <a class="btn btn-contorno" href="ponencias.php"
            >Volver al listado</a
          >
        </div>

        <aside class="tarjeta" aria-label="Datos de la sesión">
          <dl class="detalle-meta">
            <div>
              <dt>Ponente</dt>
              <dd id="detalle-ponente">Dra. Mariana Ortega</dd>
            </div>
            <div>
              <dt>Institución</dt>
              <dd id="detalle-institucion">Universidad Politécnica de Occidente</dd>
            </div>
            <div>
              <dt>Fecha y hora</dt>
              <dd id="detalle-fecha">Jueves 12 de noviembre, 10:30</dd>
            </div>
            <div>
              <dt>Sala</dt>
              <dd id="detalle-sala">Sala 1, Edificio B</dd>
            </div>
            <div>
              <dt>Área temática</dt>
              <dd id="detalle-area">Seguridad y redes</dd>
            </div>
          </dl>
        </aside>
      </article>
    </div>
  </section>

<?php include __DIR__ . '/includes/footer.php'; ?>
