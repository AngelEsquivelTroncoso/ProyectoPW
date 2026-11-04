<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$titulo        = 'Ponencias | Congreso Internacional de Ingeniería en Sistemas e IoT';
$descripcion   = 'Listado de ponencias aceptadas del congreso.';
$pagina_activa = 'ponencias';
$pagina_id     = 'pagina-ponencias';
$scripts_pie   = '';

include __DIR__ . '/includes/header.php';
?>
  <section class="cabecera-pagina">
    <div class="contenedor">
      <h1>Ponencias</h1>
      <p>Investigación y proyectos seleccionados para esta edición.</p>
    </div>
  </section>

  <section class="seccion">
    <div class="contenedor">
      <div class="rejilla-ponencias" id="lista-ponencias">
        <article
          class="tarjeta ponencia-card"
          id="p-001"
          data-area="Seguridad y redes"
        >
          <span class="badge">Seguridad y redes</span>
          <h3>Redes LoRaWAN para riego inteligente</h3>
          <p class="ponencia-autor">
            Dra. Mariana Ortega · Universidad Politécnica de Occidente
          </p>
          <p class="ponencia-resumen">
            Diseño y validación de una red de sensores de humedad que redujo
            el consumo de agua en parcelas de prueba.
          </p>
          <a
            class="btn btn-secundario"
            href="ponencia-detalle.php?id=p-001"
            >Ver detalle</a
          >
        </article>
        <article
          class="tarjeta ponencia-card"
          id="p-002"
          data-area="Ciberseguridad"
        >
          <span class="badge">Ciberseguridad</span>
          <h3>Arranque seguro en microcontroladores de bajo costo</h3>
          <p class="ponencia-autor">
            M.C. Luis Herrera · Instituto Tecnológico Regional
          </p>
          <p class="ponencia-resumen">
            Propuesta de cadena de confianza para actualizar firmware sin
            comprometer la integridad del dispositivo.
          </p>
          <a
            class="btn btn-secundario"
            href="ponencia-detalle.php?id=p-002"
            >Ver detalle</a
          >
        </article>
        <article
          class="tarjeta ponencia-card"
          id="p-003"
          data-area="Inteligencia artificial"
        >
          <span class="badge">Inteligencia artificial</span>
          <h3>Clasificación de vibraciones con TinyML</h3>
          <p class="ponencia-autor">
            Ing. Paola Segura · Centro de Investigación en Cómputo
          </p>
          <p class="ponencia-resumen">
            Modelo compacto que detecta fallas en motores directamente en el
            sensor, sin depender de la nube.
          </p>
          <a
            class="btn btn-secundario"
            href="ponencia-detalle.php?id=p-003"
            >Ver detalle</a
          >
        </article>
        <article
          class="tarjeta ponencia-card"
          id="p-004"
          data-area="Industria 4.0"
        >
          <span class="badge">Industria 4.0</span>
          <h3>Gemelo digital de una línea de empaque</h3>
          <p class="ponencia-autor">
            Dr. Andrés Villalobos · Universidad Nacional de Ingeniería
          </p>
          <p class="ponencia-resumen">
            Simulación sincronizada con telemetría real para anticipar
            cuellos de botella en producción.
          </p>
          <a
            class="btn btn-secundario"
            href="ponencia-detalle.php?id=p-004"
            >Ver detalle</a
          >
        </article>
        <article
          class="tarjeta ponencia-card"
          id="p-005"
          data-area="Protocolos IoT"
        >
          <span class="badge">Protocolos IoT</span>
          <h3>MQTT frente a CoAP: latencia y consumo energético</h3>
          <p class="ponencia-autor">
            M.C. Sofía Beltrán · Universidad Autónoma Tecnológica
          </p>
          <p class="ponencia-resumen">
            Medición experimental de ambos protocolos sobre redes inestables
            con nodos alimentados por batería.
          </p>
          <a
            class="btn btn-secundario"
            href="ponencia-detalle.php?id=p-005"
            >Ver detalle</a
          >
        </article>
        <article
          class="tarjeta ponencia-card"
          id="p-006"
          data-area="Ingeniería de software"
        >
          <span class="badge">Ingeniería de software</span>
          <h3>Pruebas automatizadas para sistemas ciberfísicos</h3>
          <p class="ponencia-autor">
            Ing. Diego Camacho · Laboratorio de Software Industrial
          </p>
          <p class="ponencia-resumen">
            Estrategia de integración continua con hardware simulado para
            reducir errores antes del despliegue.
          </p>
          <a
            class="btn btn-secundario"
            href="ponencia-detalle.php?id=p-006"
            >Ver detalle</a
          >
        </article>
      </div>
    </div>
  </section>

<?php include __DIR__ . '/includes/footer.php'; ?>
