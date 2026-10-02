<?php
/**
 * verificar-correo.php
 * Página pública que abre el enlace del correo de verificación.
 * Procesa el token por servidor: si es válido marca el correo como verificado
 * y muestra confirmación; si expiró o es inválido ofrece reenviar el correo.
 */

require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/models/Usuario.php';

Auth::iniciarSesion();
$csrfToken = Auth::tokenCsrf();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$config = require __DIR__ . '/config/config.php';
$appUrl = (string) ($config['app']['url'] ?? '/');
$basePath = rtrim((string) (parse_url($appUrl, PHP_URL_PATH) ?? '/'), '/');
$tituloSitio = 'SERVIFARMACIA RK';

$estado = 'pendiente';   // pendiente | ok | expirado | error
$nombre = '';

$token = trim((string) ($_GET['token'] ?? ''));
if ($token !== '') {
    $modelo = new UsuarioModelo();
    $id = $modelo->verificarTokenEmail($token);
    if ($id !== null) {
        $usuario = $modelo->obtenerPorId($id);
        $nombre = $usuario['name'] ?? '';
        $estado = 'ok';
    } else {
        // Token inválido, ya usado o expirado.
        $estado = 'expirado';
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
  <title>Verificar correo — <?= htmlspecialchars($tituloSitio, ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="icon" type="image/png" sizes="192x192" href="images/favicon-192x192.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Inter', system-ui, sans-serif; background: #f8f9fa; }
    .card-verif { max-width: 480px; border: none; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,.08); }
    .logo-txt { font-family: 'Poppins', sans-serif; font-weight: 600; color: #198754; }
    .icono-ok { color: #198754; }
    .icono-error { color: #dc3545; }
  </style>
</head>
<body class="d-flex align-items-center justify-content-center vh-100 p-3">
  <div class="card card-verif w-100 p-4 p-md-5">
    <div class="text-center mb-4">
      <h1 class="h4 logo-txt mb-1"><?= htmlspecialchars($tituloSitio, ENT_QUOTES, 'UTF-8') ?></h1>
      <p class="text-muted mb-0">Verificación de correo</p>
    </div>

    <?php if ($estado === 'ok'): ?>
      <div class="text-center">
        <h2 class="h5 mb-2"><i class="fa-solid fa-circle-check icono-ok me-2"></i>Correo verificado</h2>
        <p class="text-muted">¡Gracias<?= $nombre !== '' ? ', ' . htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') : '' ?>! Tu cuenta quedó verificada. Ya puedes iniciar sesión.</p>
        <a class="btn btn-success w-100 mt-2" href="<?= htmlspecialchars($appUrl, ENT_QUOTES, 'UTF-8') ?>">Ir a la tienda</a>
      </div>
    <?php elseif ($estado === 'expirado'): ?>
      <div class="text-center">
        <h2 class="h5 mb-2"><i class="fa-solid fa-circle-xmark icono-error me-2"></i>Enlace no válido o expirado</h2>
        <p class="text-muted">Este enlace ya expiró o no es válido. Escribe el correo de tu cuenta para que te enviemos uno nuevo.</p>
        <form id="reenviarForm" class="mt-3">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
          <div class="mb-3 text-start">
            <label for="emailReenvio" class="form-label">Tu correo</label>
            <input type="email" class="form-control" id="emailReenvio" name="email" required placeholder="tucorreo@ejemplo.com">
          </div>
          <button type="submit" class="btn btn-success w-100">Reenviar correo de verificación</button>
        </form>
        <div id="reenvioMsg" class="mt-3 small d-none"></div>
        <a class="btn btn-link w-100 mt-2" href="<?= htmlspecialchars($appUrl, ENT_QUOTES, 'UTF-8') ?>">Volver a la tienda</a>
      </div>
    <?php else: ?>
      <div class="text-center">
        <p class="text-muted mb-0">No recibimos un enlace de verificación válido.</p>
        <a class="btn btn-success mt-3" href="<?= htmlspecialchars($appUrl, ENT_QUOTES, 'UTF-8') ?>">Ir a la tienda</a>
      </div>
    <?php endif; ?>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <?php if ($estado === 'expirado'): ?>
  <script>
    document.getElementById('reenviarForm')?.addEventListener('submit', async (e) => {
      e.preventDefault()
      const msg = document.getElementById('reenvioMsg')
      const email = document.getElementById('emailReenvio').value.trim()
      const csrf = document.querySelector('input[name="csrf_token"]').value
      if (!msg) return
      msg.classList.add('d-none')
      try {
        const res = await fetch('api.php?accion=reenviarVerificacion', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ email, csrf_token: csrf })
        })
        const data = await res.json()
        if (data.success) {
          msg.textContent = 'Correo enviado. Revisa tu bandeja (y la carpeta de spam).'
          msg.classList.remove('d-none', 'text-danger')
          msg.classList.add('text-success')
        } else {
          msg.textContent = data.message || data.error || 'No se pudo reenviar el correo.'
          msg.classList.remove('d-none', 'text-success')
          msg.classList.add('text-danger')
        }
      } catch (err) {
        msg.textContent = 'Error de red. Inténtalo de nuevo.'
        msg.classList.remove('d-none', 'text-success')
        msg.classList.add('text-danger')
      }
    })
  </script>
  <?php endif; ?>
</body>
</html>
