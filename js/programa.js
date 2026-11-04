/**
 * Filtros de la agenda del programa.
 *
 * Lee los tres <select> y oculta las sesiones que no coinciden.
 * También oculta los días sin sesiones visibles y refleja el filtro en la URL.
 */
(function () {
  'use strict';

  const form = document.getElementById('filtros-programa');
  const agenda = document.getElementById('agenda');

  if (!form || !agenda) {
    return;
  }

  const filtros = {
    dia: document.getElementById('filtro-dia'),
    tipo: document.getElementById('filtro-tipo'),
    sala: document.getElementById('filtro-sala'),
  };

  const resumen = document.getElementById('resumen-filtros');
  const sesiones = Array.from(agenda.querySelectorAll('.sesion'));
  const dias = Array.from(agenda.querySelectorAll('.dia-agenda'));

  const aviso = document.createElement('p');
  aviso.className = 'sin-resultados';
  aviso.id = 'sin-resultados';
  aviso.setAttribute('role', 'status');
  aviso.hidden = true;
  aviso.textContent = 'No hay sesiones con esos filtros. Prueba con otros criterios.';
  agenda.after(aviso);

  function estado() {
    return {
      dia: filtros.dia.value,
      tipo: filtros.tipo.value,
      sala: filtros.sala.value,
    };
  }

  function sincronizarUrl(actual) {
    const params = new URLSearchParams();

    if (actual.dia) params.set('dia', actual.dia);
    if (actual.tipo) params.set('tipo', actual.tipo);
    if (actual.sala) params.set('sala', actual.sala);

    const consulta = params.toString();

    try {
      history.replaceState(null, '', consulta ? '?' + consulta : location.pathname);
    } catch (error) {
      // En algunos contextos (file://) replaceState no está permitido.
    }
  }

  function aplicar(actualizarUrl) {
    const actual = estado();
    let visibles = 0;

    sesiones.forEach((sesion) => {
      const coincide =
        (!actual.dia || sesion.dataset.dia === actual.dia) &&
        (!actual.tipo || sesion.dataset.tipo === actual.tipo) &&
        (!actual.sala || sesion.dataset.sala === actual.sala);

      sesion.hidden = !coincide;

      if (coincide) {
        visibles += 1;
      }
    });

    dias.forEach((dia) => {
      dia.hidden = dia.querySelectorAll('.sesion:not([hidden])').length === 0;
    });

    aviso.hidden = visibles > 0;

    if (resumen) {
      resumen.textContent =
        visibles === 1
          ? '1 sesión coincide con los filtros.'
          : visibles + ' sesiones coinciden con los filtros.';
    }

    if (actualizarUrl) {
      sincronizarUrl(actual);
    }
  }

  Object.values(filtros).forEach((select) => {
    if (select) {
      select.addEventListener('change', () => aplicar(true));
    }
  });

  form.addEventListener('reset', () => {
    // Espera a que el navegador restablezca los valores.
    window.setTimeout(() => aplicar(true), 0);
  });

  const params = new URLSearchParams(location.search);

  ['dia', 'tipo', 'sala'].forEach((clave) => {
    const valor = params.get(clave);

    if (valor && filtros[clave]) {
      filtros[clave].value = valor;
    }
  });

  aplicar(false);
})();
