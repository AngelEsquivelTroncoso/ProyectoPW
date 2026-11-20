/**
 * Validación del lado del cliente.
 *
 * Los campos se marcan con `data-validar="<regla>"` y este módulo aplica la
 * regla correspondiente. Las reglas replican las validaciones que ya existen
 * en el servidor (`registro.php`, `login-cliente.php`, `admin/login.php`,
 * `admin/productos.php` y `carrito.php`): el servidor sigue siendo la
 * autoridad; esto solo mejora la experiencia antes de enviar.
 *
 * Los formularios llevan `novalidate` a propósito, para que los globos
 * nativos del navegador no compitan con los mensajes de este módulo.
 */
(function () {
  'use strict';

  const REGLAS = {
    nombre: {
      requerido: true,
      patron: /^[\p{L}\p{M}\s'.-]{3,100}$/u,
      vacio: 'Escribe tu nombre completo.',
      mensaje:
        'El nombre debe tener entre 3 y 100 caracteres (solo letras y espacios).',
    },
    correo: {
      requerido: true,
      patron: /^[\w.+-]+@[\w-]+(\.[\w-]{2,})+$/,
      maxLongitud: 150,
      vacio: 'Escribe tu correo electrónico.',
      mensaje: 'El correo electrónico no es válido (ejemplo: nombre@dominio.com).',
    },
    password: {
      requerido: true,
      minLongitud: 8,
      vacio: 'Escribe una contraseña.',
      mensaje: 'La contraseña debe tener al menos 8 caracteres.',
    },
    // En el acceso no se aplica la política de longitud: aquí solo se validan
    // las credenciales, y el servidor es quien decide si son correctas.
    'clave-acceso': {
      requerido: true,
      vacio: 'Escribe tu contraseña.',
    },
    confirmar: {
      requerido: true,
      igualA: 'password',
      vacio: 'Confirma tu contraseña.',
      mensaje: 'Las contraseñas no coinciden.',
    },
    descripcion: {
      requerido: false,
      maxLongitud: 2000,
      mensaje: 'La descripción no puede superar los 2000 caracteres.',
    },
    precio: {
      requerido: true,
      patron: /^\d+(\.\d{1,2})?$/,
      mayorQue: 0,
      vacio: 'Escribe el precio.',
      mensaje: 'El precio debe ser mayor que cero, con hasta dos decimales.',
    },
    stock: {
      requerido: true,
      patron: /^\d+$/,
      vacio: 'Escribe los cupos.',
      mensaje: 'Los cupos deben ser un número entero igual o mayor que cero.',
    },
    cantidad: {
      requerido: true,
      patron: /^\d+$/,
      rango: true,
      vacio: 'Escribe una cantidad.',
      mensaje: 'Escribe una cantidad en números enteros.',
    },
  };

  function reglaDe(campo) {
    return REGLAS[campo.dataset.validar] || null;
  }

  function identificador(campo) {
    return campo.id || campo.name || 'campo';
  }

  function campoPorRegla(form, nombre) {
    return form.querySelector('[data-validar="' + nombre + '"]');
  }

  /**
   * Devuelve el mensaje de error del campo, o null si es válido.
   */
  function calcularError(campo) {
    const regla = reglaDe(campo);

    if (regla === null) {
      return null;
    }

    const valor = campo.value.trim();

    if (valor === '') {
      return regla.requerido ? regla.vacio : null;
    }

    if (regla.maxLongitud !== undefined && valor.length > regla.maxLongitud) {
      return regla.mensaje;
    }

    if (regla.minLongitud !== undefined && valor.length < regla.minLongitud) {
      return regla.mensaje;
    }

    if (regla.patron !== undefined && !regla.patron.test(valor)) {
      return regla.mensaje;
    }

    if (regla.mayorQue !== undefined && !(Number(valor) > regla.mayorQue)) {
      return regla.mensaje;
    }

    // Comparaciones contra otro campo del mismo formulario (confirmar).
    if (regla.igualA !== undefined && campo.form !== null) {
      const otro = campoPorRegla(campo.form, regla.igualA);

      if (otro !== null && otro.value !== campo.value) {
        return regla.mensaje;
      }
    }

    // La cantidad de un producto no puede superar los cupos que ya trae el
    // atributo max generado por el servidor.
    if (regla.rango === true) {
      const minimo = campo.min === '' ? 0 : Number(campo.min);
      const maximo = campo.max === '' ? null : Number(campo.max);
      const numero = Number(valor);

      if (!Number.isInteger(numero) || numero < minimo) {
        return regla.mensaje;
      }

      if (maximo !== null && numero > maximo) {
        return 'Solo hay ' + maximo + ' cupos disponibles.';
      }
    }

    return null;
  }

  /**
   * Crea (una sola vez) el párrafo de error asociado a un campo.
   */
  function nodoError(campo) {
    const id = 'error-' + identificador(campo);
    let nodo = document.getElementById(id);

    if (nodo === null) {
      nodo = document.createElement('p');
      nodo.id = id;
      nodo.className = 'campo-error';
      nodo.setAttribute('role', 'alert');
      nodo.hidden = true;
      campo.insertAdjacentElement('afterend', nodo);
    }

    return nodo;
  }

  function describir(campo, idError) {
    if (campo.dataset.describirOriginal === undefined) {
      campo.dataset.describirOriginal = campo.getAttribute('aria-describedby') || '';
    }

    const previos = campo.dataset.describirOriginal;

    campo.setAttribute(
      'aria-describedby',
      previos === '' ? idError : previos + ' ' + idError
    );
  }

  function marcarError(campo, mensaje) {
    const nodo = nodoError(campo);

    nodo.textContent = mensaje;
    nodo.hidden = false;
    campo.setAttribute('aria-invalid', 'true');
    describir(campo, nodo.id);
  }

  function limpiarError(campo) {
    const nodo = document.getElementById('error-' + identificador(campo));

    if (nodo !== null) {
      nodo.hidden = true;
      nodo.textContent = '';
    }

    campo.removeAttribute('aria-invalid');

    const original = campo.dataset.describirOriginal;

    if (original === undefined) {
      return;
    }

    if (original === '') {
      campo.removeAttribute('aria-describedby');
    } else {
      campo.setAttribute('aria-describedby', original);
    }
  }

  function validarCampo(campo) {
    const mensaje = calcularError(campo);

    if (mensaje === null) {
      limpiarError(campo);
      return true;
    }

    marcarError(campo, mensaje);
    return false;
  }

  function validarFormulario(form) {
    let primeroInvalido = null;

    Array.from(form.querySelectorAll('[data-validar]')).forEach((campo) => {
      const valido = validarCampo(campo);

      if (!valido && primeroInvalido === null) {
        primeroInvalido = campo;
      }
    });

    if (primeroInvalido !== null) {
      primeroInvalido.focus();
      return false;
    }

    return true;
  }

  /**
   * Si cambia la contraseña, vuelve a comprobar la confirmación.
   */
  function revalidarDependientes(campo) {
    if (campo.form === null) {
      return;
    }

    const nombre = campo.dataset.validar;

    Array.from(campo.form.querySelectorAll('[data-validar]')).forEach((otro) => {
      const regla = reglaDe(otro);

      if (
        regla !== null &&
        regla.igualA === nombre &&
        otro.getAttribute('aria-invalid') === 'true'
      ) {
        validarCampo(otro);
      }
    });
  }

  function prepararFormulario(form) {
    const campos = Array.from(form.querySelectorAll('[data-validar]'));

    if (campos.length === 0) {
      return;
    }

    form.addEventListener('submit', (evento) => {
      if (!validarFormulario(form)) {
        evento.preventDefault();
      }
    });

    campos.forEach((campo) => {
      campo.addEventListener('blur', () => validarCampo(campo));

      campo.addEventListener('input', () => {
        if (campo.getAttribute('aria-invalid') === 'true') {
          validarCampo(campo);
        }

        revalidarDependientes(campo);
      });
    });

    form.addEventListener('reset', () => {
      // Espera a que el navegador restablezca los valores.
      window.setTimeout(() => campos.forEach(limpiarError), 0);
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form').forEach(prepararFormulario);
  });
})();
