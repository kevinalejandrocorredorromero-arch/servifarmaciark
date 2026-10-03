<!-- Added Admin Panel Modal -->
<div class="modal fade" id="adminPanel" tabindex="-1">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header bg-warning">
          <h5><i class="fa-solid fa-user-shield me-2"></i>Panel de Administración</h5>
          <button class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <!-- Navigation Tabs -->
            <div class="col-md-3">
              <div class="nav flex-column nav-pills" id="admin-tabs" role="tablist">
                <button class="nav-link active" id="users-tab" data-role="admin" data-bs-toggle="pill" data-bs-target="#users-panel" type="button">
                  <i class="fa-solid fa-users me-2"></i>Gestionar Usuarios
                </button>
                <button class="nav-link" id="products-tab" data-role="admin" data-bs-toggle="pill" data-bs-target="#products-panel" type="button">
                  <i class="fa-solid fa-box me-2"></i>Gestionar Productos
                </button>
                <button class="nav-link" id="orders-tab" data-role="admin" data-bs-toggle="pill" data-bs-target="#orders-panel" type="button">
                  <i class="fa-solid fa-shopping-cart me-2"></i>Ver Pedidos
                </button>
                <!-- Adding new Sales Statistics tab -->
                <button class="nav-link" id="sales-tab" data-role="seller" data-bs-toggle="pill" data-bs-target="#sales-panel" type="button">
                  <i class="fa-solid fa-chart-line me-2"></i>Estadísticas de Ventas
                </button>
                <!-- Adding Barcode Scanner tab -->
                <button class="nav-link" id="barcode-tab" data-role="seller" data-bs-toggle="pill" data-bs-target="#barcode-panel" type="button">
                  <i class="fa-solid fa-barcode me-2"></i>Lector de Códigos de Barras
                </button>
                <!-- Adding Expiration Alerts Dashboard Tab -->
                <button class="nav-link" id="alerts-tab" data-role="admin" data-bs-toggle="pill" data-bs-target="#alerts-panel" type="button">
                  <i class="fa-solid fa-bell me-2"></i>Alertas de Vencimiento
                </button>
                <button class="nav-link" id="payments-tab" data-role="admin" data-bs-toggle="pill" data-bs-target="#payments-panel" type="button">
                  <i class="fa-solid fa-qrcode me-2"></i>Métodos de Pago
                </button>
              </div>
            </div>
            
            <!-- Tab Content -->
            <div class="col-md-9">
              <div class="tab-content" id="admin-tabContent">
                
                <!-- Users Management Panel -->
                <div class="tab-pane fade show active" id="users-panel" data-role="admin">
                  <h6 class="mb-3">Usuarios Registrados</h6>
                  <div class="table-responsive">
                    <table class="table table-striped">
                      <thead>
                        <tr>
                          <th>Nombre</th>
                          <th>Email</th>
                          <th>Usuario</th>
                          <th>Tipo</th>
                          <th>Fecha Registro</th>
                          <th>Acciones</th>
                        </tr>
                      </thead>
                      <tbody id="usersTableBody">
                        <!-- Users will be populated here -->
                      </tbody>
                      </table>
                      <div id="usersPager"></div>
                  </div>
                </div>
                
                <!-- Products Management Panel -->
                <div class="tab-pane fade" id="products-panel" data-role="admin">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6>Gestión de Productos</h6>
                    <button class="btn btn-primary btn-sm" id="addProductBtn">
                      <i class="fa-solid fa-plus me-1"></i>Agregar Producto
                    </button>
                  </div>

                  <!-- Search products -->
                  <div class="mb-3">
                    <div class="input-group">
                      <span class="input-group-text"><i class="fa-solid fa-search"></i></span>
                      <input type="text" class="form-control" id="adminProductSearch" placeholder="Buscar producto por nombre, categoría o código...">
                      <button class="btn btn-outline-secondary" id="adminProductSearchClear" title="Limpiar búsqueda">
                        <i class="fa-solid fa-times"></i>
                      </button>
                    </div>
                  </div>
                  
                  <!-- Add/Edit Product Form -->
                  <div class="card mb-3" id="productForm" style="display: none;">
                    <div class="card-header">
                      <h6 class="mb-0" id="productFormTitle">Agregar Nuevo Producto</h6>
                    </div>
                    <div class="card-body">
                      <form id="productManagementForm">
                        <div class="row">
                          <div class="col-md-6">
                            <div class="mb-3">
                              <label class="form-label">Nombre del Producto</label>
                              <input type="text" class="form-control" id="productName" required>
                            </div>
                            <div class="mb-3">
                              <label class="form-label">Precio</label>
                              <input type="number" class="form-control" id="productPrice" step="0.01" min="0" required>
                            </div>
                            <div class="row g-2 mb-3">
                              <div class="col-md-6"><label class="form-label" for="productOriginalPrice">Precio anterior</label><input type="number" class="form-control" id="productOriginalPrice" min="0" step="0.01" placeholder="Opcional"></div>
                              <div class="col-md-6"><label class="form-label" for="productDiscount">Descuento (%)</label><input type="number" class="form-control" id="productDiscount" min="0" max="100" step="0.01" value="0"><small class="text-muted">Usa 0 para quitarlo.</small></div>
                            </div>
                            <div id="productDiscountPreview" class="alert alert-info py-2 d-none" role="status"></div>
                            <div class="mb-3">
                              <label class="form-label">Stock Disponible</label>
                              <input type="number" class="form-control" id="productStock" required>
                            </div>
                            <!-- Added barcode field -->
                            <div class="mb-3">
                              <label class="form-label">Código de Barras</label>
                              <input type="text" class="form-control" id="productBarcode" placeholder="ej: 7501000000000">
                            </div>
                          </div>
                          <div class="col-md-6">
                            <div class="mb-3">
                              <label class="form-label">Categoría</label>
                              <select class="form-control" id="productCategory" required>
                                <option value="">Seleccionar categoría</option>
                                <option value="medicamentos">Medicamentos</option>
                                <option value="dermocosmetica">Dermocosmética</option>
                                <option value="bebes">Bebés</option>
                                <option value="ofertas">Ofertas</option>
                                <option value="dermatologicos">Dermatológicos</option>
                                <option value="higiene">Higiene y Cuidado Personal</option>
                                <option value="cosmeticos">Cosméticos</option>
                                <option value="nutricion">Nutrición y Vida Saludable</option>
                                <option value="equipos">Equipos de Cuidado en Casa</option>
                                <option value="salud-particular">Salud Particular</option>
                              </select>
                            </div>
                            <div class="mb-3">
                              <label class="form-label">Descripción</label>
                              <textarea class="form-control" id="productDescription" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                              <label class="form-label">URL de Imagen</label>
                              <input type="url" class="form-control" id="productImage" placeholder="https://ejemplo.com/imagen.jpg">
                              <small class="form-text text-muted">URL de la imagen del producto (opcional)</small>
                            </div>
                          </div>
                        </div>
                        <!-- Added batch, date fields -->
                        <div class="row">
                          <div class="col-md-3">
                            <div class="mb-3">
                              <label class="form-label">Número de Lote</label>
                              <input type="text" class="form-control" id="productBatch" placeholder="ej: L123456">
                            </div>
                          </div>
                          <div class="col-md-3">
                            <div class="mb-3">
                              <label class="form-label">Fecha de Ingreso</label>
                              <input type="date" class="form-control" id="productIngressDate">
                            </div>
                          </div>
                          <div class="col-md-3">
                            <div class="mb-3">
                              <label class="form-label">Fecha de Vencimiento</label>
                              <input type="date" class="form-control" id="productExpiryDate">
                            </div>
                          </div>
                          <div class="col-md-3">
                            <div class="mb-3">
                              <label class="form-label">Cantidad del Lote</label>
                              <input type="number" class="form-control" id="productBatchQuantity" placeholder="Unidades">
                            </div>
                          </div>
                        </div>

                        <hr>
                        <!-- Venta fraccionada (cajas vs tabletas/unidades sueltas) -->
                        <div class="row">
                          <div class="col-12">
                            <div class="form-check form-switch mb-3">
                              <input class="form-check-input" type="checkbox" role="switch" id="productFractionable">
                              <label class="form-check-label" for="productFractionable">
                                ¿Este producto permite venta por tabletas/unidades sueltas?
                              </label>
                              <small class="text-muted d-block">Actívalo solo para medicamentos que se venden sueltos (ej. caja de 10 tabletas, vender 3 tabletas).</small>
                            </div>
                          </div>
                        </div>
                        <div class="row" id="fractionFields" style="display: none;">
                          <div class="col-md-4">
                            <div class="mb-3">
                              <label class="form-label">Unidades por caja</label>
                              <input type="number" class="form-control" id="productUnitsPerBox" min="1" placeholder="ej: 10">
                              <small class="text-muted">Tabletas/unidades que trae la caja completa.</small>
                            </div>
                          </div>
                          <div class="col-md-4">
                            <div class="mb-3">
                              <label class="form-label">Precio de caja completa</label>
                              <input type="number" class="form-control" id="productBoxPrice" step="0.01" placeholder="ej: 25000">
                            </div>
                          </div>
                          <div class="col-md-4">
                            <div class="mb-3">
                              <label class="form-label">Precio por unidad suelta</label>
                              <input type="number" class="form-control" id="productUnitPrice" step="0.01" placeholder="ej: 2800">
                            </div>
                          </div>
                        </div>

                        <div class="d-flex gap-2">
                          <button type="button" class="btn btn-success" id="saveProductBtn">
                            <i class="fa-solid fa-save me-1"></i>Guardar Producto
                          </button>
                          <button type="button" class="btn btn-secondary" id="cancelProductForm">
                            Cancelar
                          </button>
                        </div>
                      </form>
                    </div>
                  </div>
                  
                  <!-- Products List -->
                  <div class="table-responsive">
                    <table class="table table-striped">
                      <thead>
                        <tr>
                          <th>Producto</th>
                          <th>Categoría</th>
                          <th>Precio</th>
                          <th>Stock</th>
                          <th>Acciones</th>
                        </tr>
                      </thead>
                      <tbody id="productsTableBody">
                        <!-- Products will be populated here -->
                      </tbody>
                      </table>
                      <div id="productsPager"></div>
                  </div>
                </div>
                
                <!-- Orders Panel -->
                <div class="tab-pane fade" id="orders-panel" data-role="admin">
                  <h6 class="mb-3">Pedidos Realizados</h6>
                  <div class="table-responsive">
                    <table class="table table-striped table-hover">
                      <thead class="table-dark">
                        <tr>
                          <th>No. Orden</th>
                          <th>Fecha</th>
                          <th>Articulos</th>
                          <th>Total</th>
                          <th>Metodo de Pago</th>
                          <th>Estado</th>
                          <th>Acciones</th>
                        </tr>
                      </thead>
                      <tbody id="ordersTableBody">
                        <tr>
                          <td colspan="7" class="text-center text-muted py-4">No hay pedidos registrados</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                  <div id="ordersPager"></div>
                </div>
                
                <!-- Sales Panel -->
                <div class="tab-pane fade" id="sales-panel" data-role="seller">
                  <h6 class="mb-4">Estadísticas de Ventas</h6>
                  
                  <!-- Date Range Selector -->
                  <div class="card mb-4">
                    <div class="card-body">
                      <div class="row g-3">
                        <div class="col-md-3">
                          <label for="salesStartDate" class="form-label">Fecha Inicial</label>
                          <input type="date" class="form-control" id="salesStartDate">
                        </div>
                        <div class="col-md-3">
                          <label for="salesEndDate" class="form-label">Fecha Final</label>
                          <input type="date" class="form-control" id="salesEndDate">
                        </div>
                        <div class="col-md-6">
                          <div class="d-flex align-items-end h-100 gap-2">
                            <button class="btn btn-primary flex-grow-1" id="filterSalesBtn">
                              <i class="fa-solid fa-filter me-1"></i>Filtrar
                            </button>
                            <button class="btn btn-outline-secondary flex-grow-1" id="resetSalesBtn">
                              <i class="fa-solid fa-redo me-1"></i>Reiniciar
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Summary Cards -->
                  <div class="row g-3 mb-4">
                    <div class="col-md-3">
                      <div class="card bg-light">
                        <div class="card-body">
                          <div class="d-flex align-items-center justify-content-between">
                            <div>
                              <p class="text-muted mb-1">Total Ventas</p>
                              <h5 class="mb-0" id="totalSales">$0</h5>
                            </div>
                            <i class="fa-solid fa-money-bill-wave fa-2x text-success"></i>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="card bg-light">
                        <div class="card-body">
                          <div class="d-flex align-items-center justify-content-between">
                            <div>
                              <p class="text-muted mb-1">Total Pedidos</p>
                              <h5 class="mb-0" id="totalOrders">0</h5>
                            </div>
                            <i class="fa-solid fa-shopping-cart fa-2x text-primary"></i>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="card bg-light">
                        <div class="card-body">
                          <div class="d-flex align-items-center justify-content-between">
                            <div>
                              <p class="text-muted mb-1">Ticket Promedio</p>
                              <h5 class="mb-0" id="averageTicket">$0</h5>
                            </div>
                            <i class="fa-solid fa-calculator fa-2x text-info"></i>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="card bg-light">
                        <div class="card-body">
                          <div class="d-flex align-items-center justify-content-between">
                            <div>
                              <p class="text-muted mb-1">Productos Vendidos</p>
                              <h5 class="mb-0" id="totalProductsSold">0</h5>
                            </div>
                            <i class="fa-solid fa-box fa-2x text-warning"></i>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Charts -->
                  <div class="row g-3">
                    <div class="col-md-6">
                      <div class="card">
                        <div class="card-header">
                          <h6 class="mb-0">Ventas Diarias</h6>
                        </div>
                        <div class="card-body">
                          <div class="sales-chart" id="dailySalesChart">
                            <table class="table table-sm">
                              <thead>
                                <tr>
                                  <th>Fecha</th>
                                  <th>Ventas</th>
                                  <th>Pedidos</th>
                                </tr>
                              </thead>
                              <tbody id="dailySalesBody">
                                <tr>
                                  <td colspan="3" class="text-center text-muted">No hay datos</td>
                                </tr>
                              </tbody>
                            </table>
                          </div>
                          <div id="dailySalesPager"></div>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="card">
                        <div class="card-header">
                          <h6 class="mb-0">Resumen por Categoría</h6>
                        </div>
                        <div class="card-body">
                          <div class="sales-chart" id="categorySalesChart">
                            <table class="table table-sm">
                              <thead>
                                <tr>
                                  <th>Categoría</th>
                                  <th>Ventas</th>
                                  <th>Cantidad</th>
                                </tr>
                              </thead>
                              <tbody id="categorySalesBody">
                                <tr>
                                  <td colspan="3" class="text-center text-muted">No hay datos</td>
                                </tr>
                              </tbody>
                            </table>
                          </div>
                          <div id="categorySalesPager"></div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Monthly Summary -->
                  <div class="card mt-3">
                    <div class="card-header">
                      <h6 class="mb-0">Resumen Mensual</h6>
                    </div>
                    <div class="card-body">
                      <div class="monthly-summary" id="monthlySummary">
                        <div class="text-center text-muted py-4">No hay datos para mostrar</div>
                      </div>
                    </div>
                  </div>
                </div>
                
                <!-- Barcode Scanner Panel -->
                <div class="tab-pane fade" id="barcode-panel" data-role="seller">
                  <h6 class="mb-4">Lector de Códigos de Barras - Tienda Física</h6>
                  
                  <div class="row g-3">
                    <!-- Scanner input -->
                    <div class="col-md-8">
                      <div class="card">
                        <div class="card-header">
                          <h6 class="mb-0">Escanear Producto</h6>
                        </div>
                        <div class="card-body">
                          <div class="mb-3">
                            <label for="barcodeSearchInput" class="form-label">Código de Barras</label>
                            <input type="text" class="form-control form-control-lg" id="barcodeSearchInput" placeholder="Escanea o ingresa el código..." autofocus>
                            <small class="text-muted">Presiona Enter después de escanear</small>
                          </div>
                          <button class="btn btn-primary w-100" id="barcodeScanBtn">
                            <i class="fa-solid fa-search me-2"></i>Buscar Producto
                          </button>
                        </div>
                      </div>
                    </div>
                    
                    <!-- Stock info -->
                    <div class="col-md-4">
                      <div class="card">
                        <div class="card-header">
                          <h6 class="mb-0">Información del Producto</h6>
                        </div>
                        <div class="card-body" id="barcodeScannerResult">
                          <p class="text-muted text-center">Escanea un código para ver los detalles</p>
                        </div>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Products with expiration alert -->
                  <div class="card mt-4">
                    <div class="card-header">
                      <h6 class="mb-0"><i class="fa-solid fa-exclamation-triangle text-warning me-2"></i>Alertas de Vencimiento (próximo 2 meses)</h6>
                    </div>
                    <div class="card-body">
                      <div id="expirationAlerts">
                        <p class="text-muted text-center">Cargando alertas...</p>
                      </div>
                    </div>
                  </div>
                </div>
                
                <!-- Expiration Alerts Panel -->
                <div class="tab-pane fade" id="alerts-panel" data-role="admin">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">Alertas de Vencimiento - Centro de Control</h6>
                    <a href="#" id="notifyAlertsByWhatsapp" target="_blank" rel="noopener" class="btn btn-success btn-sm">
                      <i class="fa-brands fa-whatsapp me-1"></i>Notificar a la farmacia por WhatsApp
                    </a>
                  </div>
                  
                  <!-- Filter Options -->
                  <div class="card mb-4">
                    <div class="card-body">
                      <div class="row g-3">
                        <div class="col-md-3">
                          <label class="form-label">Filtrar por:</label>
                          <select class="form-control form-control-sm" id="alertFilter">
                            <option value="all">Todos</option>
                            <option value="expired">Vencidos</option>
                            <option value="critical">Crítico (0-7 días)</option>
                            <option value="warning">Advertencia (8-30 días)</option>
                            <option value="monitoring">Monitoreo (31-60 días)</option>
                          </select>
                        </div>
                        <div class="col-md-9 d-flex align-items-end">
                          <button class="btn btn-primary btn-sm" id="refreshAlertsBtn">
                            <i class="fa-solid fa-sync me-1"></i>Actualizar
                          </button>
                          <button class="btn btn-outline-secondary btn-sm ms-2" id="exportAlertsBtn">
                            <i class="fa-solid fa-download me-1"></i>Exportar
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Alert Statistics -->
                  <div class="row g-3 mb-4">
                    <div class="col-md-3">
                      <div class="card bg-danger text-white">
                        <div class="card-body">
                          <div class="d-flex justify-content-between align-items-center">
                            <div>
                              <small class="d-block">Vencidos</small>
                              <h5 class="mb-0" id="expiredCount">0</h5>
                            </div>
                            <i class="fa-solid fa-exclamation-circle fa-2x opacity-50"></i>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="card bg-warning text-dark">
                        <div class="card-body">
                          <div class="d-flex justify-content-between align-items-center">
                            <div>
                              <small class="d-block">Crítico</small>
                              <h5 class="mb-0" id="criticalCount">0</h5>
                            </div>
                            <i class="fa-solid fa-fire fa-2x opacity-50"></i>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="card bg-info text-white">
                        <div class="card-body">
                          <div class="d-flex justify-content-between align-items-center">
                            <div>
                              <small class="d-block">Advertencia</small>
                              <h5 class="mb-0" id="warningCount">0</h5>
                            </div>
                            <i class="fa-solid fa-triangle-exclamation fa-2x opacity-50"></i>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="card bg-primary text-white">
                        <div class="card-body">
                          <div class="d-flex justify-content-between align-items-center">
                            <div>
                              <small class="d-block">Monitoreo</small>
                              <h5 class="mb-0" id="monitoringCount">0</h5>
                            </div>
                            <i class="fa-solid fa-clock fa-2x opacity-50"></i>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Alerts Table -->
                  <div class="card">
                    <div class="card-header">
                      <h6 class="mb-0">Productos con Alertas</h6>
                    </div>
                    <div class="table-responsive">
                      <table class="table table-hover table-striped mb-0">
                        <thead>
                          <tr>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Lote</th>
                            <th>Vencimiento</th>
                            <th>Días Restantes</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                          </tr>
                        </thead>
                        <tbody id="alertsTableBody">
                          <tr>
                            <td colspan="7" class="text-center text-muted py-4">Cargando alertas...</td>
                          </tr>
                        </tbody>
                        </table>
                        </div>
                        <div id="alertsPager"></div>
                  </div>
                </div>
                
                <!-- Payment Methods Panel -->
                <div class="tab-pane fade" id="payments-panel" data-role="admin">
                  <h6 class="mb-3">Métodos de Pago - Códigos QR</h6>
                  <p class="text-muted small">Pega la <strong>URL de la imagen del código QR</strong> de cada billetera. El cliente la verá al seleccionar Nequi o Daviplata en el checkout. Puede ser un link de internet o la ruta de un archivo que subas a la carpeta <code>images/</code> (ej. <code>images/nequi_qr.png</code>).</p>
                  <form id="paymentConfigForm">
                    <div class="mb-3">
                      <label for="nequiQrInput" class="form-label"><i class="fa-solid fa-mobile-screen text-primary me-2"></i>QR de Nequi</label>
                      <input type="url" class="form-control" id="nequiQrInput" placeholder="https://.../nequi_qr.png o images/nequi_qr.png">
                       <input type="file" class="form-control mt-2" id="nequiQrFile" accept="image/png,image/jpeg,image/webp,image/gif">
                      <div class="form-text">Vista previa:</div>
                      <div id="nequiQrPreview" class="mt-2"></div>
                    </div>
                    <div class="mb-3">
                      <label for="daviplataQrInput" class="form-label"><i class="fa-solid fa-wallet text-info me-2"></i>QR de Daviplata</label>
                      <input type="url" class="form-control" id="daviplataQrInput" placeholder="https://.../daviplata_qr.png o images/daviplata_qr.png">
                       <input type="file" class="form-control mt-2" id="daviplataQrFile" accept="image/png,image/jpeg,image/webp,image/gif">
                      <div class="form-text">Vista previa:</div>
                      <div id="daviplataQrPreview" class="mt-2"></div>
                    </div>
                    <div class="d-flex gap-2">
                      <button type="submit" class="btn btn-primary" id="savePaymentConfigBtn">
                        <i class="fa-solid fa-save me-1"></i>Guardar configuración
                      </button>
                      <button type="button" class="btn btn-outline-secondary" id="previewPaymentConfigBtn">
                        <i class="fa-solid fa-eye me-1"></i>Actualizar vista previa
                      </button>
                    </div>
                  </form>
                </div>

              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
