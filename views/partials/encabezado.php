<header class="site-header border-bottom shadow-sm fixed-top">
  <div class="container d-flex align-items-center gap-3 py-3">
    <button class="btn btn-outline-primary" id="aboutMenuBtn" title="Sobre nosotros" aria-label="Menú sobre nosotros">
      <i class="fa-solid fa-bars"></i>
    </button>
    
    <a class="navbar-brand" href="#" aria-label="SERVIFARMACIA RK">
      <img src="<?= htmlspecialchars(($basePath ?? '') . '/images/logo.png', ENT_QUOTES, 'UTF-8') ?>" alt="SERVIFARMACIA RK" class="logo-header">
    </a>

    <div class="d-flex align-items-center gap-2">
      <button class="btn btn-light position-relative" id="accountBtn" title="Mi cuenta" aria-label="Mi cuenta">
        <i class="fa-regular fa-user"></i>
      </button>
      <button class="btn btn-light position-relative" id="cartBtn" data-bs-toggle="modal" data-bs-target="#cartModal" title="Carrito" aria-label="Carrito de compras">
        <i class="fa-solid fa-cart-shopping"></i>
        <span id="cartCount" class="badge bg-danger rounded-pill position-absolute top-0 start-100 translate-middle">0</span>
      </button>
    </div>

    <div class="flex-fill">
      <div class="input-group">
        <input id="searchInput" class="form-control" type="search" placeholder="Buscar productos..." aria-label="Buscar productos">
        <button id="searchBtn" class="btn btn-primary" aria-label="Buscar">
          <i class="fa-solid fa-magnifying-glass"></i>
        </button>
      </div>
    </div>
  </div>
</header>

<div class="frontend-content">

<section class="hero">
  <div class="swiper mySwiperHero">
    <div class="swiper-wrapper">
      <div class="swiper-slide hero-slide" style="background-image:url('https://upload.wikimedia.org/wikipedia/commons/thumb/e/e6/Pharmacy%2C_interior%2C_cash_register%2C_scale%2C_interior_of_a_pharmacy_Fortepan_57178.jpg/1280px-Pharmacy%2C_interior%2C_cash_register%2C_scale%2C_interior_of_a_pharmacy_Fortepan_57178.jpg')">
        <div class="hero-card" data-aos="fade-up">
          <span class="hero-eyebrow">Bienvenido a SERVIFARMACIA RK</span>
          <h1>Tu droguería de confianza</h1>
          <p>Un espacio pensado para tu salud y la de tu familia. Conocé nuestras instalaciones y el respaldo de profesionales farmacéuticos.</p>
          <a class="btn btn-light btn-lg" href="#categories">Conocenos</a>
        </div>
      </div>
      <div class="swiper-slide hero-slide" style="background-image:url('https://upload.wikimedia.org/wikipedia/commons/thumb/2/2d/Brest_Greenberg_Pharmacy_Interior_2024-09-20_3798.jpg/1280px-Brest_Greenberg_Pharmacy_Interior_2024-09-20_3798.jpg')">
        <div class="hero-card" data-aos="fade-up">
          <span class="hero-eyebrow">Nuestra droguería</span>
          <h1>Variedad y cercanía</h1>
          <p>Recorrer nuestras estanterías es descubrir calidad en cada producto. Estamos para acompañarte en tu bienestar diario.</p>
          <a class="btn btn-light btn-lg" href="#products">Ver productos</a>
        </div>
      </div>
    </div>
    <div class="swiper-pagination"></div>
  </div>
</section>
