<section class="categories-section" id="categories">
  <div class="container">
    <h3 class="mb-4 fw-semibold">Categorías </h3>
    <div class="swiper categories-swiper">
      <div class="swiper-wrapper">
        <div class="swiper-slide">
          <div class="card shadow-sm hover-card category-card" data-category="medicamentos" style="cursor: pointer;" data-aos="fade-up">
            <img src="<?= htmlspecialchars(($basePath ?? '') . '/images/categorias/medicamentos.svg', ENT_QUOTES, 'UTF-8') ?>" class="card-img-top" alt="Medicamentos">
            <div class="card-body text-center"><h6>Medicamentos</h6></div>
          </div>
        </div>
        <div class="swiper-slide">
          <div class="card shadow-sm hover-card category-card" data-category="dermocosmetica" style="cursor: pointer;" data-aos="fade-up">
            <img src="<?= htmlspecialchars(($basePath ?? '') . '/images/categorias/dermocosmetica.svg', ENT_QUOTES, 'UTF-8') ?>" class="card-img-top" alt="Dermocosmética">
            <div class="card-body text-center"><h6>Dermocosmética</h6></div>
          </div>
        </div>
        <div class="swiper-slide">
          <div class="card shadow-sm hover-card category-card" data-category="bebes" style="cursor: pointer;" data-aos="fade-up">
            <img src="<?= htmlspecialchars(($basePath ?? '') . '/images/categorias/bebes.svg', ENT_QUOTES, 'UTF-8') ?>" class="card-img-top" alt="Bebés">
            <div class="card-body text-center"><h6>Bebés</h6></div>
          </div>
        </div>
        <div class="swiper-slide">
          <div class="card shadow-sm hover-card category-card" data-category="ofertas" style="cursor: pointer;" data-aos="fade-up">
            <img src="<?= htmlspecialchars(($basePath ?? '') . '/images/categorias/ofertas.svg', ENT_QUOTES, 'UTF-8') ?>" class="card-img-top" alt="Ofertas">
            <div class="card-body text-center"><h6>Ofertas</h6></div>
          </div>
        </div>
        <div class="swiper-slide">
          <div class="card shadow-sm hover-card category-card" data-category="dermatologicos" style="cursor: pointer;" data-aos="fade-up">
            <img src="<?= htmlspecialchars(($basePath ?? '') . '/images/categorias/dermatologicos.svg', ENT_QUOTES, 'UTF-8') ?>" class="card-img-top" alt="Dermatológicos">
            <div class="card-body text-center"><h6>Dermatológicos</h6></div>
          </div>
        </div>
        <div class="swiper-slide">
          <div class="card shadow-sm hover-card category-card" data-category="higiene" style="cursor: pointer;" data-aos="fade-up">
            <img src="<?= htmlspecialchars(($basePath ?? '') . '/images/categorias/higiene.svg', ENT_QUOTES, 'UTF-8') ?>" class="card-img-top" alt="Higiene y Cuidado Personal">
            <div class="card-body text-center"><h6>Higiene y Cuidado Personal</h6></div>
          </div>
        </div>
        <div class="swiper-slide">
          <div class="card shadow-sm hover-card category-card" data-category="cosmeticos" style="cursor: pointer;" data-aos="fade-up">
            <img src="<?= htmlspecialchars(($basePath ?? '') . '/images/categorias/cosmeticos.svg', ENT_QUOTES, 'UTF-8') ?>" class="card-img-top" alt="Cosméticos">
            <div class="card-body text-center"><h6>Cosméticos</h6></div>
          </div>
        </div>
        <div class="swiper-slide">
          <div class="card shadow-sm hover-card category-card" data-category="nutricion" style="cursor: pointer;" data-aos="fade-up">
            <img src="<?= htmlspecialchars(($basePath ?? '') . '/images/categorias/nutricion.svg', ENT_QUOTES, 'UTF-8') ?>" class="card-img-top" alt="Nutrición y Vida Saludable">
            <div class="card-body text-center"><h6>Nutrición y Vida Saludable</h6></div>
          </div>
        </div>
        <div class="swiper-slide">
          <div class="card shadow-sm hover-card category-card" data-category="equipos" style="cursor: pointer;" data-aos="fade-up">
            <img src="<?= htmlspecialchars(($basePath ?? '') . '/images/categorias/equipos.svg', ENT_QUOTES, 'UTF-8') ?>" class="card-img-top" alt="Equipos de Cuidado en Casa">
            <div class="card-body text-center"><h6>Equipos de Cuidado en Casa</h6></div>
          </div>
        </div>
        <div class="swiper-slide">
          <div class="card shadow-sm hover-card category-card" data-category="salud-particular" style="cursor: pointer;" data-aos="fade-up">
            <img src="<?= htmlspecialchars(($basePath ?? '') . '/images/categorias/salud-particular.svg', ENT_QUOTES, 'UTF-8') ?>" class="card-img-top" alt="Salud Particular">
            <div class="card-body text-center"><h6>Salud Particular</h6></div>
          </div>
        </div>
      </div>
      <div class="swiper-button-next categories-next"></div>
      <div class="swiper-button-prev categories-prev"></div>
      <div class="swiper-pagination categories-pagination"></div>
    </div>
  </div>
</section>
