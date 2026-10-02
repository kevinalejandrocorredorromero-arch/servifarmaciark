<div class="modal fade" id="productDetailModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="productDetailTitle">Detalles del Producto</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-5 mb-3 mb-md-0">
            <img id="productDetailImg" src="" alt="Producto" class="img-fluid rounded shadow-sm">
          </div>
          <div class="col-md-7">
            <h4 id="productDetailName" class="fw-bold mb-2"></h4>
            <p class="text-muted mb-2" id="productDetailCategory"></p>
            <div class="mb-3">
              <span class="fs-4 fw-bold text-primary" id="productDetailPrice"></span>
              <span class="text-muted text-decoration-line-through ms-2 d-none" id="productDetailOriginalPrice"></span>
            </div>
            <div id="productDetailDescription" class="mb-3 text-muted"></div>
            <div class="border rounded p-3 mb-3 bg-light">
              <h6 class="fw-bold mb-2"><i class="fa-solid fa-info-circle me-2 text-primary"></i>Especificaciones</h6>
              <ul class="list-unstyled mb-0 small">
                <li class="mb-1"><strong>Descripción:</strong> <span id="productDetailSpecDescription">-</span></li>
                <li class="mb-1"><strong>Stock disponible:</strong> <span id="productDetailStock" class="badge bg-success">-</span></li>
                <li class="mb-1"><strong>Fecha de vencimiento:</strong> <span id="productDetailExpiry">-</span></li>
              </ul>
            </div>
            <!-- Selector de presentación (venta fraccionada) -->
            <div id="fractionSelector" class="mb-3 d-none">
              <label class="fw-bold d-block mb-2">Presentación:</label>
              <div class="btn-group w-100" role="group" id="presentationGroup">
                <input type="radio" class="btn-check" name="presentation" id="presBox" value="caja" autocomplete="off">
                <label class="btn btn-outline-primary" for="presBox" id="presBoxLabel">Caja completa</label>
                <input type="radio" class="btn-check" name="presentation" id="presUnit" value="unidad" autocomplete="off">
                <label class="btn btn-outline-primary" for="presUnit">Tableta / Unidad suelta</label>
              </div>
              <small class="text-muted d-block mt-1" id="fractionHint"></small>
            </div>
            <div class="d-flex align-items-center gap-3 mb-3">
              <label class="fw-bold">Cantidad:</label>
              <div class="input-group" style="width: 140px;">
                <button class="btn btn-outline-secondary" type="button" id="decreaseQty">-</button>
                <input type="number" class="form-control text-center" id="productQty" value="1" min="1" max="99">
                <button class="btn btn-outline-secondary" type="button" id="increaseQty">+</button>
              </div>
            </div>
            <button class="btn btn-primary btn-lg w-100" id="addToCartFromModal">
              <i class="fa-solid fa-cart-plus me-2"></i>Agregar al Carrito
            </button>
          </div>
        </div>
        <hr class="my-4">
        <h5 class="fw-bold mb-3"><i class="fa-solid fa-tags me-2 text-primary"></i>Productos Relacionados</h5>
        <div class="row g-3" id="relatedProductsContainer"></div>
      </div>
    </div>
  </div>
</div>
