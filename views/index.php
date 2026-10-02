<?php
require_once __DIR__ . '/../core/Auth.php';
Auth::iniciarSesion();
$csrfToken = Auth::tokenCsrf();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$configApp = require __DIR__ . '/../config/config.php';
$firebase = $configApp['firebase'] ?? [];
$appUrl = (string) ($configApp['app']['url'] ?? '/');
$basePath = rtrim((string) (parse_url($appUrl, PHP_URL_PATH) ?? '/'), '/');
// Solo lo que el SDK necesita en el navegador; el projectId del backend verifica los tokens.
$firebasePublico = json_encode([
    'apiKey'    => $firebase['api_key'] ?? '',
    'authDomain' => $firebase['auth_domain'] ?? '',
    'projectId' => $firebase['project_id'] ?? '',
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
  <title>SERVIFARMACIA RK - Prototipo Mejorado</title>
  <link rel="icon" type="image/png" sizes="192x192" href="images/favicon-192x192.png">
  <link rel="apple-touch-icon" href="images/favicon-192x192.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <script>window.RK_FIREBASE_CONFIG = <?= $firebasePublico ?>;</script>
</head>
<body>

  <?php require __DIR__ . '/partials/encabezado.php'; ?>

  <?php require __DIR__ . '/partials/beneficios.php'; ?>

  <?php require __DIR__ . '/partials/categorias.php'; ?>

  <?php require __DIR__ . '/partials/productos.php'; ?>

  <?php require __DIR__ . '/partials/marcas.php'; ?>

  </div>

  <?php require __DIR__ . '/partials/detalle-producto.php'; ?>

  <?php require __DIR__ . '/partials/pie-pagina.php'; ?>

  <?php require __DIR__ . '/partials/administracion.php'; ?>

  <?php require __DIR__ . '/partials/modales.php'; ?>

  <?php require __DIR__ . '/partials/autenticacion.php'; ?>

  <?php require __DIR__ . '/partials/barra-lateral.php'; ?>

  <?php require __DIR__ . '/partials/widget-chat.php'; ?>

  <input type="hidden" id="requireTest" value="OK">

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
  <script src="api-client.js?v=<?= filemtime(__DIR__ . '/../api-client.js') ?>"></script>
  <script src="script.js?v=<?= filemtime(__DIR__ . '/../script.js') ?>"></script>
  <script type="module" src="firebase-auth.js?v=<?= filemtime(__DIR__ . '/../firebase-auth.js') ?>"></script>
</body>
</html>
