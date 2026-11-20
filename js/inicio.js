/**
 * Carrusel del hero (index.php).
 *
 * Usa jQuery + Slick, servidas localmente desde assets/vendor/. Si alguna de
 * las dos no cargó, el módulo no hace nada y la primera diapositiva queda
 * visible: la portada se lee igual sin JavaScript.
 */
(function () {
  'use strict';

  function iniciar() {
    var $ = window.jQuery;

    // Sin jQuery, sin Slick o sin el contenedor, no hay nada que inicializar.
    if (!$ || !$.fn || !$.fn.slick) {
      return;
    }

    var $slider = $('#hero-slider');

    if (!$slider.length || $slider.hasClass('slick-initialized')) {
      return;
    }

    var reducido =
      window.matchMedia &&
      window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    $slider.slick({
      autoplay: !reducido,
      autoplaySpeed: 7000,
      // Con movimiento reducido el cambio es instantáneo, sin desplazamiento.
      speed: reducido ? 0 : 550,
      arrows: true,
      dots: true,
      infinite: true,
      pauseOnHover: true,
      pauseOnFocus: true,
      swipeToSlide: true,
      prevArrow:
        '<button type="button" class="hero-flecha hero-flecha--anterior" aria-label="Diapositiva anterior"><span aria-hidden="true">\u2190</span></button>',
      nextArrow:
        '<button type="button" class="hero-flecha hero-flecha--siguiente" aria-label="Diapositiva siguiente"><span aria-hidden="true">\u2192</span></button>',
      responsive: [{ breakpoint: 820, settings: { arrows: false } }],
    });

    if (reducido) {
      return;
    }

    // WCAG 2.2.2: el avance automático se puede detener sin usar el ratón.
    var $pausa = $('<button type="button" class="hero-pausa" aria-pressed="false">Pausar</button>');

    $pausa.on('click', function () {
      var pausado = $pausa.attr('aria-pressed') === 'true';

      if (pausado) {
        $slider.slick('slickPlay');
      } else {
        $slider.slick('slickPause');
      }

      $pausa.attr('aria-pressed', String(!pausado));
      $pausa.text(pausado ? 'Pausar' : 'Reanudar');
    });

    $slider.append($pausa);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', iniciar);
  } else {
    iniciar();
  }
})();
