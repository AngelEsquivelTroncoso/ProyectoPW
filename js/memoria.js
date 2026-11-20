/**
 * Filtros de la memoria del congreso.
 *
 * Lee los dos <select> y oculta los documentos que no coinciden.
 * Refleja el filtro en la URL para poder compartir la búsqueda.
 */
(function () {
  'use strict';

  const form = document.getElementById('filtros-memoria');
  const lista = document.getElementById('lista-documentos');

  if (!form || !lista) {
    return;
  }

  const filtros = {
    edicion: document.getElementById('filtro-edicion'),
    categoria: document.getElementById('filtro-categoria'),
  };

  const resumen = document.getElementById('resumen-memoria');
  const documentos = Array.from(lista.querySelectorAll('.documento'));

  const aviso = document.createElement('p');
  aviso.className = 'sin-resultados';
  aviso.id = 'sin-resultados-memoria';
  aviso.setAttribute('role', 'status');
  aviso.hidden = true;
  aviso.textContent =
    'No hay documentos con esos filtros. Prueba con otros criterios.';
  lista.after(aviso);

  function estado() {
    return {
      edicion: filtros.edicion.value,
      categoria: filtros.categoria.value,
    };
  }

  function sincronizarUrl(actual) {
    const params = new URLSearchParams();

    if (actual.edicion) params.set('edicion', actual.edicion);
    if (actual.categoria) params.set('categoria', actual.categoria);

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

    documentos.forEach((documento) => {
      const coincide =
        (!actual.edicion || documento.dataset.edicion === actual.edicion) &&
        (!actual.categoria || documento.dataset.categoria === actual.categoria);

      documento.hidden = !coincide;

      if (coincide) {
        visibles += 1;
      }
    });

    aviso.hidden = visibles > 0;

    if (resumen) {
      resumen.textContent =
        visibles === 1
          ? '1 documento coincide con los filtros.'
          : visibles + ' documentos coinciden con los filtros.';
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

  ['edicion', 'categoria'].forEach((clave) => {
    const valor = params.get(clave);

    if (valor && filtros[clave]) {
      filtros[clave].value = valor;
    }
  });

  aplicar(false);
})();
