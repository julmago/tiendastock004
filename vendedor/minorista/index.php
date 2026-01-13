<?php
require __DIR__.'/../../config.php';
require __DIR__.'/../../_inc/layout.php';
$s = require_seller_kind($pdo, 'minorista', '/vendedor/login.php');

page_header('Panel Vendedor - Minorista');
echo "<p>Vendedor: <b>".h($s['display_name'] ?? '')."</b> | Tipo: <b>minorista</b></p>";
echo "<ul>
<li><a href='/vendedor/minorista/tiendas.php'>Mis tiendas</a></li>
<li><a href='/vendedor/minorista/productos.php'>Productos</a></li>
<li><a href='/vendedor/minorista/solicitar_mayorista.php'>Solicitar mayorista</a></li>
</ul>";
page_footer();
