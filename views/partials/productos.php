<section class="products-section" id="products" aria-labelledby="productsTitle">
  <div class="container">
    <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
      <div>
        <span class="section-eyebrow" id="catalogEyebrow">Catálogo seleccionado</span>
        <h2 class="section-title mb-0" id="productsTitle">Productos para tu bienestar</h2>
      </div>
      <div class="rk-catalog-filters d-flex flex-wrap align-items-center gap-2" role="group" aria-label="Filtros del catálogo">
        <label class="rk-filter">
          <i class="fa-solid fa-layer-group" aria-hidden="true"></i>
          <select id="catalogCategoryFilter" aria-label="Filtrar por categoría">
            <option value="">Todas las categorías</option>
          </select>
        </label>
        <label class="rk-filter">
          <i class="fa-solid fa-boxes-stacked" aria-hidden="true"></i>
          <select id="catalogStockFilter" aria-label="Filtrar por disponibilidad">
            <option value="">Disponibilidad</option>
            <option value="available">En stock</option>
            <option value="out">Agotados</option>
          </select>
        </label>
        <label class="rk-filter">
          <i class="fa-solid fa-tags" aria-hidden="true"></i>
          <select id="catalogSaleTypeFilter" aria-label="Filtrar por tipo de venta">
            <option value="">Todos los tipos</option>
            <option value="offers">Ofertas</option>
            <option value="formula">Fórmula médica</option>
          </select>
        </label>
        <button type="button" class="rk-filter-clear d-none" id="catalogClearFilters">
          <i class="fa-solid fa-xmark" aria-hidden="true"></i>Limpiar
        </button>
      </div>
    </div>
    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3" id="productsWrapper" aria-live="polite"></div>
    <div id="productsEmptyState" class="rk-empty-state d-none" role="status">No encontramos productos con estos filtros.</div>
    <div class="catalog-load-more text-center mt-4">
      <button type="button" class="btn btn-outline-primary" id="loadMoreProductsBtn">Mostrar más productos</button>
      <p class="small text-muted mt-2 mb-0" id="catalogProgress" aria-live="polite"></p>
    </div>
  </div>
</section>
