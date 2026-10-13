<div class="cookie-banner" id="cookieBanner" role="dialog" aria-live="polite" aria-label="Aviso de cookies" hidden>
  <div class="cookie-banner__icon" aria-hidden="true"><i class="fa-solid fa-cookie-bite"></i></div>
  <div class="cookie-banner__body">
    <p class="cookie-banner__text">
      Usamos cookies propias y de terceros para mejorar tu experiencia, analizar el tráfico y personalizar contenido.
      Puedes aceptar todas, rechazarlas o consultar nuestra
      <a href="<?= htmlspecialchars(($basePath ?? '') . '/cookies.php', ENT_QUOTES, 'UTF-8') ?>">Política de Cookies</a>.
    </p>
  </div>
  <div class="cookie-banner__actions">
    <button type="button" class="btn btn-primary cookie-banner__accept" id="cookieAccept">Aceptar</button>
    <button type="button" class="btn btn-outline-secondary cookie-banner__reject" id="cookieReject">Rechazar</button>
  </div>
</div>
