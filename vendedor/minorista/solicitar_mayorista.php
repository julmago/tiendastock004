<?php
require __DIR__.'/../../config.php';
require __DIR__.'/../../_inc/layout.php';
csrf_check();
$s = require_seller_kind($pdo, 'minorista', '/vendedor/login.php');

page_header('Solicitar mayorista');
echo "<p>Tu cuenta es minorista. El cambio a mayorista requiere un nuevo registro.</p>";
page_footer();
