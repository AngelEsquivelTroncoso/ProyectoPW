<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$titulo        = 'Memoria | Congreso Internacional de Ingeniería en Sistemas e IoT';
$descripcion   = 'Repositorio de documentos PDF de ediciones anteriores.';
$pagina_activa = 'memoria';
$pagina_id     = 'pagina-memoria';
$scripts_pie   = '';

include __DIR__ . '/includes/header.php';
?>
  <section class="cabecera-pagina">
    <div class="contenedor">
      <h1>Memoria</h1>
      <p>Archivo de ediciones anteriores del congreso.</p>
    </div>
  </section>

  <section class="seccion">
    <div class="contenedor">
      <!-- FILTROS (solo diseño) -->
      <form
        class="barra-filtros"
        id="filtros-memoria"
        aria-label="Filtros de la memoria"
      >
        <div class="campo-filtro">
          <label for="filtro-edicion">Edición</label>
          <select id="filtro-edicion" name="edicion">
            <option value="">Todas las ediciones</option>
            <option value="2025">2025</option>
            <option value="2024">2024</option>
            <option value="2023">2023</option>
          </select>
        </div>
        <div class="campo-filtro">
          <label for="filtro-categoria">Categoría</label>
          <select id="filtro-categoria" name="categoria">
            <option value="">Todas las categorías</option>
            <option value="actas">Actas</option>
            <option value="articulo">Artículos</option>
            <option value="taller">Guías de taller</option>
          </select>
        </div>
        <button
          class="btn btn-contorno"
          type="reset"
          id="btn-limpiar-memoria"
        >
          Limpiar filtros
        </button>
      </form>

      <ul class="rejilla-documentos" id="lista-documentos">
        <li
          class="tarjeta documento"
          data-edicion="2025"
          data-categoria="actas"
        >
          <div class="documento-icono" aria-hidden="true">PDF</div>
          <div>
            <h3 class="documento-nombre">Actas del Congreso 2025</h3>
            <dl class="documento-datos">
              <dt>Autor:</dt>
              <dd class="documento-autor">Comité científico</dd>
              <dt>Año:</dt>
              <dd class="documento-anio">2025</dd>
            </dl>
          </div>
          <a
            class="btn btn-secundario"
            href="memoria/actas-2025.pdf"
            aria-label="Ver PDF de Actas del Congreso 2025"
            >Ver PDF</a
          >
        </li>
        <li
          class="tarjeta documento"
          data-edicion="2025"
          data-categoria="articulo"
        >
          <div class="documento-icono" aria-hidden="true">PDF</div>
          <div>
            <h3 class="documento-nombre">
              Detección de anomalías en redes de sensores
            </h3>
            <dl class="documento-datos">
              <dt>Autor:</dt>
              <dd class="documento-autor">R. Navarro y T. Ibarra</dd>
              <dt>Año:</dt>
              <dd class="documento-anio">2025</dd>
            </dl>
          </div>
          <a
            class="btn btn-secundario"
            href="memoria/deteccion-anomalias-sensores.pdf"
            aria-label="Ver PDF de Detección de anomalías en redes de sensores"
            >Ver PDF</a
          >
        </li>
        <li
          class="tarjeta documento"
          data-edicion="2024"
          data-categoria="articulo"
        >
          <div class="documento-icono" aria-hidden="true">PDF</div>
          <div>
            <h3 class="documento-nombre">
              Arquitecturas de borde para ciudades inteligentes
            </h3>
            <dl class="documento-datos">
              <dt>Autor:</dt>
              <dd class="documento-autor">C. Fuentes</dd>
              <dt>Año:</dt>
              <dd class="documento-anio">2024</dd>
            </dl>
          </div>
          <a
            class="btn btn-secundario"
            href="memoria/arquitecturas-borde-ciudades.pdf"
            aria-label="Ver PDF de Arquitecturas de borde para ciudades inteligentes"
            >Ver PDF</a
          >
        </li>
        <li
          class="tarjeta documento"
          data-edicion="2024"
          data-categoria="taller"
        >
          <div class="documento-icono" aria-hidden="true">PDF</div>
          <div>
            <h3 class="documento-nombre">
              Guía del taller: Introducción a Arduino y MQTT
            </h3>
            <dl class="documento-datos">
              <dt>Autor:</dt>
              <dd class="documento-autor">E. Lozano</dd>
              <dt>Año:</dt>
              <dd class="documento-anio">2024</dd>
            </dl>
          </div>
          <a
            class="btn btn-secundario"
            href="memoria/guia-arduino-mqtt.pdf"
            aria-label="Ver PDF de Guía del taller: Introducción a Arduino y MQTT"
            >Ver PDF</a
          >
        </li>
        <li
          class="tarjeta documento"
          data-edicion="2023"
          data-categoria="actas"
        >
          <div class="documento-icono" aria-hidden="true">PDF</div>
          <div>
            <h3 class="documento-nombre">Actas del Congreso 2023</h3>
            <dl class="documento-datos">
              <dt>Autor:</dt>
              <dd class="documento-autor">Comité científico</dd>
              <dt>Año:</dt>
              <dd class="documento-anio">2023</dd>
            </dl>
          </div>
          <a
            class="btn btn-secundario"
            href="memoria/actas-2023.pdf"
            aria-label="Ver PDF de Actas del Congreso 2023"
            >Ver PDF</a
          >
        </li>
        <li
          class="tarjeta documento"
          data-edicion="2023"
          data-categoria="articulo"
        >
          <div class="documento-icono" aria-hidden="true">PDF</div>
          <div>
            <h3 class="documento-nombre">
              Blockchain para trazabilidad de cadenas de frío
            </h3>
            <dl class="documento-datos">
              <dt>Autor:</dt>
              <dd class="documento-autor">M. Aguilar y J. Prieto</dd>
              <dt>Año:</dt>
              <dd class="documento-anio">2023</dd>
            </dl>
          </div>
          <a
            class="btn btn-secundario"
            href="memoria/blockchain-cadena-frio.pdf"
            aria-label="Ver PDF de Blockchain para trazabilidad de cadenas de frío"
            >Ver PDF</a
          >
        </li>
      </ul>
    </div>
  </section>

<?php include __DIR__ . '/includes/footer.php'; ?>
