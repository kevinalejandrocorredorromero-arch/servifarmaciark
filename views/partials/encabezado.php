<header class="site-header fixed-top" id="siteHeader">
  <div class="rk-topbar" role="status">
    <div class="container d-flex justify-content-between gap-3 small">
      <span><i class="fa-solid fa-truck-fast me-2"></i>Domicilios locales en 45 min</span>
      <span class="d-none d-md-inline"><i class="fa-brands fa-whatsapp me-2"></i>Línea WhatsApp disponible · Atención cercana</span>
      <span class="d-none d-md-inline"><i class="fa-regular fa-clock me-2"></i>Servicio todos los días</span>
    </div>
  </div>

  <div class="container py-2 py-lg-3">
    <div class="d-flex align-items-center gap-2 gap-lg-3">
      <button class="btn btn-icon btn-outline-primary" id="aboutMenuBtn" title="Sobre nosotros" aria-label="Abrir menú sobre nosotros" aria-controls="aboutSidebar" aria-expanded="false">
        <i class="fa-solid fa-bars"></i>
      </button>
      <a class="navbar-brand flex-shrink-0" href="#home" aria-label="Ir al inicio de SERVIFARMACIA RK">
        <img src="<?= htmlspecialchars(($basePath ?? '') . '/images/logo.png', ENT_QUOTES, 'UTF-8') ?>" alt="SERVIFARMACIA RK" class="logo-header">
      </a>

      <div class="rk-search-wrap flex-grow-1 order-3 order-lg-2">
        <div class="input-group rk-search-group">
          <span class="input-group-text" aria-hidden="true"><i class="fa-solid fa-magnifying-glass"></i></span>
          <input id="searchInput" class="form-control" type="search" placeholder="Buscar medicamentos, cuidado personal..." autocomplete="off" aria-label="Buscar productos" aria-controls="searchResultsDropdown" aria-expanded="false">
          <button id="searchClearBtn" class="btn btn-light" type="button" aria-label="Limpiar búsqueda" title="Limpiar búsqueda"><i class="fa-solid fa-xmark"></i></button>
          <button id="searchBtn" class="btn btn-primary" type="button" aria-label="Buscar productos"><i class="fa-solid fa-arrow-right"></i></button>
        </div>
        <div id="searchResultsDropdown" class="rk-search-dropdown d-none" role="listbox" aria-label="Sugerencias de productos"></div>
      </div>

      <div class="header-actions d-flex align-items-center gap-2 order-2 order-lg-3 ms-auto">
        <button class="btn btn-icon btn-light position-relative" id="accountBtn" title="Mi cuenta" aria-label="Abrir mi cuenta">
          <i class="fa-regular fa-user"></i><span class="d-none d-xl-inline ms-2">Mi cuenta</span>
        </button>
        <button class="btn btn-light position-relative header-cart-button" id="cartBtn" type="button" data-bs-toggle="offcanvas" data-bs-target="#cartDrawer" aria-controls="cartDrawer" aria-label="Abrir carrito de compras" title="Carrito">
          <i class="fa-solid fa-cart-shopping"></i>
          <span id="cartCount" class="badge bg-danger rounded-pill position-absolute top-0 start-100 translate-middle">0</span>
          <span id="headerCartSubtotal" class="d-none d-xl-inline ms-2">$0</span>
        </button>
      </div>
    </div>
  </div>
</header>

<nav class="rk-bottom-nav fixed-bottom d-md-none" aria-label="Navegación móvil">
  <a href="#home" aria-label="Inicio"><i class="fa-solid fa-house"></i><span>Inicio</span></a>
  <a href="#categories" aria-label="Categorías"><i class="fa-solid fa-layer-group"></i><span>Categorías</span></a>
  <a href="#recipeTitle" aria-label="Receta médica"><i class="fa-solid fa-file-prescription"></i><span>Receta</span></a>
  <a href="#cartDrawer" data-bs-toggle="offcanvas" aria-controls="cartDrawer" aria-label="Carrito"><i class="fa-solid fa-cart-shopping"></i><span>Carrito</span><b id="mobileCartCount">0</b></a>
  <a href="#chatFab" aria-label="Soporte"><i class="fa-solid fa-comment-medical"></i><span>Soporte</span></a>
</nav>

<div class="frontend-content">
