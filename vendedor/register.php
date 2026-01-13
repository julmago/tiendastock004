<?php
require __DIR__.'/../config.php';
require __DIR__.'/../_inc/layout.php';
page_header('Registro Vendedor');
echo "<p>Elegí el tipo de cuenta:</p>
<ul>
  <li><a href='/vendedor/minorista/register.php'>Registrar minorista</a></li>
  <li><a href='/vendedor/mayorista/register.php'>Registrar mayorista</a></li>
</ul>
<p style='margin-top:12px'>¿Ya tenés cuenta? <a href='/vendedor/login.php'>Ingresá acá</a>.</p>";
page_footer();
