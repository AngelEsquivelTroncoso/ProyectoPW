/**
 * Gráfico de cupos del panel de administración (Chart.js).
 *
 * Lee los datos que imprime dashboard.php en un
 * <script type="application/json" id="datos-cupos"> y dibuja una barra
 * horizontal por producto. Los productos con cinco cupos o menos se pintan
 * con el color de alerta.
 */
(function () {
  'use strict';

  const lienzo = document.getElementById('grafico-cupos');
  const fuente = document.getElementById('datos-cupos');

  // Sin canvas, sin datos o sin Chart.js no hay nada que hacer.
  if (!lienzo || !fuente || typeof window.Chart !== 'function') {
    return;
  }

  let datos;

  try {
    datos = JSON.parse(fuente.textContent);
  } catch (error) {
    return;
  }

  if (!datos || !Array.isArray(datos.etiquetas) || !Array.isArray(datos.valores)) {
    return;
  }

  if (datos.etiquetas.length === 0) {
    return;
  }

  const estilo = getComputedStyle(document.documentElement);
  const colorTexto =
    estilo.getPropertyValue('--color-texto-suave').trim() || '#9aa7c7';
  const colorAlerta = estilo.getPropertyValue('--color-error').trim() || '#ff8b8b';
  const colorPrincipal = estilo.getPropertyValue('--color-cyan').trim() || '#22d3ee';
  const colorRejilla = 'rgba(255, 255, 255, 0.08)';

  const UMBRAL_ALERTA = 5;
  const sinMovimiento = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  const colores = datos.valores.map((valor) =>
    valor <= UMBRAL_ALERTA ? colorAlerta : colorPrincipal
  );

  new window.Chart(lienzo, {
    type: 'bar',
    data: {
      labels: datos.etiquetas,
      datasets: [
        {
          label: 'Cupos disponibles',
          data: datos.valores,
          backgroundColor: colores,
          borderRadius: 6,
          borderSkipped: false,
          maxBarThickness: 26,
        },
      ],
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      animation: sinMovimiento ? false : undefined,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: (contexto) => {
              const valor = contexto.parsed.x;

              return valor === 1 ? '1 cupo' : valor + ' cupos';
            },
          },
        },
      },
      scales: {
        x: {
          beginAtZero: true,
          border: { display: false },
          grid: { color: colorRejilla },
          ticks: { color: colorTexto, precision: 0 },
        },
        y: {
          border: { display: false },
          grid: { display: false },
          ticks: {
            color: colorTexto,
            font: { size: 11 },
            callback: function (indice) {
              const etiqueta = String(this.getLabelForValue(indice));
              // El límite crece con el ancho del gráfico: en móvil se recorta
              // para no comerse el área de las barras.
              const limite = Math.max(16, Math.floor(this.chart.width / 22));

              return etiqueta.length > limite
                ? etiqueta.slice(0, limite - 1) + '…'
                : etiqueta;
            },
          },
        },
      },
    },
  });
})();
