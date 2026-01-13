<?php
require __DIR__.'/../../config.php';
require __DIR__.'/../../_inc/layout.php';
require __DIR__.'/../../_inc/auth.php';
csrf_check();

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $u = login_user($pdo, $_POST['email']??'', $_POST['password']??'', ['seller']);
  if ($u) {
    $st = $pdo->prepare("SELECT wholesale_status FROM sellers WHERE user_id=? LIMIT 1");
    $st->execute([(int)$u['id']]);
    $seller = $st->fetch();
    $status = $seller['wholesale_status'] ?? 'not_requested';
    if ($status === 'not_requested') {
      $err = "Tu cuenta es minorista. Ingresá desde el login minorista.";
    } else {
      session_set_user($u);
      header('Location: /vendedor/mayorista/');
      exit;
    }
  } else {
    $err="Credenciales inválidas.";
  }
}
page_header('Vendedor Mayorista - Login');
if (!empty($err)) echo "<p style='color:#b00'>".h($err)."</p>";
echo "<form method='post'>
<input type='hidden' name='csrf' value='".h(csrf_token())."'>
<p><input name='email' placeholder='Email' style='width:320px'></p>
<p><input name='password' type='password' placeholder='Contraseña' style='width:320px'></p>
<button>Ingresar</button>
</form>";
page_footer();
