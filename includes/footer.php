    </main>

    <footer class="sitio-pie" id="sitio-pie">
      <div class="contenedor pie-contenido">
        <div>
          <p>
            <strong>Congreso Internacional de Ingeniería en Sistemas e IoT</strong>
          </p>
          <p>12 al 14 de noviembre de 2026 · Centro de Convenciones Tecnológico</p>
        </div>
        <div>
          <p>
            Contacto:
            <a href="mailto:contacto@congreso-sysiot.example">contacto@congreso-sysiot.example</a>
          </p>
          <ul class="pie-enlaces">
            <li><a href="<?= e($raiz ?? '') ?>catalogo.php">Catálogo</a></li>
            <li><a href="<?= e($raiz ?? '') ?>registro.php">Registro</a></li>
            <li><a href="<?= e($raiz ?? '') ?>admin/login.php">Administrador</a></li>
          </ul>
        </div>
      </div>
    </footer>

    <?= $scripts_pie ?? '' ?>
  </body>
</html>
