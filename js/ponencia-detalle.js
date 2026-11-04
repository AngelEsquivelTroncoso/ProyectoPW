/**
 * Detalle de ponencia.
 *
 * Toma el id de la URL (?id=p-001) y rellena la página con los datos
 * correspondientes. Si el id no existe, muestra un aviso y oculta los metadatos.
 */
(function () {
  'use strict';

  const PONENCIAS = {
    'p-001': {
      area: 'Seguridad y redes',
      titulo: 'Redes LoRaWAN para riego inteligente',
      resumen1:
        'Se presenta el diseño de una red de sensores de humedad de suelo basada en LoRaWAN para parcelas de cultivo. El sistema envía lecturas cada 15 minutos y activa válvulas de riego solo cuando la humedad cae por debajo de un umbral configurable.',
      resumen2:
        'En seis meses de pruebas, el consumo de agua disminuyó frente al riego programado por horario. Se discuten las decisiones de arquitectura, el consumo energético de los nodos y los retos de cobertura.',
      conclusiones:
        'La combinación de sensores económicos y comunicación de largo alcance es viable para pequeños productores. Como trabajo futuro se plantea integrar predicción de lluvia.',
      ponente: 'Dra. Mariana Ortega',
      institucion: 'Universidad Politécnica de Occidente',
      fecha: 'Jueves 12 de noviembre, 10:30',
      sala: 'Sala 1, Edificio B',
    },
    'p-002': {
      area: 'Ciberseguridad',
      titulo: 'Arranque seguro en microcontroladores de bajo costo',
      resumen1:
        'Se propone una cadena de arranque seguro para microcontroladores económicos basada en una raíz de confianza inmutable y firma de firmware con claves Ed25519. El proceso verifica cada actualización antes de ejecutarla y bloquea imágenes no firmadas.',
      resumen2:
        'Las pruebas sobre tres familias de microcontroladores muestran un costo de verificación inferior a 200 ms y un impacto mínimo en el tiempo de arranque. Se analiza también la gestión y rotación de claves.',
      conclusiones:
        'La seguridad desde el arranque es alcanzable en hardware de bajo costo sin sacrificar tiempos de operación. Como trabajo futuro se plantea integrar atestación remota.',
      ponente: 'M.C. Luis Herrera',
      institucion: 'Instituto Tecnológico Regional',
      fecha: 'Jueves 12 de noviembre, 16:30',
      sala: 'Sala 2, Edificio B',
    },
    'p-003': {
      area: 'Inteligencia artificial',
      titulo: 'Clasificación de vibraciones con TinyML',
      resumen1:
        'Se entrena un modelo convolucional compacto para clasificar patrones de vibración en motores eléctricos directamente en un microcontrolador. La inferencia ocurre en el sensor, sin enviar audio ni series crudas a la nube.',
      resumen2:
        'El modelo ocupa menos de 90 KB y alcanza una exactitud del 94 % en un conjunto de prueba con cinco clases de falla. Se comparan técnicas de cuantización y poda.',
      conclusiones:
        'TinyML permite mantenimiento predictivo con bajo ancho de banda y mayor privacidad. Falta validar el desempeño con motores de mayor potencia.',
      ponente: 'Ing. Paola Segura',
      institucion: 'Centro de Investigación en Cómputo',
      fecha: 'Viernes 13 de noviembre, 10:30',
      sala: 'Sala 1, Edificio B',
    },
    'p-004': {
      area: 'Industria 4.0',
      titulo: 'Gemelo digital de una línea de empaque',
      resumen1:
        'Se construye un gemelo digital de una línea de empaque alimentado por telemetría de sensores industriales. El modelo se sincroniza en tiempo casi real y permite simular cambios de turno, velocidad y mantenimiento.',
      resumen2:
        'La comparación con la operación real identifica cuellos de botella que no eran visibles en los reportes históricos. Se detalla la arquitectura de integración con OPC UA y MQTT.',
      conclusiones:
        'El gemelo digital reduce el tiempo de diagnóstico de fallas y mejora la planeación de capacidad. El siguiente paso es cerrar el lazo con control automático.',
      ponente: 'Dr. Andrés Villalobos',
      institucion: 'Universidad Nacional de Ingeniería',
      fecha: 'Viernes 13 de noviembre, 16:30',
      sala: 'Sala 2, Edificio B',
    },
    'p-005': {
      area: 'Protocolos IoT',
      titulo: 'MQTT frente a CoAP: latencia y consumo energético',
      resumen1:
        'Se comparan MQTT y CoAP en escenarios de red inestable con nodos alimentados por batería. Se miden latencia, retransmisiones y energía por mensaje bajo distintas tasas de pérdida.',
      resumen2:
        'MQTT ofrece menor latencia con conexión persistente, mientras CoAP consume menos energía en tráfico esporádico. Los resultados dependen fuertemente del patrón de comunicación.',
      conclusiones:
        'No existe un protocolo superior en todos los casos: la elección debe partir del patrón de tráfico y del presupuesto energético. Se propone una guía de selección.',
      ponente: 'M.C. Sofía Beltrán',
      institucion: 'Universidad Autónoma Tecnológica',
      fecha: 'Sábado 14 de noviembre, 10:30',
      sala: 'Sala 1, Edificio B',
    },
    'p-006': {
      area: 'Ingeniería de software',
      titulo: 'Pruebas automatizadas para sistemas ciberfísicos',
      resumen1:
        'Se presenta una estrategia de integración continua para sistemas ciberfísicos que combina pruebas unitarias, simulación de hardware y pruebas de regresión sobre bancos físicos.',
      resumen2:
        'La incorporación de hardware simulado permitió ejecutar la suite completa en cada cambio sin ocupar los laboratorios. Los defectos detectados antes del despliegue aumentaron de forma medible.',
      conclusiones:
        'Automatizar con hardware simulado acelera la detección de errores sin sustituir las pruebas físicas. Se recomienda mantener una pirámide de pruebas con ambos niveles.',
      ponente: 'Ing. Diego Camacho',
      institucion: 'Laboratorio de Software Industrial',
      fecha: 'Sábado 14 de noviembre, 16:30',
      sala: 'Sala 2, Edificio B',
    },
  };

  const articulo = document.getElementById('detalle-ponencia');

  if (!articulo) {
    return;
  }

  const principal = articulo.querySelector('.detalle-principal');
  const aside = articulo.querySelector('aside');
  const params = new URLSearchParams(location.search);
  const id = (params.get('id') || 'p-001').toLowerCase();
  const datos = PONENCIAS[id];

  if (!datos) {
    if (principal) {
      principal.innerHTML =
        '<span class="badge">Ponencia</span>' +
        '<h1>Ponencia no encontrada</h1>' +
        '<p>No encontramos la ponencia solicitada. Vuelve al listado para elegir otra.</p>' +
        '<a class="btn btn-contorno" href="ponencias.php">Volver al listado</a>';
    }

    if (aside) {
      aside.hidden = true;
    }

    document.title = 'Ponencia no encontrada | Congreso SysIoT 2026';

    return;
  }

  articulo.dataset.id = id;
  document.title = datos.titulo + ' | Congreso SysIoT 2026';

  const asignar = (elementoId, valor) => {
    const elemento = document.getElementById(elementoId);

    if (elemento) {
      elemento.textContent = valor;
    }
  };

  asignar('detalle-area-badge', datos.area);
  asignar('detalle-titulo', datos.titulo);
  asignar('detalle-resumen', datos.resumen1);
  asignar('detalle-resumen-2', datos.resumen2);
  asignar('detalle-conclusiones', datos.conclusiones);
  asignar('detalle-ponente', datos.ponente);
  asignar('detalle-institucion', datos.institucion);
  asignar('detalle-fecha', datos.fecha);
  asignar('detalle-sala', datos.sala);
  asignar('detalle-area', datos.area);
  asignar('miga-detalle', datos.area);

  const descripcion = document.querySelector('meta[name="description"]');

  if (descripcion) {
    descripcion.setAttribute('content', datos.resumen1);
  }
})();
