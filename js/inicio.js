/**
 * Hero de la portada (index.php): carrusel con jQuery + Slick.
 *
 * Slick genera el carril de diapositivas y una lista de control
 * (`ul.slick-dots[role="tablist"]`). Aquí esa lista se convierte en el índice
 * del hero: cada control muestra el nombre de su diapositiva y el subrayado
 * del activo hace de barra de progreso del avance automático.
 *
 * Si jQuery o Slick no cargan, el módulo no hace nada y la primera diapositiva
 * sigue visible: la portada se lee igual sin JavaScript.
 */
(function () {
  'use strict';

  var SVG_PAUSA =
    '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false">' +
    '<rect x="7" y="5" width="3.4" height="14" rx="1.3" fill="currentColor"></rect>' +
    '<rect x="13.6" y="5" width="3.4" height="14" rx="1.3" fill="currentColor"></rect>' +
    '</svg>';

  var SVG_REANUDAR =
    '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false">' +
    '<path d="M8 5.4v13.2L19 12z" fill="currentColor"></path>' +
    '</svg>';

  var ETIQUETA_PAUSA = 'Pausar el avance automático';
  var ETIQUETA_REANUDAR = 'Reanudar el avance automático';

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

    // Los títulos se leen antes de inicializar: Slick se lleva las diapositivas
    // a su propio carril y agrega clones al final.
    var titulos = $slider
      .children('.hero-slide')
      .map(function () {
        return $(this).attr('data-titulo') || '';
      })
      .get();

    var reducido =
      window.matchMedia &&
      window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    $slider.slick({
      autoplay: !reducido,
      autoplaySpeed: 7000,
      // Con movimiento reducido el cambio es instantáneo, sin desplazamiento.
      speed: reducido ? 0 : 550,
      arrows: false,
      dots: true,
      infinite: true,
      // La pausa por hover y por foco la maneja este módulo, para que el riel
      // del índice se detenga exactamente cuando se detiene el temporizador.
      pauseOnHover: false,
      pauseOnFocus: false,
      swipeToSlide: true,
    });

    var $puntos = $slider.find('.slick-dots');

    // --- El índice: nombres en lugar de "1, 2, 3" -------------------------
    $puntos.children('li').each(function (indice) {
      var $boton = $(this).children('button');

      // Slick nombra los controles en inglés ("1 of 3"). Al quitar ese
      // aria-label, el nombre accesible pasa a ser el texto visible.
      // El estado de pestaña (aria-selected, tabindex) lo refresca el propio
      // Slick en cada cambio de diapositiva: no hay que duplicarlo aquí.
      $boton.removeAttr('aria-label');

      var numero = ('0' + (indice + 1)).slice(-2);

      $boton.empty().append(
        $('<span class="indice-numero" aria-hidden="true"></span>').text(numero),
        $('<span class="indice-texto"></span>').text(
          titulos[indice] || 'Diapositiva ' + (indice + 1)
        ),
        $('<span class="indice-riel" aria-hidden="true"></span>')
      );
    });

    // --- Pausa manual -----------------------------------------------------
    var manual = false;
    var porHover = false;
    var porFoco = false;
    var $pausa = $();

    function pausado() {
      return manual || porHover || porFoco;
    }

    function reiniciarRiel() {
      $slider.removeClass('hero-avanza');
      // Reflujo a propósito: obliga a reiniciar la animación CSS del riel.
      void $slider[0].offsetWidth;
      $slider.addClass('hero-avanza');
    }

    function sincronizar() {
      var detenido = pausado();
      var estabaDetenido = $slider.hasClass('hero-pausado');

      $slider.toggleClass('hero-pausado', detenido);

      if (detenido) {
        $slider.slick('slickPause');
      } else {
        $slider.slick('slickPlay');
      }

      // Slick reinicia su temporizador al reanudar: el riel arranca de cero
      // con él en vez de seguir donde se había congelado.
      if (estabaDetenido && !detenido) {
        reiniciarRiel();
      }

      if ($pausa.length) {
        $pausa
          .attr('aria-pressed', String(manual))
          .attr('aria-label', manual ? ETIQUETA_REANUDAR : ETIQUETA_PAUSA)
          .html(manual ? SVG_REANUDAR : SVG_PAUSA);
      }
    }

    if (!reducido) {
      $pausa = $(
        '<button type="button" class="hero-pausa" aria-pressed="false"></button>'
      )
        .attr('aria-label', ETIQUETA_PAUSA)
        .html(SVG_PAUSA);

      $pausa.on('click', function (evento) {
        // El clic no debe llegar al manejador delegado de Slick sobre el li:
        // lo interpretaría como "ir a la diapositiva 4".
        evento.preventDefault();
        evento.stopPropagation();
        manual = !manual;
        sincronizar();
      });

      // WCAG 2.2.2: el avance automático se detiene sin usar el ratón.
      $puntos.append(
        $('<li class="indice-pausa" role="presentation"></li>').append($pausa)
      );

      $slider
        .on('mouseenter', function () {
          porHover = true;
          sincronizar();
        })
        .on('mouseleave', function () {
          porHover = false;
          sincronizar();
        })
        .on('focusin', function () {
          porFoco = true;
          sincronizar();
        })
        .on('focusout', function (evento) {
          // focusout también salta entre los botones del índice: solo
          // consideramos que se fue cuando el foco sale del carrusel.
          if (!$.contains(this, evento.relatedTarget)) {
            porFoco = false;
            sincronizar();
          }
        });

      $slider.addClass('hero-avanza');
    }

    // --- Teclado: izquierda/derecha cambian de diapositiva -----------------
    var moverFoco = false;

    $slider.on('keydown', function (evento) {
      if (evento.key !== 'ArrowRight' && evento.key !== 'ArrowLeft') {
        return;
      }

      evento.preventDefault();
      moverFoco = $.contains($puntos[0], document.activeElement);

      if (evento.key === 'ArrowRight') {
        $slider.slick('slickNext');
      } else {
        $slider.slick('slickPrev');
      }

      if (!reducido) {
        reiniciarRiel();
      }
    });

    // Slick actualiza las clases recién al terminar la transición: el foco
    // se mueve ahí, no en el keydown.
    $slider.on('afterChange', function (evento, instancia, actual) {
      if (!moverFoco) {
        return;
      }

      moverFoco = false;

      var $destino = $puntos.children('li').eq(actual).children('button');

      if ($destino.length) {
        $destino.trigger('focus');
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', iniciar);
  } else {
    iniciar();
  }
})();
