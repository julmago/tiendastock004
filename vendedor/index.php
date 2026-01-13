<?php
require __DIR__.'/../config.php';
require __DIR__.'/../_inc/layout.php';

if (isset($_SESSION['uid'])) {
  $st = $pdo->prepare("SELECT wholesale_status FROM sellers WHERE user_id=? LIMIT 1");
  $st->execute([(int)$_SESSION['uid']]);
  $s = $st->fetch();
  $status = $s['wholesale_status'] ?? 'not_requested';
  $target = ($status === 'not_requested') ? '/vendedor/minorista/' : '/vendedor/mayorista/';
  header('Location: '.$target);
  exit;
}

page_header('Panel Vendedor');
echo "<p>Accesos:</p>
<ul>
  <li><a href='/vendedor/minorista/login.php'>Login minorista</a></li>
  <li><a href='/vendedor/mayorista/login.php'>Login mayorista</a></li>
  <li><a href='/vendedor/register.php'>Registro</a></li>
</ul>";
page_footer();
