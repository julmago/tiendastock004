<?php
require __DIR__.'/../../config.php';
require __DIR__.'/../../_inc/layout.php';
$s = require_seller_kind($pdo, 'mayorista', '/vendedor/login.php');

page_header('Panel Vendedor - Mayorista');
echo "<p>Vendedor: <b>".h($s['display_name'] ?? '')."</b> | Tipo: <b>mayorista</b></p>";
echo "<ul>
<li><a href='/vendedor/mayorista/tiendas.php'>Mis tiendas</a></li>
<li><a href='/vendedor/mayorista/productos.php'>Productos</a></li>
<li><a href='/vendedor/mayorista/solicitar_mayorista.php'>Solicitar mayorista</a></li>
</ul>";
page_footer();
