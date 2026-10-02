// Frontend conectado a PHP/MySQL para SERVIFARMACIA RK
// Todo el panel y las órdenes usan API en el servidor, sin sessionStorage.

async function appInit() {
  const apiClient = new APIClient('api.php')
  let products = []
  let cart = []
  let orders = []
  let productsSwiper = null
  let currentUser = null

  const loginModal = document.getElementById('loginModal')
  const registerModal = document.getElementById('registerModal')
  const accountBtn = document.getElementById('accountBtn')
  const searchInput = document.getElementById('searchInput')
  const searchBtn = document.getElementById('searchBtn')
  const cartCount = document.getElementById('cartCount')
  const cartItems = document.getElementById('cartItems')
  const cartEmpty = document.getElementById('cartEmpty')
  const checkoutItems = document.getElementById('checkoutItems')
  const checkoutTotal = document.getElementById('checkoutTotal')
  const confirmOrderBtn = document.getElementById('confirmOrder')
  const orderNumber = document.getElementById('orderNumber')
  const loginForm = document.getElementById('loginForm')
  const registerForm = document.getElementById('registerForm')
  const registerPassword = document.getElementById('registerPassword')
  const cardDetails = document.getElementById('cardDetails')
  const transferDetails = document.getElementById('transferDetails')
  const paypalDetails = document.getElementById('paypalDetails')
  const qrDetails = document.getElementById('qrDetails')
  const qrImage = document.getElementById('qrImage')
  const qrEmpty = document.getElementById('qrEmpty')
  const qrMethodName = document.getElementById('qrMethodName')
  const qrAppName = document.getElementById('qrAppName')
  const categoryProductsSection = document.getElementById('categoryProductsSection')
  const categoryProductsGrid = document.getElementById('categoryProductsGrid')
  const productsWrapper = document.getElementById('productsWrapper')
  const closeCategoryBtn = document.getElementById('closeCategoryBtn')
  const aboutMenuBtn = document.getElementById('aboutMenuBtn')
  const aboutSidebar = document.getElementById('aboutSidebar')
  const aboutSidebarOverlay = document.getElementById('aboutSidebarOverlay')
  const closeAboutBtn = document.getElementById('closeAboutBtn')
  const productManagementForm = document.getElementById('productManagementForm')
  const addProductBtn = document.getElementById('addProductBtn')
  const cancelProductForm = document.getElementById('cancelProductForm')
  const productFormCard = document.getElementById('productForm')
  const productFormTitle = document.getElementById('productFormTitle')
  const productFallbackImages = {
    medicamentos: 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=600&h=400&fit=crop',
    dermocosmetica: 'https://images.unsplash.com/photo-1556228578-0d85b1a4d571?w=600&h=400&fit=crop',
    bebes: 'https://images.unsplash.com/photo-1544126592-807ade215a0?w=600&h=400&fit=crop',
    ofertas: 'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?w=600&h=400&fit=crop',
    dermatologicos: 'https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?w=600&h=400&fit=crop',
    higiene: 'https://images.unsplash.com/photo-1556228720-195a672e8a03?w=600&h=400&fit=crop',
    cosmeticos: 'https://images.unsplash.com/photo-1596462502278-27bfdc403348?w=600&h=400&fit=crop',
    nutricion: 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop',
    equipos: 'https://images.unsplash.com/photo-1559757175-5700dde675bc?w=600&h=400&fit=crop',
    'salud-particular': 'https://images.unsplash.com/photo-1576091160550-2173dba999ef?w=600&h=400&fit=crop',
  }

  const productFallbackPool = Object.values(productFallbackImages)
  const appBasePath = new URL('.', document.baseURI).pathname

  function productImageUrl(url, category = '', productId = '') {
    if (url) return url.startsWith('images/products/') ? appBasePath + url : url
    const categoryImage = productFallbackImages[category]
    const seed = Number(productId) || String(productId || '').length
    const offset = seed % productFallbackPool.length
    if (categoryImage && seed % 3 === 0) return categoryImage
    return productFallbackPool[offset]
  }

  function productImgHtml(url, name, cssClass = 'card-img-top', category = '', productId = '') {
    const src = productImageUrl(url, category, productId)
    const safeSrc = escapeHtml(src)
    const safeName = escapeHtml(name || 'Producto')
    const fallback = escapeHtml(productFallbackPool[((Number(productId) || 0) + 1) % productFallbackPool.length])
    return `<img src="${safeSrc}" class="${cssClass}" alt="${safeName}" loading="lazy" onerror="this.onerror=null;this.src='${fallback}'">`
  }
  const deliveryName = document.getElementById('deliveryName')
  const deliveryPhone = document.getElementById('deliveryPhone')
  const deliveryAddress = document.getElementById('deliveryAddress')
  const filterSalesBtn = document.getElementById('filterSalesBtn')
  const resetSalesBtn = document.getElementById('resetSalesBtn')
  const salesStartDate = document.getElementById('salesStartDate')
  const salesEndDate = document.getElementById('salesEndDate')
  const totalSalesEl = document.getElementById('totalSales')
  const totalOrdersEl = document.getElementById('totalOrders')
  const averageTicketEl = document.getElementById('averageTicket')
  const totalProductsSoldEl = document.getElementById('totalProductsSold')
  const dailySalesBody = document.getElementById('dailySalesBody')
  const categorySalesBody = document.getElementById('categorySalesBody')
  const monthlySummaryEl = document.getElementById('monthlySummary')
  const alertFilter = document.getElementById('alertFilter')
  const refreshAlertsBtn = document.getElementById('refreshAlertsBtn')
  const exportAlertsBtn = document.getElementById('exportAlertsBtn')
  const expirationAlertsContainer = document.getElementById('expirationAlerts')
  const alertsTableBody = document.getElementById('alertsTableBody')
  const expiredCountEl = document.getElementById('expiredCount')
  const criticalCountEl = document.getElementById('criticalCount')
  const warningCountEl = document.getElementById('warningCount')
  const monitoringCountEl = document.getElementById('monitoringCount')
  const adminProductSearch = document.getElementById('adminProductSearch')
  const adminProductSearchClear = document.getElementById('adminProductSearchClear')

  const categoryDisplayNames = {
    medicamentos: 'Medicamentos',
    dermocosmetica: 'Dermocosmética',
    bebes: 'Bebés',
    ofertas: 'Ofertas',
    dermatologicos: 'Dermatológicos',
    higiene: 'Higiene y Cuidado Personal',
    cosmeticos: 'Cosméticos',
    nutricion: 'Nutrición y Vida Saludable',
    equipos: 'Equipos de Cuidado en Casa',
    'salud-particular': 'Salud Particular',
  }

  function setCookie(name, value, days) {
    const date = new Date()
    date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000)
    // SameSite=Lax mitiga CSRF. En produccion (HTTPS) agregar '; Secure' y considerar HttpOnly via backend.
    const secure = location.protocol === 'https:' ? '; Secure' : ''
    document.cookie = `${name}=${encodeURIComponent(value)}; expires=${date.toUTCString()}; path=/; SameSite=Lax${secure}`
  }

  function getCookie(name) {
    const cookieString = `; ${document.cookie}`
    const parts = cookieString.split(`; ${name}=`)
    if (parts.length === 2) return decodeURIComponent(parts.pop().split(';').shift())
    return null
  }

  function deleteCookie(name) {
    document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/`
  }

  function ensureSessionId() {
    let sessionId = getCookie('rk_session_id')
    if (!sessionId) {
      sessionId = `rk_${Math.random().toString(36).substr(2)}_${Date.now()}`
      setCookie('rk_session_id', sessionId, 30)
    }
    return sessionId
  }

  function getCartOwner() {
    if (currentUser && currentUser.id) {
      return { userId: currentUser.id, sessionId: null }
    }
    return { userId: null, sessionId: ensureSessionId() }
  }

  function formatCurrency(value) {
    const amount = Number(value) || 0
    return `$${amount.toLocaleString('es-CO')}`
  }

  function getProductDisplayNameById(productId) {
    const product = Array.isArray(products)
      ? products.find((item) => Number(item.id) === Number(productId))
      : null
    return product?.name || ''
  }

  function normalizeOrderItems(order) {
    if (!order || !Array.isArray(order.items)) return []

    return order.items.map((item) => {
      const productId = item.product_id ?? item.id
      const productName = (item.name || getProductDisplayNameById(productId) || 'Producto sin nombre').trim()
      const quantity = Number(item.quantity) || 0
      const price = Number(item.price) || 0
      const total = Number(item.total) || price * quantity

      return {
        ...item,
        name: productName,
        category: item.category || 'Sin categoría',
        quantity,
        price,
        total,
      }
    })
  }

  function showNotification(message, type = 'info') {
    const toast = document.createElement('div')
    toast.className = `position-fixed top-0 end-0 m-3 alert alert-${type} alert-dismissible fade show`
    toast.style.zIndex = '9999'
    toast.innerHTML = `
      ${message}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `
    document.body.appendChild(toast)
    setTimeout(() => toast.remove(), 4000)
  }

  function initSwipers() {
    const AOS = window.AOS
    if (AOS) AOS.init({ duration: 600, once: true })
    const Swiper = window.Swiper
    if (!Swiper) return

    new Swiper('.mySwiperHero', {
      loop: true,
      autoplay: { delay: 6000, disableOnInteraction: false },
      pagination: { el: '.swiper-pagination', clickable: true },
      navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
    })

    new Swiper('.categories-swiper', {
      slidesPerView: 'auto',
      spaceBetween: 20,
      loop: true,
      autoplay: { delay: 4000, disableOnInteraction: false, pauseOnMouseEnter: true },
      navigation: { nextEl: '.categories-next', prevEl: '.categories-prev' },
      pagination: { el: '.categories-pagination', clickable: true },
      breakpoints: {
        320: { slidesPerView: 1.2, spaceBetween: 15 },
        480: { slidesPerView: 1.5, spaceBetween: 15 },
        768: { slidesPerView: 2.5, spaceBetween: 20 },
        1024: { slidesPerView: 3.5, spaceBetween: 20 },
        1200: { slidesPerView: 4, spaceBetween: 20 },
      },
    })

    new Swiper('.brands-swiper', {
      slidesPerView: 2,
      spaceBetween: 20,
      loop: true,
      autoplay: { delay: 3000, disableOnInteraction: false, pauseOnMouseEnter: true },
      navigation: { nextEl: '.brands-next', prevEl: '.brands-prev' },
      pagination: { el: '.brands-pagination', clickable: true },
      breakpoints: {
        320: { slidesPerView: 2, spaceBetween: 15 },
        480: { slidesPerView: 3, spaceBetween: 15 },
        768: { slidesPerView: 4, spaceBetween: 20 },
        1024: { slidesPerView: 5, spaceBetween: 20 },
        1200: { slidesPerView: 6, spaceBetween: 20 },
      },
    })

    productsSwiper = new Swiper('.products-swiper', {
      slidesPerView: 'auto',
      spaceBetween: 16,
      loop: false,
      navigation: { nextEl: '.products-next', prevEl: '.products-prev' },
      breakpoints: {
        320: { slidesPerView: 1.1, spaceBetween: 16 },
        480: { slidesPerView: 1.5, spaceBetween: 16 },
        768: { slidesPerView: 2.1, spaceBetween: 16 },
        1024: { slidesPerView: 3.1, spaceBetween: 16 },
        1200: { slidesPerView: 3.8, spaceBetween: 16 },
      },
      observer: true,
      observeParents: true,
    })
  }

  async function loadProducts() {
    try {
      const result = await apiClient.getProducts()
      products = Array.isArray(result) ? result : []
      renderProductsSlider()
    } catch (error) {
      console.error('Error cargando productos:', error)
      showNotification('Error al cargar los productos', 'danger')
    }
  }

  function renderProductsSlider() {
    if (!productsWrapper) return
    const slideProducts = products
    productsWrapper.innerHTML = slideProducts
      .map(
        (product) => `
          <div class="swiper-slide">
            <div class="card product-card h-100 cursor-pointer" style="cursor: pointer;" data-product-id="${product.id}">
              ${productImgHtml(product.img, product.name, 'card-img-top', product.category, product.id)}
              <div class="card-body d-flex flex-column">
                <h6 class="card-title">${product.name}</h6>
                <p class="text-primary fw-bold mb-2">${formatCurrency(product.price)}</p>
                <div class="d-flex gap-2 mt-auto">
                  <button class="btn btn-primary btn-sm flex-grow-1" onclick="event.stopPropagation(); addToCart(${product.id})">
                    <i class="fa-solid fa-cart-plus"></i>
                  </button>
                  <button class="btn btn-outline-primary btn-sm" onclick="event.stopPropagation(); showProductDetail(${product.id})" title="Ver detalles">
                    <i class="fa-solid fa-eye"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
        `,
      )
      .join('')
    
    // Agregar event listeners a las tarjetas
    document.querySelectorAll('.product-card[data-product-id]').forEach(card => {
      card.addEventListener('click', function() {
        const productId = this.dataset.productId
        showProductDetail(productId)
      })
    })

    if (productsSwiper) {
      productsSwiper.update()
      productsSwiper.slideTo(0)
    }
  }

  async function loadCart() {
    try {
      const result = await apiClient.getCart()
      cart = Array.isArray(result.cart) ? result.cart : []
      updateCartUI()
      populateCheckoutSummary()
    } catch (error) {
      console.error('Error cargando carrito:', error)
      showNotification('No se pudo cargar el carrito', 'danger')
    }
  }

  function showCheckoutModal() {
    if (!currentUser) {
      showNotification('Debes iniciar sesión para realizar una compra', 'warning')
      showLoginModal()
      return
    }
    if (cart.length === 0) {
      showNotification('Tu carrito está vacío', 'warning')
      return
    }
    populateCheckoutSummary()
    loadPaymentConfig()
    const cartModalEl = document.getElementById('cartModal')
    if (cartModalEl) {
      const cartModalInstance = bootstrap.Modal.getInstance(cartModalEl) || new bootstrap.Modal(cartModalEl)
      cartModalInstance.hide()
    }
    const modalEl = document.getElementById('checkoutModal')
    if (modalEl) {
      let modal = bootstrap.Modal.getInstance(modalEl)
      if (!modal) modal = new bootstrap.Modal(modalEl)
      modal.show()
    }
  }

  // Carga la configuración de pagos (URLs de QR) y la guarda en window
  async function loadPaymentConfig() {
    try {
      const res = await apiClient.getPaymentConfig()
      window.__nequiQr = (res && res.config && res.config.nequi_qr) || ''
      window.__daviplataQr = (res && res.config && res.config.daviplata_qr) || ''
    } catch (e) {
      window.__nequiQr = ''
      window.__daviplataQr = ''
    }
    const selected = document.querySelector('input[name="paymentMethod"]:checked')?.value || 'cash'
    updateQrPanel(selected)
  }

  // Muestra el panel de QR según el método seleccionado
  function updateQrPanel(method) {
    if (!qrDetails) return
    if (method !== 'nequi' && method !== 'daviplata') {
      qrDetails.style.display = 'none'
      return
    }
    qrDetails.style.display = 'block'
    const qrUrl = method === 'nequi' ? (window.__nequiQr || '') : (window.__daviplataQr || '')
    const label = method === 'nequi' ? 'Nequi' : 'Daviplata'
    if (qrMethodName) qrMethodName.textContent = 'Paga con ' + label
    if (qrAppName) qrAppName.textContent = label
    if (qrUrl) {
      if (qrImage) { qrImage.src = qrUrl; qrImage.style.display = 'inline-block' }
      if (qrEmpty) qrEmpty.style.display = 'none'
    } else {
      if (qrImage) { qrImage.src = ''; qrImage.style.display = 'none' }
      if (qrEmpty) qrEmpty.style.display = 'block'
    }
  }

  function updateCartUI() {
    const totalQuantity = cart.reduce((sum, item) => sum + (Number(item.quantity) || 0), 0)
    if (cartCount) cartCount.textContent = totalQuantity
    const badge = document.getElementById('cartCountBadge')
    if (badge) badge.textContent = totalQuantity

    if (!cartItems || !cartEmpty) return
    if (cart.length === 0) {
      cartEmpty.style.display = 'block'
      cartItems.style.display = 'none'
      cartItems.innerHTML = ''
      const totalEl = document.getElementById('cartTotal')
      if (totalEl) totalEl.textContent = formatCurrency(0)
      return
    }

    cartEmpty.style.display = 'none'
    cartItems.style.display = 'block'

    let cartTotal = 0
    cartItems.innerHTML = cart
      .map((item) => {
        const qty = Number(item.quantity) || 1
        const price = Number(item.price) || 0
        const subtotal = price * qty
        cartTotal += subtotal
        const tipo = item.tipo_venta === 'caja' ? 'Caja' : 'Unidad'
        const tipoClass = item.tipo_venta === 'caja' ? 'bg-info text-dark' : 'bg-secondary'
        const imgHtml = productImgHtml(item.img, item.name, 'cart-item-img', item.category || 'medicamentos', item.product_id)
        return `
          <div class="cart-item">
            <div class="cart-item-media">${imgHtml}</div>
            <div class="cart-item-info">
              <div class="cart-item-name">${item.name}</div>
              <span class="cart-item-badge ${tipoClass}">${tipo}</span>
              <div class="cart-item-price">${formatCurrency(price)} c/u</div>
            </div>
            <div class="cart-item-actions">
              <div class="cart-qty">
                <button type="button" class="cart-qty-btn" onclick="changeCartQuantity(${item.id}, ${Math.max(1, qty - 1)})" aria-label="Disminuir">-</button>
                <span class="cart-qty-val">${qty}</span>
                <button type="button" class="cart-qty-btn" onclick="changeCartQuantity(${item.id}, ${qty + 1})" aria-label="Aumentar">+</button>
              </div>
              <div class="cart-item-subtotal">${formatCurrency(subtotal)}</div>
              <button type="button" class="cart-remove-btn" onclick="removeFromCart(${item.id})" title="Eliminar">
                <i class="fa-solid fa-trash"></i>
              </button>
            </div>
          </div>
        `
      })
      .join('')

    const totalEl = document.getElementById('cartTotal')
    if (totalEl) totalEl.textContent = formatCurrency(cartTotal)
  }

  window.addToCart = async function (productId, requestedQty = 1, options = {}) {
    if (!currentUser) {
      showNotification('Debes iniciar sesión para agregar productos al carrito', 'warning')
      showLoginModal()
      return
    }
    const product = products.find((item) => Number(item.id) === Number(productId))
    if (!product) {
      showNotification('Producto no encontrado', 'warning')
      return
    }
    const tipoVenta = options.tipoVenta || 'unidad'
    const unitPrice = options.price != null ? Number(options.price) : product.price
    const stock = Number(product.stock) || 0
    // Cantidad ya presente en el carrito para este producto
    const existing = (cart || []).find((c) => Number(c.product_id) === Number(productId))
    const existingQty = existing ? Number(existing.quantity) || 0 : 0
    const totalAfter = existingQty + Number(requestedQty) || 1

    // Validación de stock según presentación
    let stockDisponible = Number(product.stock) || 0
    let unidadMedida = 'unidades'
    if (tipoVenta === 'caja' && product.is_fractionable && product.units_per_box > 0) {
      stockDisponible = Math.floor((Number(product.stock_total_units) || 0) / product.units_per_box)
      unidadMedida = 'cajas'
    } else if (product.is_fractionable) {
      stockDisponible = Number(product.stock_total_units) || 0
      unidadMedida = 'unidades'
    }
    if (stockDisponible <= 0) {
      showNotification(`"${product.name}" no tiene stock disponible`, 'warning')
      return
    }
    if (totalAfter > stockDisponible) {
      showNotification(`Solo hay ${stockDisponible} ${unidadMedida} de "${product.name}"${existingQty > 0 ? ` (ya tienes ${existingQty} en el carrito)` : ''}`, 'warning')
      return
    }
    try {
      const nombreCarrito = tipoVenta === 'caja'
        ? `${product.name} (Caja)`
        : (product.is_fractionable ? `${product.name} (x${requestedQty} uds.)` : product.name)
      const result = await apiClient.addToCart({
        product_id: Number(product.id),
        name: nombreCarrito,
        price: unitPrice,
        img: product.img || '',
        quantity: Number(requestedQty) || 1,
        tipo_venta: tipoVenta,
      })
      cart = Array.isArray(result.cart) ? result.cart : cart
      if (result.session_id) {
        setCookie('rk_session_id', result.session_id, 30)
      }
      updateCartUI()
      showNotification(`${product.name} agregado al carrito`, 'success')
    } catch (error) {
      console.error('Error agregando al carrito:', error)
      showNotification('No se pudo agregar el producto', 'danger')
    }
  }

  window.removeFromCart = async function (cartId) {
    try {
      await apiClient.removeFromCart(cartId)
      await loadCart()
      showNotification('Producto eliminado del carrito', 'success')
    } catch (error) {
      console.error('Error eliminando producto:', error)
      showNotification('No se pudo eliminar el producto', 'danger')
    }
  }

  window.changeCartQuantity = async function (cartId, quantity) {
    if (quantity < 1) {
      return removeFromCart(cartId)
    }
    // Validar contra el stock disponible del producto
    const item = (cart || []).find((c) => Number(c.id) === Number(cartId) || Number(c.cart_id) === Number(cartId))
    if (item) {
      const product = products.find((p) => Number(p.id) === Number(item.product_id))
      const stock = product ? (Number(product.stock) || 0) : Infinity
      if (quantity > stock) {
        showNotification(`Solo hay ${stock} unidades disponibles de "${product ? product.name : 'este producto'}"`, 'warning')
        await loadCart()
        return
      }
    }
    try {
      await apiClient.updateCartItem(cartId, quantity)
      await loadCart()
    } catch (error) {
      console.error('Error actualizando cantidad:', error)
      showNotification('No se pudo actualizar la cantidad', 'danger')
    }
  }

  window.showProductDetail = function (productId) {
    const product = products.find((p) => Number(p.id) === Number(productId))
    if (!product) {
      showNotification('Producto no encontrado', 'warning')
      return
    }

    // Llenar los datos del modal
    document.getElementById('productDetailTitle').textContent = product.name
    document.getElementById('productDetailName').textContent = product.name
    const detailImg = document.getElementById('productDetailImg')
    const detailSrc = productImageUrl(product.img, product.category, product.id)
    detailImg.src = detailSrc
    detailImg.alt = product.name || 'Producto'
    detailImg.style.display = ''
    detailImg.onerror = () => { detailImg.onerror = null; detailImg.src = productImageUrl('', product.category, Number(product.id) + 1) }
    document.getElementById('productDetailPrice').textContent = formatCurrency(product.price)
    document.getElementById('productDetailCategory').textContent = categoryDisplayNames[product.category] || product.category
    document.getElementById('productDetailDescription').textContent = product.description || 'Sin descripción disponible'
    
    document.getElementById('productDetailSpecDescription').textContent = product.description || 'Sin descripción disponible'
    document.getElementById('productDetailStock').textContent = `${product.stock} unidades`
    document.getElementById('productDetailExpiry').textContent = product.expiry_date ? new Date(product.expiry_date).toLocaleDateString('es-CO') : 'No especificado'

    // --- Venta fraccionada (cajas vs tabletas/unidades sueltas) ---
    const fractionSelector = document.getElementById('fractionSelector')
    const presBox = document.getElementById('presBox')
    const presUnit = document.getElementById('presUnit')
    const presBoxLabel = document.getElementById('presBoxLabel')
    const fractionHint = document.getElementById('fractionHint')
    const addBtn = document.getElementById('addToCartFromModal')
    if (fractionSelector) fractionSelector.classList.add('d-none')
    if (addBtn) delete addBtn.dataset.presentation

    if (product.is_fractionable && product.units_per_box > 0) {
      if (fractionSelector) fractionSelector.classList.remove('d-none')
      // Preparar labels con precios
      if (presBoxLabel) presBoxLabel.textContent = `Caja completa (${product.units_per_box} u.) - ${formatCurrency(product.box_price || 0)}`
      if (presUnit) presUnit.textContent // noop
      // Marcar "caja" por defecto
      if (presBox) presBox.checked = true
      if (presUnit) presUnit.checked = false
      if (addBtn) addBtn.dataset.presentation = 'caja'
      // Mostrar precio de caja inicialmente
      document.getElementById('productDetailPrice').textContent = formatCurrency(product.box_price || product.price)
      if (fractionHint) fractionHint.textContent = `Caja: ${formatCurrency(product.box_price || 0)} | Tableta suelta: ${formatCurrency(product.unit_price || 0)}`
      // Calcular stock disponible en cajas
      const cajasDisp = Math.floor((product.stock_total_units || 0) / product.units_per_box)
      document.getElementById('productDetailStock').textContent = `${cajasDisp} cajas (${product.stock_total_units || 0} uds.)`
      // Listeners para cambiar presentación y recalcular precio/stock
      const recalc = () => {
        const pres = presBox && presBox.checked ? 'caja' : 'unidad'
        if (addBtn) addBtn.dataset.presentation = pres
        const precio = pres === 'caja' ? (product.box_price || product.price) : (product.unit_price || product.price)
        document.getElementById('productDetailPrice').textContent = formatCurrency(precio)
        if (pres === 'caja') {
          const cDisp = Math.floor((product.stock_total_units || 0) / product.units_per_box)
          document.getElementById('productDetailStock').textContent = `${cDisp} cajas (${product.stock_total_units || 0} uds.)`
        } else {
          document.getElementById('productDetailStock').textContent = `${product.stock_total_units || 0} tabletas/unidades sueltas`
        }
      }
      if (presBox) presBox.onchange = recalc
      if (presUnit) presUnit.onchange = recalc
      recalc()
    }

    // Resetear cantidad
    document.getElementById('productQty').value = 1

    // Guardar el ID actual para agregar al carrito
    document.getElementById('addToCartFromModal').dataset.productId = productId

    // Cargar productos relacionados
    loadRelatedProducts(product.category, productId)

    // Mostrar el modal
    let modal = window.bootstrap.Modal.getInstance(document.getElementById('productDetailModal'))
    if (!modal) modal = new window.bootstrap.Modal(document.getElementById('productDetailModal'))
    modal.show()
  }

  function loadRelatedProducts(category, excludeProductId) {
    const relatedContainer = document.getElementById('relatedProductsContainer')
    // Tomar 3 productos de TODO el catálogo (excluyendo el que se está viendo),
    // sin limitarse a una sola categoría, para asegurar que siempre haya relacionados.
    const related = products.filter((p) => Number(p.id) !== Number(excludeProductId)).slice(0, 3)

    if (related.length === 0) {
      relatedContainer.innerHTML = '<div class="col-12"><p class="text-muted">No hay otros productos</p></div>'
      return
    }

    relatedContainer.innerHTML = related
      .map(
        (p) => `
        <div class="col-6 col-md-4">
          <div class="card product-card h-100 cursor-pointer" style="cursor: pointer;">
            ${productImgHtml(p.img, p.name, 'card-img-top', p.category, p.id)}
            <div class="card-body d-flex flex-column">
              <h6 class="card-title">${p.name}</h6>
              <p class="text-primary fw-bold mb-2">${formatCurrency(p.price)}</p>
              <button class="btn btn-sm btn-outline-primary mt-auto" onclick="showProductDetail(${p.id})">
                Ver detalles
              </button>
            </div>
          </div>
        </div>
      `,
      )
      .join('')
  }

  async function clearCart() {
    try {
      await apiClient.clearCart()
      cart = []
      updateCartUI()
    } catch (error) {
      console.error('Error limpiando carrito:', error)
    }
  }

  function populateCheckoutSummary() {
    if (!checkoutItems || !checkoutTotal) return
    let total = 0
    checkoutItems.innerHTML = cart
      .map((item) => {
        const price = Number(item.price) || 0
        const amount = price * (Number(item.quantity) || 0)
        total += amount
        return `
          <div class="d-flex justify-content-between mb-1" style="font-size: 0.85rem; padding: 6px 0; border-bottom: 1px solid #eee;">
            <div>
              <div style="font-weight: 500; font-size: 0.85rem;">${item.name}</div>
              <small class="text-muted" style="font-size: 0.75rem;">x${item.quantity}</small>
            </div>
            <div style="font-weight: 600; color: #00a86b;">${formatCurrency(amount)}</div>
          </div>
        `
      })
      .join('')
    checkoutTotal.textContent = formatCurrency(total)
  }

  async function handleCheckout() {
    const form = document.getElementById('checkoutForm')
    if (!form || !form.checkValidity()) {
      form?.reportValidity()
      return
    }
    if (cart.length === 0) {
      showNotification('Tu carrito está vacío', 'warning')
      return
    }
    const deliveryInfo = {
      name: deliveryName?.value || '',
      phone: deliveryPhone?.value || '',
      address: deliveryAddress?.value || '',
    }
    const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked')?.value || 'cash'
    if (paymentMethod === 'nequi' || paymentMethod === 'daviplata') {
      const qrUrl = (paymentMethod === 'nequi' ? window.__nequiQr : window.__daviplataQr) || ''
      if (!qrUrl) {
        showNotification('Aún no tenemos un QR configurado para este método. Elige otro o contáctanos.', 'warning')
        return
      }
    }
    // Validar stock disponible para cada producto del carrito (considera presentacion fraccionada)
    for (const item of cart) {
      const product = products.find((prod) => Number(prod.id) === Number(item.product_id))
      if (!product) continue
      const tipoVenta = item.tipo_venta || 'unidad'
      let stockDisp = Number(product.stock) || 0
      let unidad = 'unidades'
      if (product.is_fractionable && product.units_per_box > 0) {
        if (tipoVenta === 'caja') {
          stockDisp = Math.floor((Number(product.stock_total_units) || 0) / product.units_per_box)
          unidad = 'cajas'
        } else {
          stockDisp = Number(product.stock_total_units) || 0
          unidad = 'unidades sueltas'
        }
      }
      const qty = Number(item.quantity) || 0
      if (qty > stockDisp) {
        showNotification(`Stock insuficiente para "${product.name}": solicitas ${qty} ${unidad} pero solo hay ${stockDisp} disponibles`, 'warning')
        return
      }
    }
    const items = cart.map((item) => {
      const product = products.find((prod) => Number(prod.id) === Number(item.product_id))
      return {
        id: Number(item.product_id),
        name: item.name,
        category: product?.category || 'general',
        price: Number(item.price),
        quantity: Number(item.quantity),
        tipo_venta: item.tipo_venta || 'unidad',
      }
    })
    const total = items.reduce((sum, item) => sum + item.price * item.quantity, 0)
    try {
      confirmOrderBtn.disabled = true
      const originalText = confirmOrderBtn.innerHTML
      confirmOrderBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...'
      const response = await apiClient.checkout({ deliveryInfo, items, total, paymentMethod })
      if (response.success) {
        orderNumber.textContent = response.orderNumber || '#RK' + Date.now().toString().slice(-6)
        // Guardar el pedido para armar el enlace de WhatsApp de confirmación
        window.__lastOrder = {
          orderNumber: orderNumber.textContent,
          deliveryInfo,
          items,
          total,
          paymentMethod,
        }
        await clearCart()
        const checkoutModalInstance = bootstrap.Modal.getInstance(document.getElementById('checkoutModal'))
        checkoutModalInstance?.hide()
        setTimeout(() => {
          let confirmationModal = bootstrap.Modal.getInstance(document.getElementById('orderConfirmationModal'))
          if (!confirmationModal) confirmationModal = new bootstrap.Modal(document.getElementById('orderConfirmationModal'))
          confirmationModal.show()
        }, 300)
        form.reset()
        showNotification('Pedido procesado correctamente', 'success')
      } else {
        showNotification(response.error || 'Error al procesar la orden', 'danger')
      }
    } catch (error) {
      console.error('Error en checkout:', error)
      showNotification('No se pudo procesar el pedido', 'danger')
    } finally {
      confirmOrderBtn.disabled = false
      confirmOrderBtn.innerHTML = 'Confirmar Pedido'
      await loadProducts()
      await loadProductsTable()
      await loadOrdersData()
    }
  }

  async function checkAuthStatus() {
    try {
      const response = await apiClient.getMe()
      if (response.success && response.user) {
        currentUser = response.user
        updateUIForLoggedInUser(currentUser)
      }
    } catch (error) {
      console.warn('No se pudo comprobar la sesión:', error)
    }
  }

  function updateUIForLoggedInUser(userData) {
    if (!accountBtn) return
    let role = (userData.rol || userData.role || '').toLowerCase()
    if (!role) {
      if (userData.es_admin || userData.isAdmin) role = 'admin'
      else if (userData.es_vendedor || userData.isSeller) role = 'seller'
      else role = 'customer'
    }
    if (role === 'admin') {
      accountBtn.innerHTML = `<i class="fa-solid fa-user-shield text-warning"></i> ${userData.name}`
      accountBtn.title = 'Panel de Administrador'
      accountBtn.onclick = showAdminMenu
    } else if (role === 'seller') {
      accountBtn.innerHTML = `<i class="fa-solid fa-user-tag text-primary"></i> ${userData.name}`
      accountBtn.title = 'Panel de Vendedor'
      accountBtn.onclick = showSellerMenu
    } else {
      accountBtn.innerHTML = `<i class="fa-solid fa-user"></i> ${userData.name}`
      accountBtn.title = 'Mi cuenta'
      accountBtn.onclick = showUserMenu
    }
  }

  function renderAccountMenu({ name, roleLabel, roleIcon, items }) {
    document.querySelectorAll('.account-dropdown').forEach((m) => m.remove())
    const menu = document.createElement('div')
    menu.className = 'account-dropdown'
    const roleText = roleLabel ? `${roleIcon ? '<i class="' + roleIcon + ' me-1"></i>' : ''}${roleLabel}` : 'Mi cuenta'
    menu.innerHTML = `
      <div class="acc-header">
        <div class="acc-avatar"><i class="fa-solid fa-user-circle"></i></div>
        <div class="acc-userinfo">
          <div class="acc-name">${name || 'Usuario'}</div>
          <div class="acc-role">${roleText}</div>
        </div>
      </div>
      <div class="acc-body">
        ${items.map((it, i) => it.divider
          ? '<div class="acc-divider"></div>'
          : `<button class="acc-item ${it.danger ? 'acc-danger' : ''}" data-acc-index="${i}"><i class="${it.icon}"></i><span>${it.label}</span></button>`
        ).join('')}
      </div>
    `
    document.body.appendChild(menu)
    items.forEach((it, i) => {
      const btn = menu.querySelector(`[data-acc-index="${i}"]`)
      if (btn && it.onClick) btn.addEventListener('click', () => { menu.remove(); it.onClick() })
    })
    setTimeout(() => {
      document.addEventListener('click', function closeMenu(e) {
        if (!menu.contains(e.target) && e.target !== accountBtn && !accountBtn.contains(e.target)) {
          menu.remove()
          document.removeEventListener('click', closeMenu)
        }
      })
    }, 120)
    return menu
  }

  function showAdminMenu() {
    renderAccountMenu({
      name: currentUser?.name,
      roleLabel: 'Administrador',
      roleIcon: 'fa-solid fa-user-shield',
      items: [
        { icon: 'fa-solid fa-gauge-high', label: 'Panel de Administración', onClick: () => {
            let adminPanelModal = bootstrap.Modal.getInstance(document.getElementById('adminPanel'))
            if (!adminPanelModal) adminPanelModal = new bootstrap.Modal(document.getElementById('adminPanel'))
            adminPanelModal.show()
            applyAdminPanelAccess('admin')
            loadAdminData()
          } },
        { icon: 'fa-solid fa-bell', label: 'Alertas de Inventario', onClick: () => {
            let adminPanelModal = bootstrap.Modal.getInstance(document.getElementById('adminPanel'))
            if (!adminPanelModal) adminPanelModal = new bootstrap.Modal(document.getElementById('adminPanel'))
            adminPanelModal.show()
            applyAdminPanelAccess('admin')
            loadAdminData()
            setTimeout(() => document.getElementById('alerts-tab')?.click(), 300)
          } },
        { divider: true },
        { icon: 'fa-solid fa-right-from-bracket', label: 'Cerrar Sesión', danger: true, onClick: logout },
      ],
    })
  }

  function showSellerMenu() {
    renderAccountMenu({
      name: currentUser?.name,
      roleLabel: 'Vendedor',
      roleIcon: 'fa-solid fa-barcode',
      items: [
        { icon: 'fa-solid fa-barcode', label: 'Escáner de Venta', onClick: () => {
            let adminPanelModal = bootstrap.Modal.getInstance(document.getElementById('adminPanel'))
            if (!adminPanelModal) adminPanelModal = new bootstrap.Modal(document.getElementById('adminPanel'))
            adminPanelModal.show()
            applyAdminPanelAccess('seller')
            loadSellerDashboard()
          } },
        { divider: true },
        { icon: 'fa-solid fa-right-from-bracket', label: 'Cerrar Sesión', danger: true, onClick: logout },
      ],
    })
  }

  function showUserMenu() {
    renderAccountMenu({
      name: currentUser?.name,
      roleLabel: 'Cliente',
      roleIcon: 'fa-solid fa-user-tag',
      items: [
        { icon: 'fa-solid fa-bag-shopping', label: 'Mis Pedidos', onClick: () => {
            openMyOrders()
          } },
        { icon: 'fa-solid fa-user-pen', label: 'Editar mi cuenta', onClick: () => openEditProfileModal() },
        { divider: true },
        { icon: 'fa-solid fa-right-from-bracket', label: 'Cerrar Sesión', danger: true, onClick: logout },
      ],
    })
  }

  async function openMyOrders() {
    if (!currentUser) {
      showLoginModal()
      return
    }
    const modal = document.getElementById('myAccountModal')
    if (!modal) return
    const instance = bootstrap.Modal.getOrCreateInstance(modal)
    instance.show()
    await loadMyOrders()
  }

  async function loadMyOrders() {
    const container = document.getElementById('myOrdersContainer')
    if (!container) return
    try {
      const result = await apiClient.getOrders()
      const ownOrders = Array.isArray(result) ? result : []
      if (!ownOrders.length) {
        container.innerHTML = '<div class="text-center text-muted py-4">No tienes pedidos registrados.</div>'
        return
      }
      container.innerHTML = ownOrders.map((order) => {
        const cancelable = order.status === 'pendiente'
        const status = order.status === 'cancelado'
          ? '<span class="badge bg-danger">Cancelado</span>'
          : order.status === 'entregado'
            ? '<span class="badge bg-success">Entregado</span>'
            : '<span class="badge bg-warning text-dark">Pendiente</span>'
        return `<div class="card mb-3"><div class="card-body d-flex justify-content-between align-items-center gap-3">
          <div><strong>${order.order_number}</strong><div class="small text-muted">${new Date(order.order_date).toLocaleString()} · ${formatCurrency(order.total)}</div></div>
          <div class="d-flex align-items-center gap-2">${status}
            ${cancelable ? `<button class="btn btn-sm btn-outline-danger" type="button" onclick="cancelMyOrder(${order.id})"><i class="fa-solid fa-ban me-1"></i>Cancelar</button>` : ''}
          </div>
        </div></div>`
      }).join('')
    } catch (error) {
      container.innerHTML = '<div class="alert alert-danger">No se pudieron cargar tus pedidos.</div>'
    }
  }

  window.cancelMyOrder = async function (orderId) {
    if (!confirm('¿Deseas cancelar este pedido?')) return
    try {
      await apiClient.cancelOrder(orderId)
      showNotification('Pedido cancelado', 'success')
      await loadMyOrders()
    } catch (error) {
      showNotification(error.message || 'No se pudo cancelar el pedido', 'danger')
    }
  }

  function openEditProfileModal() {
    if (!currentUser) {
      showLoginModal()
      return
    }
    const epName = document.getElementById('epName')
    const epEmail = document.getElementById('epEmail')
    const epUsername = document.getElementById('epUsername')
    const epPassword = document.getElementById('epPassword')
    if (epName) epName.value = currentUser.name || ''
    if (epEmail) epEmail.value = currentUser.email || ''
    if (epUsername) epUsername.value = currentUser.username || ''
    if (epPassword) epPassword.value = ''
    const modal = document.getElementById('editProfileModal')
    if (modal) {
      let m = bootstrap.Modal.getInstance(modal)
      if (!m) m = new bootstrap.Modal(modal)
      m.show()
    }
  }

  function handleEditProfileSubmit(e) {
    e.preventDefault()
    if (!currentUser) return
    const epName = document.getElementById('epName')
    const epEmail = document.getElementById('epEmail')
    const epUsername = document.getElementById('epUsername')
    const epPassword = document.getElementById('epPassword')
    const payload = {
      id: currentUser.id,
      name: epName?.value.trim(),
      email: epEmail?.value.trim(),
      username: epUsername?.value.trim(),
    }
    if (epPassword && epPassword.value) payload.password = epPassword.value
    apiClient.updateProfile(payload)
      .then((res) => {
        if (res && res.user) {
          currentUser = res.user
          updateUIForLoggedInUser(currentUser)
          showNotification('Tu cuenta ha sido actualizada', 'success')
        } else {
          showNotification('No se pudo actualizar la cuenta', 'danger')
        }
        const modal = document.getElementById('editProfileModal')
        if (modal) bootstrap.Modal.getInstance(modal)?.hide()
      })
      .catch((err) => {
        console.error('Error actualizando perfil:', err)
        showNotification(err.message || 'Error al actualizar la cuenta', 'danger')
      })
  }

  function applyAdminPanelAccess(role) {
    const allowedRoles = role === 'seller' ? ['seller'] : ['admin']
    document.querySelectorAll('#admin-tabs .nav-link').forEach((tab) => {
      const tabRole = tab.dataset.role || 'admin'
      const isAccessible = allowedRoles.includes(tabRole) || role === 'admin'
      tab.classList.toggle('d-none', !isAccessible)
    })

    document.querySelectorAll('#admin-tabContent .tab-pane').forEach((panel) => {
      const panelRole = panel.dataset.role || 'admin'
      const isAccessible = allowedRoles.includes(panelRole) || role === 'admin'
      if (!isAccessible) {
        panel.classList.remove('show', 'active')
      }
    })

    if (role === 'seller') {
      const salesTab = document.getElementById('sales-tab')
      const barcodeTab = document.getElementById('barcode-tab')
      if (salesTab) salesTab.classList.remove('d-none')
      if (barcodeTab) barcodeTab.classList.remove('d-none')
      const salesPanel = document.getElementById('sales-panel')
      const barcodePanel = document.getElementById('barcode-panel')
      if (salesPanel) salesPanel.classList.add('show', 'active')
      if (barcodePanel) barcodePanel.classList.remove('show', 'active')
    }
  }

  function showSellerMenu() {
    const menu = document.createElement('div')
    menu.className = 'position-fixed bg-white shadow-lg rounded p-3 admin-user-menu'
    menu.style.cssText = 'top: 70px; right: 20px; z-index: 9999; min-width: 220px;'
    menu.innerHTML = `
      <div class="d-flex flex-column gap-2">
        <button class="btn btn-primary btn-sm" id="openSellerPanelBtn">
          <i class="fa-solid fa-barcode me-2"></i>Escáner de Venta
        </button>
        <button class="btn btn-outline-danger btn-sm" id="logoutBtn">
          <i class="fa-solid fa-sign-out-alt me-2"></i>Cerrar Sesión
        </button>
      </div>
    `
    document.querySelectorAll('.admin-user-menu').forEach((m) => m.remove())
    document.body.appendChild(menu)
    document.getElementById('openSellerPanelBtn')?.addEventListener('click', () => {
      menu.remove()
      let adminPanelModal = bootstrap.Modal.getInstance(document.getElementById('adminPanel'))
      if (!adminPanelModal) adminPanelModal = new bootstrap.Modal(document.getElementById('adminPanel'))
      adminPanelModal.show()
      applyAdminPanelAccess('seller')
      loadSellerDashboard()
    })
    document.getElementById('logoutBtn')?.addEventListener('click', () => {
      menu.remove()
      logout()
    })
    setTimeout(() => {
      document.addEventListener('click', function closeMenu(e) {
        if (!menu.contains(e.target) && e.target !== accountBtn) {
          menu.remove()
          document.removeEventListener('click', closeMenu)
        }
      })
    }, 100)
  }

  async function logout() {
    if (!confirm('¿Estás seguro de que quieres cerrar sesión?')) return
    try {
      await apiClient.logout()
    } catch (error) {
      console.warn('El servidor no pudo cerrar la sesión:', error)
    }
    await window.rkFirebase?.cerrarSesion?.()
    currentUser = null
    cart = []
    accountBtn.innerHTML = '<i class="fa-regular fa-user"></i>'
    accountBtn.title = 'Mi cuenta'
    accountBtn.onclick = () => showLoginModal()
    // La página contiene el token CSRF de la sesión anterior. Recargar evita
    // reutilizarlo y obtiene una sesión/token nuevos antes del siguiente login.
    window.location.replace(`${window.location.pathname}?auth_refresh=${Date.now()}`)
  }

  function showLoginModal() {
    if (!loginModal) return
    mostrarErrorFirebase(null)
    let modal = bootstrap.Modal.getInstance(loginModal)
    if (!modal) modal = new bootstrap.Modal(loginModal)
    modal.show()
  }

  function showRegisterModal() {
    if (!registerModal) return
    let modal = bootstrap.Modal.getInstance(registerModal)
    if (!modal) modal = new bootstrap.Modal(registerModal)
    modal.show()
  }

  function validatePasswordSecurity(password) {
    return {
      length: password.length >= 8,
      uppercase: /[A-Z]/.test(password),
      lowercase: /[a-z]/.test(password),
      number: /\d/.test(password),
      special: /[!@#$%^&*()_+\-=[\]{};':"\\|,.<>/?]/.test(password),
    }
  }

  function updatePasswordRules(password) {
    const rules = validatePasswordSecurity(password)
    Object.keys(rules).forEach((rule) => {
      const element = document.getElementById(`rule-${rule}`)
      if (!element) return
      const icon = element.querySelector('i')
      if (rules[rule]) {
        icon.className = 'fa-solid fa-check text-success me-1'
        element.classList.add('text-success')
        element.classList.remove('text-muted')
      } else {
        icon.className = 'fa-solid fa-times text-danger me-1'
        element.classList.remove('text-success')
        element.classList.add('text-muted')
      }
    })
  }

  let reenvioEmailPendiente = ''
  let reenvioCooldown = null

  function ocultarPanelReenvio() {
    document.getElementById('registerResend')?.classList.add('d-none')
    if (reenvioCooldown) {
      clearInterval(reenvioCooldown)
      reenvioCooldown = null
    }
  }

  function iniciarCooldownReenvio(segundos) {
    const btn = document.getElementById('btnReenviarCorreo')
    if (!btn) return
    btn.disabled = true
    let restante = segundos
    btn.textContent = `Reenviar correo (${restante}s)`
    if (reenvioCooldown) clearInterval(reenvioCooldown)
    reenvioCooldown = setInterval(() => {
      restante--
      if (restante <= 0) {
        clearInterval(reenvioCooldown)
        reenvioCooldown = null
        btn.disabled = false
        btn.textContent = 'Reenviar correo'
      } else {
        btn.textContent = `Reenviar correo (${restante}s)`
      }
    }, 1000)
  }

  function mostrarPanelReenvio(email) {
    reenvioEmailPendiente = email
    const emailEl = document.getElementById('registerResendEmail')
    const msgEl = document.getElementById('registerResendMsg')
    if (emailEl) emailEl.textContent = email
    if (msgEl) {
      msgEl.textContent = ''
      msgEl.classList.add('d-none')
      msgEl.classList.remove('text-danger')
    }
    document.getElementById('registerResend')?.classList.remove('d-none')
    iniciarCooldownReenvio(60)
  }

  async function handleReenviarCorreo() {
    const btn = document.getElementById('btnReenviarCorreo')
    const msgEl = document.getElementById('registerResendMsg')
    if (!btn || !msgEl || !reenvioEmailPendiente) return
    btn.disabled = true
    msgEl.classList.remove('d-none', 'text-danger')
    msgEl.textContent = 'Enviando...'
    try {
      const res = await apiClient.resendVerification(reenvioEmailPendiente)
      if (!res || !res.success) throw new Error(res?.error || 'No se pudo reenviar el correo')
      msgEl.textContent = 'Correo reenviado. Revisa tu bandeja de entrada y la carpeta de spam.'
      iniciarCooldownReenvio(60)
    } catch (err) {
      console.error('Error reenviando verificación:', err)
      msgEl.classList.add('text-danger')
      msgEl.textContent = err.message || 'No se pudo reenviar el correo. Intenta de nuevo.'
      btn.disabled = false
    }
  }

  async function handleRegister(event) {
    event.preventDefault()
    if (!registerForm) return
    const name = document.getElementById('registerName')?.value.trim() || ''
    const email = document.getElementById('registerEmail')?.value.trim() || ''
    const username = document.getElementById('registerUser')?.value.trim() || ''
    const password = registerPassword?.value || ''
    const confirmPassword = document.getElementById('registerConfirmPassword')?.value || ''
    const errorEl = document.getElementById('registerError')
    const successEl = document.getElementById('registerSuccess')
    if (!errorEl || !successEl) return
    errorEl.classList.add('d-none')
    successEl.classList.add('d-none')
    ocultarPanelReenvio()
    if (!name || !email || !username || !password || !confirmPassword) {
      errorEl.textContent = 'Todos los campos son obligatorios'
      errorEl.classList.remove('d-none')
      return
    }
    if (password !== confirmPassword) {
      errorEl.textContent = 'Las contraseñas no coinciden'
      errorEl.classList.remove('d-none')
      return
    }
    const rules = validatePasswordSecurity(password)
    if (!Object.values(rules).every(Boolean)) {
      errorEl.textContent = 'La contraseña no cumple los requisitos de seguridad'
      errorEl.classList.remove('d-none')
      return
    }
    try {
      const response = await apiClient.registerUser({ name, email, username, password })
      if (response.success) {
        registerForm.reset()
        // El modal permanece abierto en ambos casos: el usuario debe ver la
        // notificación y tener a mano la opción de reenvío.
        if (response.verificacion_enviada) {
          successEl.textContent = '¡Cuenta creada con éxito!'
          successEl.classList.remove('d-none')
          mostrarPanelReenvio(email)
        } else {
          // La cuenta se creó pero el correo no pudo enviarse (fallo temporal
          // del proveedor): sin reenvío el usuario quedaría bloqueado, porque
          // el login exige correo verificado.
          successEl.textContent = 'Cuenta creada, pero no pudimos enviarte el correo de verificación en este momento.'
          successEl.classList.remove('d-none')
          mostrarPanelReenvio(email)
        }
      } else {
        errorEl.textContent = response.error || 'Error al crear la cuenta'
        errorEl.classList.remove('d-none')
      }
    } catch (error) {
      console.error('Error registrando usuario:', error)
      errorEl.textContent = error.message || 'Error al crear la cuenta'
      errorEl.classList.remove('d-none')
    }
  }

  async function handleLogin(event) {
    event.preventDefault()
    if (!loginForm) return
    const userInput = document.getElementById('loginUser')?.value.trim() || ''
    const password = document.getElementById('loginPassword')?.value || ''
    const errorEl = document.getElementById('loginError')
    if (!errorEl) return
    errorEl.classList.add('d-none')
    if (!userInput || !password) {
      errorEl.textContent = 'Completa usuario y contraseña'
      errorEl.classList.remove('d-none')
      return
    }
    try {
      const response = await apiClient.loginUser({ userInput, password })
      if (response.success && response.user) {
        currentUser = response.user
        updateUIForLoggedInUser(currentUser)
        loginForm.reset()
        const modal = bootstrap.Modal.getInstance(loginModal)
        modal?.hide()
        showNotification(`Bienvenido ${currentUser.name}`, 'success')
        await loadCart()
      } else if (response.error === 'email_sin_verificar') {
        // La cuenta existe pero falta verificar el correo: mostrar el aviso y
        // ofrecer reenviar el enlace.
        errorEl.textContent = response.message || 'Debes verificar tu correo antes de iniciar sesión.'
        errorEl.classList.remove('d-none')
        const email = response.email || userInput
        try {
          await apiClient.resendVerification(email)
          errorEl.textContent += ' Te enviamos un nuevo enlace de verificación, revisa tu bandeja.'
        } catch (err) {
          console.warn('No se pudo reenviar verificación:', err)
        }
      } else {
        errorEl.textContent = response.error || 'Usuario o contraseña incorrectos'
        errorEl.classList.remove('d-none')
      }
    } catch (error) {
      console.error('Error en login:', error)
      errorEl.textContent = error.message || 'Error de autenticación'
      errorEl.classList.remove('d-none')
    }
  }

  function mostrarErrorFirebase(mensaje) {
    const errorEl = document.getElementById('firebaseError')
    if (!errorEl) return
    errorEl.textContent = mensaje || ''
    errorEl.classList.toggle('d-none', !mensaje)
  }

  async function iniciarSesionFirebase(nombre) {
    if (!window.rkFirebase?.disponible) {
      mostrarErrorFirebase('El inicio de sesión externo no está disponible todavía.')
      return
    }
    const boton = document.getElementById(nombre === 'google' ? 'btnGoogle' : 'btnGithub')
    mostrarErrorFirebase(null)
    if (boton) boton.disabled = true
    try {
      const idToken = await window.rkFirebase.iniciarSesion(nombre)
      const response = await apiClient.loginFirebase(idToken)
      if (!response.success || !response.user) {
        throw new Error(response.error || 'No se pudo iniciar sesión')
      }
      currentUser = response.user
      updateUIForLoggedInUser(currentUser)
      bootstrap.Modal.getInstance(loginModal)?.hide()
      showNotification(`Bienvenido ${currentUser.name}`, 'success')
      await loadCart()
    } catch (error) {
      console.error('Error en login con Firebase:', error)
      // Cerrar la ventanita sin elegir proveedor no es un fallo que haya que mostrar.
      if (!error.cancelado) {
        mostrarErrorFirebase(error.message || 'No se pudo iniciar sesión')
      }
    } finally {
      if (boton) boton.disabled = false
    }
  }

  function inicializarFirebaseUI() {
    const bloque = document.getElementById('firebaseLoginBlock')
    if (!bloque || !window.rkFirebase?.disponible) return
    bloque.classList.remove('d-none')
    document.getElementById('btnGoogle')?.addEventListener('click', () => iniciarSesionFirebase('google'))
    document.getElementById('btnGithub')?.addEventListener('click', () => iniciarSesionFirebase('github'))
  }

  async function loadAdminData() {
    await Promise.all([loadUsersTable(), loadProductsTable(), loadOrdersData(), loadExpirationAlerts(), loadPaymentConfigAdmin()])
  }

  // Carga la configuración de pagos en el panel admin
  async function loadPaymentConfigAdmin() {
    const nequiInput = document.getElementById('nequiQrInput')
    const daviplataInput = document.getElementById('daviplataQrInput')
    if (!nequiInput || !daviplataInput) return
    try {
      const res = await apiClient.getPaymentConfig()
      const cfg = (res && res.config) || {}
      nequiInput.value = cfg.nequi_qr || ''
      daviplataInput.value = cfg.daviplata_qr || ''
      renderPaymentPreviews()
    } catch (e) {
      console.error('Error cargando config de pagos:', e)
    }
  }

  function renderPaymentPreviews() {
    const nequi = (document.getElementById('nequiQrInput')?.value || '').trim()
    const daviplata = (document.getElementById('daviplataQrInput')?.value || '').trim()
    const nWrap = document.getElementById('nequiQrPreview')
    const dWrap = document.getElementById('daviplataQrPreview')
    if (nWrap) {
      nWrap.innerHTML = nequi
        ? `<img src="${nequi}" alt="QR Nequi" class="img-thumbnail" style="max-width:160px;">`
        : '<span class="text-muted small">Sin QR configurado</span>'
    }
    if (dWrap) {
      dWrap.innerHTML = daviplata
        ? `<img src="${daviplata}" alt="QR Daviplata" class="img-thumbnail" style="max-width:160px;">`
        : '<span class="text-muted small">Sin QR configurado</span>'
    }
  }

  async function handleSavePaymentConfig(event) {
    event.preventDefault()
    let nequi = (document.getElementById('nequiQrInput')?.value || '').trim()
    let daviplata = (document.getElementById('daviplataQrInput')?.value || '').trim()
    const btn = document.getElementById('savePaymentConfigBtn')
    try {
      if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...' }
      for (const [tipo, id, campo] of [['nequi', 'nequiQrFile', 'nequi_qr'], ['daviplata', 'daviplataQrFile', 'daviplata_qr']]) {
        const file = document.getElementById(id)?.files?.[0]
        if (!file) continue
        const form = new FormData()
        form.append('tipo', tipo)
        form.append(tipo + '_file', file)
        const token = document.querySelector('meta[name="csrf-token"]')?.content || ''
        const upload = await fetch('api.php?action=uploadPaymentQr', {
          method: 'POST',
          headers: token ? { 'X-CSRF-Token': token } : {},
          body: form,
        })
        const uploaded = await upload.json()
        if (!upload.ok || !uploaded.success) throw new Error(uploaded.error || 'No se pudo subir el QR')
        if (campo === 'nequi_qr') nequi = uploaded.url
        else daviplata = uploaded.url
      }
      const res = await apiClient.savePaymentConfig({ nequi_qr: nequi, daviplata_qr: daviplata })
      if (res.success) {
        showNotification('Configuración de pagos guardada', 'success')
        renderPaymentPreviews()
      } else {
        showNotification(res.error || 'No se pudo guardar', 'danger')
      }
    } catch (e) {
      showNotification(e.message || 'Error al guardar', 'danger')
    } finally {
      if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-save me-1"></i>Guardar configuración' }
    }
  }

  async function loadSellerDashboard() {
    await loadProducts()
    await loadSellerOrdersData()
    const sellerTab = document.getElementById('barcode-tab')
    if (sellerTab) {
      sellerTab.click()
    }
  }

  // ===== Paginación genérica para tablas del panel admin =====
  const pagerState = {}
  function setupPager(tbodyId, pagerId, rows, pageSize = 10) {
    pagerState[tbodyId] = { rows, pageSize, page: 0 }
    const tbody = document.getElementById(tbodyId)
    const pager = document.getElementById(pagerId)
    if (!tbody) return
    if (!Array.isArray(rows) || rows.length === 0) {
      tbody.innerHTML = ''
      if (pager) pager.innerHTML = ''
      return
    }
    const render = () => {
      const st = pagerState[tbodyId]
      const totalPages = Math.max(1, Math.ceil(st.rows.length / st.pageSize))
      if (st.page >= totalPages) st.page = totalPages - 1
      if (st.page < 0) st.page = 0
      const start = st.page * st.pageSize
      const slice = st.rows.slice(start, start + st.pageSize)
      tbody.innerHTML = slice.join('')
      if (pager) {
        pager.innerHTML = ''
        const wrap = document.createElement('div')
        wrap.className = 'd-flex justify-content-between align-items-center gap-2 mt-2 flex-wrap'
        const info = document.createElement('span')
        info.className = 'text-muted small'
        info.textContent = `Mostrando ${start + 1}-${Math.min(start + st.pageSize, st.rows.length)} de ${st.rows.length}`
        const nav = document.createElement('div')
        nav.className = 'btn-group btn-group-sm'
        const prev = document.createElement('button')
        prev.className = 'btn btn-outline-secondary'
        prev.innerHTML = '<i class="fa-solid fa-chevron-left"></i>'
        prev.disabled = st.page === 0
        prev.onclick = () => { st.page--; render() }
        const pageInfo = document.createElement('span')
        pageInfo.className = 'btn btn-outline-secondary disabled'
        pageInfo.textContent = `Página ${st.page + 1} / ${totalPages}`
        const next = document.createElement('button')
        next.className = 'btn btn-outline-secondary'
        next.innerHTML = '<i class="fa-solid fa-chevron-right"></i>'
        next.disabled = st.page >= totalPages - 1
        next.onclick = () => { st.page++; render() }
        nav.append(prev, pageInfo, next)
        wrap.append(info, nav)
        pager.append(wrap)
      }
      if (typeof attachOrderWhatsappButtons === 'function') attachOrderWhatsappButtons()
    }
    render()
  }

  async function loadUsersTable() {
    try {
      const users = await apiClient.getUsers()
      const tbody = document.getElementById('usersTableBody')
      if (!tbody || !Array.isArray(users)) return
      const userRows = users
        .map(
          (user) => `
            <tr>
              <td>${user.name}</td>
              <td>${user.email}</td>
              <td>${user.username}</td>
              <td>
                <span class="badge ${user.isAdmin ? 'bg-warning' : user.isSeller ? 'bg-primary' : 'bg-secondary'}">
                  ${user.isAdmin ? 'Administrador' : user.isSeller ? 'Vendedor' : 'Usuario'}
                </span>
              </td>
              <td>${new Date(user.created_at).toLocaleDateString()}</td>
              <td>
                <div class="d-flex flex-wrap align-items-center gap-2">
                  <button class="btn btn-sm ${user.isAdmin ? 'btn-outline-secondary' : 'btn-outline-warning'}" onclick="toggleUserAdmin(${user.id})">
                    <i class="fa-solid ${user.isAdmin ? 'fa-user-minus' : 'fa-user-plus'}"></i>
                    ${user.isAdmin ? 'Quitar Admin' : 'Hacer Admin'}
                  </button>
                  <button class="btn btn-sm ${user.isSeller ? 'btn-outline-secondary' : 'btn-outline-primary'}" onclick="toggleUserSeller(${user.id})">
                    <i class="fa-solid ${user.isSeller ? 'fa-user-minus' : 'fa-user-tag'}"></i>
                    ${user.isSeller ? 'Quitar Vendedor' : 'Hacer Vendedor'}
                  </button>
                  <button class="btn btn-sm btn-outline-danger" onclick="deleteUser(${user.id})">
                    <i class="fa-solid fa-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          `,
        )
      setupPager('usersTableBody', 'usersPager', userRows)
    } catch (error) {
      console.error('Error cargando usuarios:', error)
      showNotification('No se pudieron cargar los usuarios', 'danger')
    }
  }

  async function loadProductsTable(filterTerm) {
    try {
      if (!products.length) await loadProducts()
      const tbody = document.getElementById('productsTableBody')
      if (!tbody) return

      let filteredProducts = products
      if (filterTerm && filterTerm.trim()) {
        const term = filterTerm.trim().toLowerCase()
        filteredProducts = products.filter(p =>
          (p.name || '').toLowerCase().includes(term) ||
          (p.category || '').toLowerCase().includes(term) ||
          (p.barcode || '').toLowerCase().includes(term) ||
          (p.description || '').toLowerCase().includes(term)
        )
      }

      if (filteredProducts.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-4">
          <i class="fa-solid fa-search fa-2x mb-2 d-block opacity-50"></i>
          No se encontraron productos${filterTerm ? ` para "${filterTerm}"` : ''}
        </td></tr>`
        return
      }

      const productRows = filteredProducts
        .map(
          (product) => `
            <tr>
              <td>
                <div class="d-flex align-items-center">
                  ${product.img ? `<img src="${product.img}" alt="${product.name}" style="width: 40px; height: 40px; object-fit: cover; border-radius: 8px; margin-right: 10px;">` : `<div style="width: 40px; height: 40px; background: #f0f0f0; border-radius: 8px; margin-right: 10px; display: flex; align-items: center; justify-content: center; color: #aaa;"><i class="fa-solid fa-image"></i></div>`}
                  <div>
                    <div class="fw-semibold">${product.name}</div>
                    <small class="text-muted">${product.description || 'Sin descripción'}</small>
                  </div>
                </div>
              </td>
              <td><span class="badge bg-primary">${categoryDisplayNames[product.category] || product.category}</span></td>
              <td class="fw-semibold text-success">${formatCurrency(product.price)}</td>
              <td>
                <div class="d-flex align-items-center">
                  <button class="btn btn-sm btn-outline-secondary me-2" onclick="updateProductStock(${product.id}, -1)">
                    <i class="fa-solid fa-minus"></i>
                  </button>
                  <span class="fw-semibold mx-2">${product.stock || 0}</span>
                  <button class="btn btn-sm btn-outline-secondary" onclick="updateProductStock(${product.id}, 1)">
                    <i class="fa-solid fa-plus"></i>
                  </button>
                </div>
              </td>
              <td>
                <button class="btn btn-sm btn-outline-primary me-1" onclick="editProduct(${product.id})">
                  <i class="fa-solid fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteProduct(${product.id})">
                  <i class="fa-solid fa-trash"></i>
                </button>
              </td>
            </tr>
            `,
            )
            setupPager('productsTableBody', 'productsPager', productRows)
    } catch (error) {
      console.error('Error cargando productos admin:', error)
      showNotification('No se pudieron cargar los productos', 'danger')
    }
  }

  window.cancelAdminOrder = async function (orderId) {
    if (!confirm('¿Deseas cancelar este pedido?')) return
    try {
      await apiClient.cancelOrder(orderId)
      showNotification('Pedido cancelado', 'success')
      await loadOrdersData()
    } catch (error) {
      showNotification(error.message || 'No se pudo cancelar el pedido', 'danger')
    }
  }

  async function loadOrdersData() {
    try {
      const result = await apiClient.getOrders(currentUser?.id || null)
      const fetchedOrders = Array.isArray(result) ? result : (Array.isArray(result.orders) ? result.orders : [])
      orders = fetchedOrders
      const container = document.getElementById('ordersContainer')
      const ordersTable = document.getElementById('ordersTableBody')
      const totalSalesEl = document.getElementById('totalSales')
      const totalOrdersEl = document.getElementById('totalOrders')
      const averageTicketEl = document.getElementById('averageTicket')
      const totalProductsSoldEl = document.getElementById('totalProductsSold')
      const dailySalesBody = document.getElementById('dailySalesBody')

      const confirmedOrders = fetchedOrders.filter((order) => order.status === 'entregado')
      const totalSales = confirmedOrders.reduce((sum, order) => sum + (Number(order.total) || 0), 0)
      const totalOrders = confirmedOrders.length
      const totalProductsSold = confirmedOrders.reduce((sum, order) => {
        const normalizedItems = normalizeOrderItems(order)
        return sum + normalizedItems.reduce((sub, item) => sub + (Number(item.quantity) || 0), 0)
      }, 0)
      const averageTicket = totalOrders ? totalSales / totalOrders : 0

      if (totalSalesEl) totalSalesEl.textContent = formatCurrency(totalSales)
      if (totalOrdersEl) totalOrdersEl.textContent = totalOrders
      if (averageTicketEl) averageTicketEl.textContent = formatCurrency(averageTicket)
      if (totalProductsSoldEl) totalProductsSoldEl.textContent = totalProductsSold

      if (ordersTable) {
        if (orders.length === 0) {
          ordersTable.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">No hay pedidos registrados</td></tr>'
        } else {
          const orderRows = orders
            .map(
              (order) => `
                <tr>
                  <td>${order.order_number}</td>
                  <td>${new Date(order.order_date).toLocaleString()}</td>
                  <td>${order.items.length}</td>
                  <td>${formatCurrency(order.total)}</td>
                  <td>${order.payment_method}</td>
                  <td>
                    <span class="badge ${order.status === 'entregado' ? 'bg-success' : order.status === 'cancelado' ? 'bg-danger' : 'bg-warning text-dark'}">
                      ${order.status === 'entregado' ? 'Entregado' : order.status === 'cancelado' ? 'Cancelado' : 'Pendiente'}
                    </span>
                  </td>
                  <td>
                    ${order.status === 'entregado'
                      ? '<button class="btn btn-sm btn-outline-success me-1" disabled title="Entrega confirmada"><i class="fa-solid fa-check"></i></button>'
                      : order.status === 'cancelado'
                        ? '<button class="btn btn-sm btn-outline-danger me-1" disabled title="Pedido cancelado"><i class="fa-solid fa-ban"></i></button>'
                        : `<button class="btn btn-sm btn-success me-1" type="button" title="Confirmar Entrega" onclick="confirmOrderDelivery(${order.id})"><i class="fa-solid fa-check"></i></button>
                           <button class="btn btn-sm btn-outline-danger me-1" type="button" title="Cancelar pedido" onclick="cancelAdminOrder(${order.id})"><i class="fa-solid fa-ban"></i></button>`
                    }
                    <button class="btn btn-sm btn-outline-secondary me-1" type="button" onclick="window.openOrderDetails(${order.id})">Ver</button>
                    <a class="btn btn-sm btn-success" type="button" data-order-wa href="#" data-phone="${(order.deliveryInfo && order.deliveryInfo.phone) || ''}" data-order="${order.order_number || ''}" data-total="${order.total || 0}">
                      <i class="fa-brands fa-whatsapp"></i>
                    </a>
                  </td>
                </tr>
              `,
            )
          setupPager('ordersTableBody', 'ordersPager', orderRows)
        }
      }

      renderSalesSummary(orders)

      if (container) {
        if (orders.length === 0) {
          container.innerHTML = '<div class="text-center text-muted py-4">No hay pedidos registrados</div>'
          return
        }
        container.innerHTML = orders
          .map(
              (order) => `
                <div class="card mb-3">
                  <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Pedido ${order.order_number}</h6>
                    <div>
                      <span class="badge bg-primary me-1">${order.payment_method}</span>
                      <span class="badge ${order.status === 'entregado' ? 'bg-success' : 'bg-warning text-dark'}">
                        ${order.status === 'entregado' ? 'Entregado' : 'Pendiente'}
                      </span>
                    </div>
                  </div>
                  <div class="card-body">
                    <div class="row">
                      <div class="col-md-6">
                        <p><strong>Cliente:</strong> ${order.deliveryInfo?.name || 'N/A'}</p>
                        <p><strong>Teléfono:</strong> ${order.deliveryInfo?.phone || 'N/A'}</p>
                        <p><strong>Dirección:</strong> ${order.deliveryInfo?.address || 'N/A'}</p>
                      </div>
                      <div class="col-md-6">
                        <p><strong>Fecha:</strong> ${new Date(order.order_date).toLocaleString()}</p>
                        <p><strong>Total:</strong> <span class="text-success fw-bold">${formatCurrency(order.total)}</span></p>
                        <p><strong>Productos:</strong> ${order.items.length} artículos</p>
                      </div>
                    </div>
                    <div class="mt-2">
                      <small class="text-muted">${normalizeOrderItems(order).map((item) => `${item.name} (x${item.quantity})`).join(', ')}</small>
                    </div>
                    <div class="mt-3 text-end">
                      ${order.status === 'entregado'
                        ? '<span class="badge bg-success p-2 me-2"><i class="fa-solid fa-check me-1"></i>Entrega Confirmada</span>'
                        : `<button class="btn btn-sm btn-success me-2" type="button" onclick="confirmOrderDelivery(${order.id})">
                             <i class="fa-solid fa-check me-1"></i>Confirmar entrega
                           </button>`
                      }
                      <button class="btn btn-sm btn-outline-secondary" type="button" onclick="window.openOrderDetails(${order.id})">Ver</button>
                    </div>
                  </div>
                </div>
              `,
          )
          .join('')
      }
    } catch (error) {
      console.error('Error cargando órdenes:', error)
      showNotification('No se pudieron cargar los pedidos', 'danger')
    }
  }

  function parseSalesDate(value) {
    if (!value) return null
    const [year, month, day] = value.split('-').map(Number)
    if (!year || !month || !day) return null

    const date = new Date(year, month - 1, day, 0, 0, 0, 0)
    if (Number.isNaN(date.getTime())) return null
    return date
  }

  function getSalesDateKey(orderDate) {
    const date = new Date(orderDate)
    if (Number.isNaN(date.getTime())) return null
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    return `${year}-${month}-${day}`
  }

  function getSalesMonthKey(orderDate) {
    const date = new Date(orderDate)
    if (Number.isNaN(date.getTime())) return null
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    return `${year}-${month}`
  }

  function renderMonthlySummary(ordersList) {
    if (!monthlySummaryEl) return

    const groupedByMonth = {}
    ordersList.forEach((order) => {
      const monthKey = getSalesMonthKey(order.order_date)
      if (!monthKey) return

      if (!groupedByMonth[monthKey]) {
        groupedByMonth[monthKey] = { sales: 0, orders: 0 }
      }

      groupedByMonth[monthKey].sales += Number(order.total) || 0
      groupedByMonth[monthKey].orders += 1
    })

    const sortedMonths = Object.keys(groupedByMonth).sort()

    if (sortedMonths.length === 0) {
      monthlySummaryEl.innerHTML = '<div class="text-center text-muted py-4">No hay datos para mostrar</div>'
      return
    }

    monthlySummaryEl.innerHTML = `
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead>
            <tr>
              <th>Mes</th>
              <th>Ventas</th>
              <th>Pedidos</th>
            </tr>
          </thead>
          <tbody>
            ${sortedMonths
              .map((monthKey) => {
                const [year, month] = monthKey.split('-')
                const monthLabel = new Date(Number(year), Number(month) - 1, 1).toLocaleString('es-CO', {
                  month: 'long',
                  year: 'numeric',
                })

                return `
                  <tr>
                    <td>${monthLabel}</td>
                    <td>${formatCurrency(groupedByMonth[monthKey].sales)}</td>
                    <td>${groupedByMonth[monthKey].orders}</td>
                  </tr>
                `
              })
              .join('')}
          </tbody>
        </table>
      </div>
    `
  }

  async function loadSellerOrdersData() {
    try {
      const sellerId = currentUser?.id || null
      const fetchedOrders = await apiClient.getOrders(sellerId)
      orders = Array.isArray(fetchedOrders) ? fetchedOrders : []
      const container = document.getElementById('ordersContainer')
      const ordersTable = document.getElementById('ordersTableBody')
      if (ordersTable) {
        if (orders.length === 0) {
          ordersTable.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">No hay pedidos registrados para tu cuenta</td></tr>'
        } else {
          ordersTable.innerHTML = orders
            .map(
              (order) => `
                <tr>
                  <td>${order.order_number}</td>
                  <td>${new Date(order.order_date).toLocaleString()}</td>
                  <td>${order.items.length}</td>
                  <td>${formatCurrency(order.total)}</td>
                  <td>${order.payment_method}</td>
                  <td>
                    <span class="badge ${order.status === 'entregado' ? 'bg-success' : order.status === 'cancelado' ? 'bg-danger' : 'bg-warning text-dark'}">
                      ${order.status === 'entregado' ? 'Entregado' : order.status === 'cancelado' ? 'Cancelado' : 'Pendiente'}
                    </span>
                  </td>
                  <td>
                    <button class="btn btn-sm btn-outline-secondary" type="button" onclick="window.openOrderDetails(${order.id})">Ver</button>
                  </td>
                </tr>
              `,
            )
            .join('')
        }
      }
      renderSalesSummary(orders)
      if (container) {
        if (orders.length === 0) {
          container.innerHTML = '<div class="text-center text-muted py-4">No hay pedidos registrados para tu cuenta</div>'
          return
        }
        container.innerHTML = orders
          .map(
            (order) => `
              <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                  <h6 class="mb-0">Pedido ${order.order_number}</h6>
                  <span class="badge bg-success">${order.payment_method}</span>
                </div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-6">
                      <p><strong>Cliente:</strong> ${order.deliveryInfo?.name || 'N/A'}</p>
                      <p><strong>Teléfono:</strong> ${order.deliveryInfo?.phone || 'N/A'}</p>
                      <p><strong>Dirección:</strong> ${order.deliveryInfo?.address || 'N/A'}</p>
                    </div>
                    <div class="col-md-6">
                      <p><strong>Fecha:</strong> ${new Date(order.order_date).toLocaleString()}</p>
                      <p><strong>Total:</strong> <span class="text-success fw-bold">${formatCurrency(order.total)}</span></p>
                      <p><strong>Productos:</strong> ${order.items.length} artículos</p>
                    </div>
                  </div>
                  <div class="mt-2">
                    <small class="text-muted">${normalizeOrderItems(order).map((item) => `${item.name} (x${item.quantity})`).join(', ')}</small>
                  </div>
                  <div class="mt-3 text-end">
                    <button class="btn btn-sm btn-outline-secondary" type="button" onclick="window.openOrderDetails(${order.id})">Ver</button>
                  </div>
                </div>
              </div>
            `,
          )
          .join('')
      }
    } catch (error) {
      console.error('Error cargando órdenes del vendedor:', error)
      showNotification('No se pudieron cargar tus ventas', 'danger')
    }
  }

  function renderSalesSummary(ordersList) {
    const confirmedOrders = (ordersList || []).filter((order) => order.status === 'entregado')
    const groupedByDay = {}
    const groupedByCategory = {}
    let totalSales = 0
    let totalOrdersCount = 0
    let totalProductsSoldCount = 0

    confirmedOrders.forEach((order) => {
      const orderTotal = Number(order.total) || 0
      const orderDateKey = getSalesDateKey(order.order_date) || 'Sin fecha'
      totalSales += orderTotal
      totalOrdersCount += 1
      const itemCount = Array.isArray(order.items)
        ? order.items.reduce((sum, item) => sum + (Number(item.quantity) || 0), 0)
        : 0
      totalProductsSoldCount += itemCount

      if (!groupedByDay[orderDateKey]) {
        groupedByDay[orderDateKey] = { sales: 0, orders: 0 }
      }
      groupedByDay[orderDateKey].sales += orderTotal
      groupedByDay[orderDateKey].orders += 1

      if (Array.isArray(order.items)) {
        order.items.forEach((item) => {
          const category = item.category || 'Sin categoría'
          if (!groupedByCategory[category]) {
            groupedByCategory[category] = { sales: 0, quantity: 0 }
          }
          groupedByCategory[category].sales += Number(item.total) || 0
          groupedByCategory[category].quantity += Number(item.quantity) || 0
        })
      }
    })

    const averageTicket = totalOrdersCount ? totalSales / totalOrdersCount : 0
    if (totalSalesEl) totalSalesEl.textContent = formatCurrency(totalSales)
    if (totalOrdersEl) totalOrdersEl.textContent = totalOrdersCount
    if (averageTicketEl) averageTicketEl.textContent = formatCurrency(averageTicket)
    if (totalProductsSoldEl) totalProductsSoldEl.textContent = totalProductsSoldCount

    if (dailySalesBody) {
      const sortedDays = Object.keys(groupedByDay).sort()
      if (sortedDays.length === 0) {
        dailySalesBody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">No hay datos</td></tr>'
      } else {
        const dailyRows = sortedDays
          .map((day) => {
            const result = groupedByDay[day]
            return `
              <tr>
                <td>${day}</td>
                <td>${formatCurrency(result.sales)}</td>
                <td>${result.orders}</td>
              </tr>
            `
          })
        setupPager('dailySalesBody', 'dailySalesPager', dailyRows)
      }
    }

    if (categorySalesBody) {
      const sortedCategories = Object.keys(groupedByCategory).sort((a, b) => groupedByCategory[b].sales - groupedByCategory[a].sales)
      if (sortedCategories.length === 0) {
        categorySalesBody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">No hay datos</td></tr>'
      } else {
        const categoryRows = sortedCategories
          .map((category) => {
            const result = groupedByCategory[category]
            return `
              <tr>
                <td>${category}</td>
                <td>${formatCurrency(result.sales)}</td>
                <td>${result.quantity}</td>
              </tr>
            `
          })
        setupPager('categorySalesBody', 'categorySalesPager', categoryRows)
      }
    }

    renderMonthlySummary(confirmedOrders)
  }

  function getFilteredOrdersByDate() {
    if (!Array.isArray(orders) || orders.length === 0) return []
    const startDate = parseSalesDate(salesStartDate?.value)
    const endDate = parseSalesDate(salesEndDate?.value)

    if (endDate) {
      endDate.setHours(23, 59, 59, 999)
    }

    if (!startDate && !endDate) return orders

    return orders.filter((order) => {
      if (!order.order_date) return false
      const orderDate = new Date(order.order_date)
      if (Number.isNaN(orderDate.getTime())) return false

      if (startDate && orderDate < startDate) return false
      if (endDate && orderDate > endDate) return false
      return true
    })
  }

  function applySalesFilter() {
    const filteredOrders = getFilteredOrdersByDate()
    renderSalesSummary(filteredOrders)
  }

  function resetSalesFilter() {
    if (salesStartDate) salesStartDate.value = ''
    if (salesEndDate) salesEndDate.value = ''
    renderSalesSummary(orders)
  }

  async function loadExpirationAlerts() {
    if (!Array.isArray(products) || products.length === 0) {
      await loadProducts()
    }

    const now = new Date()
    const twoMonths = new Date(now)
    twoMonths.setDate(twoMonths.getDate() + 60)

    const alertItems = (products || [])
      .map((product) => {
        const expiryDate = product.expiry_date ? new Date(product.expiry_date) : null
        const hasExpiry = expiryDate && !Number.isNaN(expiryDate.getTime())
        const daysRemaining = hasExpiry ? Math.ceil((expiryDate - now) / (1000 * 60 * 60 * 24)) : null
        const stockThreshold = Number.isNaN(Number(product.stock_threshold)) ? 10 : Number(product.stock_threshold)
        const currentStock = Number.isNaN(Number(product.stock)) ? 0 : Number(product.stock)
        const lowStock = currentStock <= stockThreshold
        const expiringSoon = hasExpiry && expiryDate <= twoMonths
        const expired = hasExpiry && expiryDate < now
        let state = 'Sin alerta'
        if (expired) state = 'Vencido'
        else if (hasExpiry && daysRemaining <= 7) state = 'Crítico'
        else if (hasExpiry && daysRemaining <= 30) state = 'Advertencia'
        else if (hasExpiry && daysRemaining <= 60) state = 'Monitoreo'
        if (lowStock) {
          state = state === 'Sin alerta' ? 'Bajo Stock' : `${state} / Bajo Stock`
        }
        return {
          ...product,
          expiryDate,
          daysRemaining,
          lowStock,
          expiringSoon,
          expired,
          state,
          stockThreshold,
        }
      })
      .filter((item) => item.lowStock || item.expiringSoon)

    const counts = {
      expired: 0,
      critical: 0,
      warning: 0,
      monitoring: 0,
      lowStock: 0,
    }

    alertItems.forEach((item) => {
      if (item.expired) counts.expired += 1
      else if (item.daysRemaining !== null && item.daysRemaining <= 7) counts.critical += 1
      else if (item.daysRemaining !== null && item.daysRemaining <= 30) counts.warning += 1
      else if (item.daysRemaining !== null && item.daysRemaining <= 60) counts.monitoring += 1
      if (item.lowStock) counts.lowStock += 1
    })

    if (expiredCountEl) expiredCountEl.textContent = counts.expired
    if (criticalCountEl) criticalCountEl.textContent = counts.critical
    if (warningCountEl) warningCountEl.textContent = counts.warning
    if (monitoringCountEl) monitoringCountEl.textContent = counts.monitoring

    const selectedFilter = alertFilter?.value || 'all'
    const filteredItems = alertItems.filter((item) => {
      switch (selectedFilter) {
        case 'expired':
          return item.expired
        case 'critical':
          return item.daysRemaining !== null && item.daysRemaining <= 7 && item.daysRemaining >= 0
        case 'warning':
          return item.daysRemaining !== null && item.daysRemaining <= 30 && item.daysRemaining >= 8
        case 'monitoring':
          return item.daysRemaining !== null && item.daysRemaining <= 60 && item.daysRemaining >= 31
        case 'all':
        default:
          return true
      }
    })

    if (alertsTableBody) {
      if (filteredItems.length === 0) {
        alertsTableBody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">No hay alertas para mostrar</td></tr>'
      } else {
        const alertRows = filteredItems
          .map((item) => {
            const expiryText = item.expiryDate ? item.expiryDate.toISOString().slice(0, 10) : 'Sin fecha'
            const daysText = item.daysRemaining === null ? 'N/A' : `${item.daysRemaining} día(s)`
            let alertClass = 'bg-secondary'
            let stateDisplay = item.state
            
            if (item.expired) alertClass = 'bg-danger'
            else if (item.daysRemaining !== null && item.daysRemaining <= 7) alertClass = 'bg-danger'
            else if (item.daysRemaining !== null && item.daysRemaining <= 30) alertClass = 'bg-warning'
            else if (item.daysRemaining !== null && item.daysRemaining <= 60) alertClass = 'bg-info'
            
            const stockClass = item.lowStock ? 'bg-danger text-white' : 'bg-success text-white'
            return `
              <tr>
                <td class="fw-semibold">${item.name}</td>
                <td><span class="badge bg-light text-dark">${item.category || 'N/A'}</span></td>
                <td class="text-muted">${item.batch || '-'}</td>
                <td class="fw-semibold">${expiryText}</td>
                <td>${daysText}</td>
                <td><span class="badge ${alertClass}">${stateDisplay}</span></td>
                <td><span class="badge ${stockClass}">Stock: ${item.stock}/${item.stockThreshold}</span></td>
              </tr>
            `
          })
        setupPager('alertsTableBody', 'alertsPager', alertRows)
      }
    }

    if (expirationAlertsContainer) {
      expirationAlertsContainer.innerHTML = filteredItems.length
        ? `<div class="alert alert-info mb-0">Mostrando ${filteredItems.length} productos con alertas de vencimiento o bajo stock.</div>`
        : `<div class="alert alert-secondary mb-0">No hay productos próximos a vencer o con stock bajo.</div>`
    }
  }

  function exportAlerts() {
    if (!Array.isArray(products) || products.length === 0) {
      showNotification('No hay productos para exportar', 'warning')
      return
    }

    const now = new Date()
    const twoMonths = new Date(now)
    twoMonths.setDate(twoMonths.getDate() + 60)

    const alertItems = (products || [])
      .map((product) => {
        const expiryDate = product.expiry_date ? new Date(product.expiry_date) : null
        const hasExpiry = expiryDate && !Number.isNaN(expiryDate.getTime())
        const daysRemaining = hasExpiry ? Math.ceil((expiryDate - now) / (1000 * 60 * 60 * 24)) : null
        const stockThreshold = Number.isNaN(Number(product.stock_threshold)) ? 10 : Number(product.stock_threshold)
        const currentStock = Number.isNaN(Number(product.stock)) ? 0 : Number(product.stock)
        const lowStock = currentStock <= stockThreshold
        const expiringSoon = hasExpiry && expiryDate <= twoMonths
        const expired = hasExpiry && expiryDate < now
        let state = 'Sin alerta'
        if (expired) state = 'Vencido'
        else if (hasExpiry && daysRemaining <= 7) state = 'Crítico'
        else if (hasExpiry && daysRemaining <= 30) state = 'Advertencia'
        else if (hasExpiry && daysRemaining <= 60) state = 'Monitoreo'
        if (lowStock) {
          state = state === 'Sin alerta' ? 'Bajo Stock' : `${state} / Bajo Stock`
        }
        return {
          ...product,
          expiryDate,
          daysRemaining,
          lowStock,
          expiringSoon,
          expired,
          state,
          stockThreshold,
        }
      })
      .filter((item) => item.lowStock || item.expiringSoon)

    if (alertItems.length === 0) {
      showNotification('No hay alertas para exportar', 'warning')
      return
    }

    // Crear CSV
    const headers = ['Producto', 'Categoría', 'Lote', 'Fecha de Vencimiento', 'Días Restantes', 'Estado', 'Stock']
    const rows = alertItems.map((item) => {
      const expiryText = item.expiryDate ? item.expiryDate.toISOString().slice(0, 10) : 'Sin fecha'
      const daysText = item.daysRemaining === null ? 'N/A' : item.daysRemaining
      return [
        item.name,
        item.category || 'N/A',
        item.batch || '-',
        expiryText,
        daysText,
        item.state,
        `${item.stock}/${item.stockThreshold}`,
      ]
    })

    // Convertir a CSV con escaping de comillas
    const csvContent = [
      headers.map((h) => `"${h}"`).join(','),
      ...rows.map((r) => r.map((cell) => `"${String(cell).replace(/"/g, '""')}"`).join(',')),
    ].join('\n')

    // Crear descarga
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
    const link = document.createElement('a')
    const url = URL.createObjectURL(blob)
    const timestamp = new Date().toISOString().slice(0, 19).replace(/:/g, '-')
    link.setAttribute('href', url)
    link.setAttribute('download', `alertas-vencimiento-${timestamp}.csv`)
    link.style.visibility = 'hidden'
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)

    showNotification(`Exportadas ${alertItems.length} alertas a CSV`, 'success')
  }

  function searchByBarcode() {
    const barcode = document.getElementById('barcodeSearchInput')?.value.trim()
    if (!barcode) {
      showNotification('Ingresa un código de barras', 'warning')
      return
    }
    
    const product = products.find(p => p.barcode === barcode)
    const resultContainer = document.getElementById('barcodeScannerResult')
    
    if (!resultContainer) return
    
    if (product) {
      const expiryDate = product.expiry_date ? new Date(product.expiry_date).toLocaleDateString('es-CO') : 'N/A'
      const stockClass = product.stock > product.stock_threshold ? 'text-success' : 'text-danger'
      resultContainer.innerHTML = `
        <div class="card-body">
          <div class="text-center mb-3">
            ${product.img ? `<img src="${product.img}" alt="${product.name}" style="max-height: 120px; object-fit: contain;">` : ''}
          </div>
          <h6 class="fw-bold mb-2">${product.name}</h6>
          <div class="small mb-3">
            <p><strong>Categoría:</strong> ${product.category || 'N/A'}</p>
            <p><strong>Barcode:</strong> ${product.barcode}</p>
            <p><strong>Lote:</strong> ${product.batch || 'N/A'}</p>
            <p><strong>Precio:</strong> ${formatCurrency(product.price)}</p>
            <p><strong>Vencimiento:</strong> ${expiryDate}</p>
            <p><strong>Stock:</strong> <span class="${stockClass} fw-bold">${product.stock} unidades</span></p>
          </div>
          
          <div class="input-group mb-3">
            <span class="input-group-text"><i class="fa-solid fa-box me-2"></i>Cantidad</span>
            <input type="number" class="form-control" id="barcodeQuantityInput" value="1" min="1" max="${product.stock}" placeholder="Cantidad a vender" autofocus>
          </div>
          
          <button class="btn btn-success w-100" id="barcodeSaleBtn">
            <i class="fa-solid fa-credit-card me-2"></i>Registrar Venta
          </button>
          
          <button class="btn btn-secondary w-100 mt-2" onclick="resetBarcodeScanner()">
            <i class="fa-solid fa-arrow-rotate-left me-2"></i>Nuevo Código
          </button>
        </div>
      `
      
      // Set sale button to be called with correct product name
      const saleBtn = document.getElementById('barcodeSaleBtn')
      if (saleBtn) {
        saleBtn.onclick = () => processBarcodeProductSale(product.id, product.name, product.price)
      }
      
      // Focus on quantity input after short delay to allow DOM to render
      setTimeout(() => {
        const quantityInput = document.getElementById('barcodeQuantityInput')
        if (quantityInput) {
          quantityInput.focus()
          quantityInput.select()
        }
      }, 100)
      
      showNotification(`Producto encontrado: ${product.name} - Ingresa cantidad`, 'success')
    } else {
      resultContainer.innerHTML = '<p class="text-danger text-center">Producto no encontrado</p>'
      showNotification('Código de barras no encontrado en el sistema', 'danger')
    }
  }

  async function processBarcodeProductSale(productId, productName, productPrice) {
    const quantityInput = document.getElementById('barcodeQuantityInput')
    if (!quantityInput) return
    
    const quantity = parseInt(quantityInput.value)
    
    if (isNaN(quantity) || quantity <= 0) {
      showNotification('Ingresa una cantidad válida', 'warning')
      return
    }
    
    const product = products.find(p => Number(p.id) === Number(productId))
    if (!product || product.stock < quantity) {
      showNotification('Stock insuficiente para esta venta', 'danger')
      return
    }
    
    try {
      // Crear la orden de venta usando checkout
      const orderResponse = await apiClient.checkout({
        items: [{
          id: productId,
          name: productName,
          category: product.category || 'general',
          price: productPrice,
          quantity: quantity
        }],
        total: productPrice * quantity,
        paymentMethod: 'cash',
        seller_id: currentUser?.id || null,
        deliveryInfo: {
          name: currentUser?.name || 'Tienda Física',
          phone: currentUser?.username || 'N/A',
          address: 'Venta en tienda - Lector de códigos'
        }
      })
      
      if (orderResponse.success) {
        // Actualizar stock local del producto
        product.stock -= quantity
        
        // Mostrar confirmación
        const resultContainer = document.getElementById('barcodeScannerResult')
        if (resultContainer) {
          resultContainer.innerHTML = `
            <div class="text-center py-4">
              <div class="mb-3">
                <i class="fa-solid fa-check-circle text-success" style="font-size: 3rem;"></i>
              </div>
              <h6 class="text-success fw-bold">¡Venta Registrada!</h6>
              <p class="text-muted small mb-1">${productName}</p>
              <p class="text-muted small mb-3">Pedido: <strong>${orderResponse.orderNumber}</strong></p>
              <p class="mb-3">
                <span class="badge bg-info">${quantity} unidad(es)</span>
                <span class="badge bg-success">${formatCurrency(productPrice * quantity)}</span>
              </p>
              <p class="text-muted small">Stock actualizado: <strong>${product.stock} unidades</strong></p>
              <button class="btn btn-primary w-100 mt-3" onclick="resetBarcodeScanner()">
                <i class="fa-solid fa-plus me-2"></i>Siguiente Venta
              </button>
            </div>
          `
        }
        
        showNotification(`✓ Venta registrada: ${quantity}x ${productName} = ${formatCurrency(productPrice * quantity)}`, 'success')
        
        // Limpiar y enfocar input para siguiente lectura
        setTimeout(() => {
          resetBarcodeScanner()
        }, 3000)
      } else {
        showNotification(orderResponse.error || 'No se pudo registrar la venta', 'danger')
      }
    } catch (error) {
      console.error('Error registrando venta:', error)
      showNotification('Error al registrar la venta', 'danger')
    }
  }

  function resetBarcodeScanner() {
    const barcodeInput = document.getElementById('barcodeSearchInput')
    const resultContainer = document.getElementById('barcodeScannerResult')
    
    if (barcodeInput) {
      barcodeInput.value = ''
      barcodeInput.focus()
    }
    
    if (resultContainer) {
      resultContainer.innerHTML = '<p class="text-muted text-center">Escanea un código para ver los detalles</p>'
    }
  }

  window.confirmOrderDelivery = async function (orderId) {
    if (!confirm('¿Deseas confirmar la entrega de este pedido?')) return
    try {
      const response = await apiClient.confirmOrderDelivery(orderId)
      if (response.success) {
        await loadOrdersData()
        showNotification('Entrega del pedido confirmada correctamente', 'success')
      } else {
        showNotification(response.error || 'No se pudo confirmar la entrega', 'danger')
      }
    } catch (error) {
      console.error('Error confirmando entrega:', error)
      showNotification('No se pudo confirmar la entrega', 'danger')
    }
  }

  window.toggleUserAdmin = async function (userId) {
    if (!confirm('¿Deseas cambiar el rol de este usuario?')) return
    try {
      const response = await apiClient.toggleUserAdmin(userId)
      if (response.success) {
        await loadUsersTable()
        showNotification('Permiso actualizado', 'success')
      }
    } catch (error) {
      console.error('Error cambiando admin:', error)
      showNotification('No se pudo cambiar el permiso', 'danger')
    }
  }

  window.toggleUserSeller = async function (userId) {
    if (!confirm('¿Deseas cambiar el rol de este usuario a vendedor?')) return
    try {
      const response = await apiClient.toggleUserSeller(userId)
      if (response.success) {
        await loadUsersTable()
        showNotification('Rol actualizado', 'success')
      }
    } catch (error) {
      console.error('Error cambiando vendedor:', error)
      showNotification('No se pudo cambiar el rol', 'danger')
    }
  }

  window.deleteUser = async function (userId) {
    if (!confirm('¿Deseas eliminar este usuario?')) return
    try {
      const response = await apiClient.deleteUser(userId)
      if (response.success) {
        await loadUsersTable()
        showNotification('Usuario eliminado', 'success')
      }
    } catch (error) {
      console.error('Error eliminando usuario:', error)
      showNotification('No se pudo eliminar el usuario', 'danger')
    }
  }

  window.updateProductStock = async function (productId, change) {
    try {
      const response = await apiClient.updateProductStock(productId, change)
      if (response.success) {
        await loadProducts()
        await loadProductsTable()
        showNotification('Stock actualizado', 'success')
      }
    } catch (error) {
      console.error('Error actualizando stock:', error)
      showNotification('No se pudo actualizar el stock', 'danger')
    }
  }

  window.editProduct = async function (productId) {
    const product = products.find((item) => Number(item.id) === Number(productId))
    if (!product || !productFormCard) return
    productFormTitle.textContent = 'Editar Producto'
    document.getElementById('productName').value = product.name || ''
    document.getElementById('productPrice').value = Number(product.price) || 0
    document.getElementById('productStock').value = Number(product.stock) || 0
    document.getElementById('productCategory').value = product.category || ''
    document.getElementById('productDescription').value = product.description || ''
    document.getElementById('productImage').value = product.img || ''
    document.getElementById('productBarcode').value = product.barcode || ''
    document.getElementById('productBatch').value = product.batch || ''
    document.getElementById('productIngressDate').value = product.ingress_date || ''
    document.getElementById('productExpiryDate').value = product.expiry_date || ''
    document.getElementById('productBatchQuantity').value = Number(product.batch_quantity) || 0
    // Campos de venta fraccionada
    const pf = document.getElementById('productFractionable')
    if (pf) pf.checked = !!product.is_fractionable
    const ff = document.getElementById('fractionFields')
    if (ff) ff.style.display = product.is_fractionable ? 'flex' : 'none'
    document.getElementById('productUnitsPerBox').value = Number(product.units_per_box) || 0
    document.getElementById('productBoxPrice').value = Number(product.box_price) || 0
    document.getElementById('productUnitPrice').value = Number(product.unit_price) || 0
    productFormCard.style.display = 'block'
    productManagementForm.dataset.editingId = productId
    productFormCard.scrollIntoView({ behavior: 'smooth' })
  }

  window.deleteProduct = async function (productId) {
    if (!confirm('¿Deseas eliminar este producto?')) return
    try {
      const response = await apiClient.deleteProduct(productId)
      if (response.success) {
        await loadProducts()
        await loadProductsTable()
        showNotification('Producto eliminado', 'success')
      }
    } catch (error) {
      console.error('Error eliminando producto:', error)
      showNotification('No se pudo eliminar el producto', 'danger')
    }
  }

  const saveProductBtn = document.getElementById('saveProductBtn')
  async function guardarProducto() {
    const btn = saveProductBtn
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Guardando...' }
    const name = document.getElementById('productName').value.trim()
    const price = Number.parseFloat(document.getElementById('productPrice').value)
    const stock = Number.parseInt(document.getElementById('productStock').value)
    const category = document.getElementById('productCategory').value
    const description = document.getElementById('productDescription').value.trim()
    const img = document.getElementById('productImage').value.trim()
    const barcode = document.getElementById('productBarcode').value.trim()
    const batch = document.getElementById('productBatch').value.trim()
    const ingressDate = document.getElementById('productIngressDate').value
    const expiryDate = document.getElementById('productExpiryDate').value
    const batchQuantity = Number.parseInt(document.getElementById('productBatchQuantity').value) || 0
    const isFractionable = document.getElementById('productFractionable')?.checked || false
    const unitsPerBox = isFractionable ? (Number.parseInt(document.getElementById('productUnitsPerBox').value) || 0) : 0
    const boxPrice = isFractionable ? (Number.parseFloat(document.getElementById('productBoxPrice').value) || 0) : 0
    const unitPrice = isFractionable ? (Number.parseFloat(document.getElementById('productUnitPrice').value) || 0) : 0
    const editingId = productManagementForm?.dataset?.editingId
    const productData = {
      name,
      price,
      stock,
      category,
      description,
      img,
      barcode,
      batch,
      ingressDate,
      expiryDate,
      batchQuantity,
      stockThreshold: 10,
      isFractionable,
      unitsPerBox,
      boxPrice,
      unitPrice,
      stockTotalUnits: isFractionable && unitsPerBox > 0 ? (stock * unitsPerBox) : stock,
    }
    try {
      let response
      if (editingId) {
        productData.id = Number(editingId)
        response = await apiClient.updateProduct(productData)
        if (response.success) {
          showNotification('Producto actualizado correctamente', 'success')
        } else {
          showNotification(response.error || 'No se pudo actualizar el producto', 'danger')
          return
        }
      } else {
        response = await apiClient.addProduct(productData)
        if (response.success) {
          showNotification('Producto agregado correctamente', 'success')
        } else {
          showNotification(response.error || 'No se pudo agregar el producto', 'danger')
          return
        }
      }
      productFormCard.style.display = 'none'
      productManagementForm.reset()
      delete productManagementForm.dataset.editingId
      await loadProducts()
      await loadProductsTable()
    } catch (error) {
      console.error('Error guardando producto:', error)
      const msg = (error && error.message) ? error.message : 'Error desconocido al guardar'
      showNotification('Error al guardar: ' + msg, 'danger')
    } finally {
      if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-save me-1"></i>Guardar Producto' }
    }
  }
  saveProductBtn?.addEventListener('click', guardarProducto)

  addProductBtn?.addEventListener('click', () => {
    if (!productFormCard) return
    productFormTitle.textContent = 'Agregar Nuevo Producto'
    productManagementForm.reset()
    productFormCard.style.display = 'block'
    delete productManagementForm.dataset.editingId
    const ff = document.getElementById('fractionFields')
    if (ff) ff.style.display = 'none'
    const pf = document.getElementById('productFractionable')
    if (pf) pf.checked = false
  })

  // Toggle de campos de venta fraccionada en el formulario de producto
  const productFractionableToggle = document.getElementById('productFractionable')
  const fractionFieldsEl = document.getElementById('fractionFields')
  productFractionableToggle?.addEventListener('change', () => {
    if (fractionFieldsEl) {
      fractionFieldsEl.style.display = productFractionableToggle.checked ? 'flex' : 'none'
    }
  })

  cancelProductForm?.addEventListener('click', () => {
    if (!productFormCard) return
    productFormCard.style.display = 'none'
    productManagementForm.reset()
    delete productManagementForm.dataset.editingId
  })

  function performSearch() {
    const searchTerm = searchInput?.value.trim().toLowerCase() || ''
    if (!searchTerm) {
      showNotification('Ingresa un término de búsqueda', 'warning')
      return
    }
    const results = products.filter(
      (product) =>
        product.name.toLowerCase().includes(searchTerm) ||
        (product.description || '').toLowerCase().includes(searchTerm) ||
        (product.category || '').toLowerCase().includes(searchTerm),
    )
    if (results.length === 0) {
      showNotification(`No se encontraron resultados para "${searchTerm}"`, 'info')
      return
    }
    displaySearchResults(results, searchTerm)
  }

  function displaySearchResults(results, searchTerm) {
    const categoryTitle = document.getElementById('categoryTitle')
    if (categoryTitle) {
      categoryTitle.innerHTML = `Resultados de búsqueda: "${searchTerm}" <span class="badge bg-primary">${results.length}</span>`
    }
    if (!categoryProductsGrid || !categoryProductsSection) return
    categoryProductsGrid.innerHTML = results
      .map(
        (product) => `
          <div class="col-6 col-md-4 col-lg-3">
            <div class="card product-card h-100 cursor-pointer" style="cursor: pointer;" data-product-id="${product.id}">
              ${productImgHtml(product.img, product.name, 'card-img-top', product.category, product.id)}
              <div class="card-body d-flex flex-column">
                <h6 class="card-title">${product.name}</h6>
                <p class="text-primary fw-bold">${formatCurrency(product.price)}</p>
                <div class="d-flex gap-2 mt-auto">
                  <button class="btn btn-primary btn-sm flex-grow-1" onclick="event.stopPropagation(); addToCart(${product.id})">
                    <i class="fa-solid fa-cart-plus me-1"></i> Agregar
                  </button>
                  <button class="btn btn-outline-primary btn-sm" onclick="event.stopPropagation(); showProductDetail(${product.id})" title="Ver detalles">
                    <i class="fa-solid fa-eye"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
        `,
      )
      .join('')
    categoryProductsSection.style.display = 'block'
    categoryProductsSection.scrollIntoView({ behavior: 'smooth' })
  }

  function showCategoryProducts(category) {
    if (!categoryProductsGrid || !categoryProductsSection) return
    const items = products.filter((product) => product.category === category)
    const categoryTitle = document.getElementById('categoryTitle')
    if (categoryTitle) {
      categoryTitle.textContent = categoryDisplayNames[category] || category
    }
    if (items.length === 0) {
      categoryProductsGrid.innerHTML = '<div class="col-12 text-center text-muted">No hay productos en esta categoría.</div>'
    } else {
      categoryProductsGrid.innerHTML = items
        .map(
          (product) => `
            <div class="col-6 col-md-4 col-lg-3">
              <div class="card product-card h-100 cursor-pointer" style="cursor: pointer;" data-product-id="${product.id}">
                ${productImgHtml(product.img, product.name, 'card-img-top', product.category, product.id)}
                <div class="card-body d-flex flex-column">
                  <h6 class="card-title">${product.name}</h6>
                  <p class="text-primary fw-bold">${formatCurrency(product.price)}</p>
                  <div class="d-flex gap-2 mt-auto">
                    <button class="btn btn-primary btn-sm flex-grow-1" onclick="event.stopPropagation(); addToCart(${product.id})">
                      <i class="fa-solid fa-cart-plus me-1"></i> Agregar
                    </button>
                    <button class="btn btn-outline-primary btn-sm" onclick="event.stopPropagation(); showProductDetail(${product.id})" title="Ver detalles">
                      <i class="fa-solid fa-eye"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          `,
        )
        .join('')
    }
    categoryProductsSection.style.display = 'block'
    categoryProductsSection.scrollIntoView({ behavior: 'smooth' })
  }

  function initEventListeners() {
    searchBtn?.addEventListener('click', (e) => {
      e.preventDefault()
      performSearch()
    })
    searchInput?.addEventListener('keypress', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault()
        performSearch()
      }
    })
    accountBtn?.addEventListener('click', (e) => {
      if (!currentUser) {
        e.preventDefault()
        showLoginModal()
      }
    })
    document.getElementById('switchToRegister')?.addEventListener('click', (e) => {
      e.preventDefault()
      const modal = bootstrap.Modal.getInstance(loginModal)
      modal?.hide()
      setTimeout(() => {
        let regModal = bootstrap.Modal.getInstance(registerModal)
        if (!regModal) regModal = new bootstrap.Modal(registerModal)
        regModal.show()
      }, 300)
    })
    document.getElementById('switchToLogin')?.addEventListener('click', (e) => {
      e.preventDefault()
      const modal = bootstrap.Modal.getInstance(registerModal)
      modal?.hide()
      setTimeout(() => {
        let logModal = bootstrap.Modal.getInstance(loginModal)
        if (!logModal) logModal = new bootstrap.Modal(loginModal)
        logModal.show()
      }, 300)
    })
    registerPassword?.addEventListener('input', (e) => updatePasswordRules(e.target.value))
    loginForm?.addEventListener('submit', handleLogin)
    // El módulo ES corre después que este script: puede que ya esté listo, o que no.
    if (window.rkFirebase) inicializarFirebaseUI()
    else document.addEventListener('rk-firebase-ready', inicializarFirebaseUI)
    registerForm?.addEventListener('submit', handleRegister)
    document.getElementById('btnReenviarCorreo')?.addEventListener('click', handleReenviarCorreo)
    registerModal?.addEventListener('hidden.bs.modal', ocultarPanelReenvio)
    document.getElementById('editProfileForm')?.addEventListener('submit', handleEditProfileSubmit)
    document.getElementById('refreshMyOrdersBtn')?.addEventListener('click', loadMyOrders)
    closeCategoryBtn?.addEventListener('click', () => {
      if (categoryProductsSection) categoryProductsSection.style.display = 'none'
    })
    aboutMenuBtn?.addEventListener('click', () => {
      aboutSidebar?.classList.add('active')
      aboutSidebarOverlay?.classList.add('active')
      document.body.style.overflow = 'hidden'
    })
    closeAboutBtn?.addEventListener('click', () => {
      aboutSidebar?.classList.remove('active')
      aboutSidebarOverlay?.classList.remove('active')
      document.body.style.overflow = ''
    })
    aboutSidebarOverlay?.addEventListener('click', () => {
      aboutSidebar?.classList.remove('active')
      aboutSidebarOverlay?.classList.remove('active')
      document.body.style.overflow = ''
    })
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && aboutSidebar?.classList.contains('active')) {
        aboutSidebar?.classList.remove('active')
        aboutSidebarOverlay?.classList.remove('active')
        document.body.style.overflow = ''
      }
    })
    document.querySelectorAll('.category-card').forEach((card) => {
      card.addEventListener('click', function () {
        const category = this.getAttribute('data-category')
        if (category) showCategoryProducts(category)
      })
    })
    document.getElementById('proceedToCheckout')?.addEventListener('click', showCheckoutModal)
    confirmOrderBtn?.addEventListener('click', handleCheckout)
    filterSalesBtn?.addEventListener('click', (e) => {
      e.preventDefault()
      applySalesFilter()
    })
    salesStartDate?.addEventListener('change', () => applySalesFilter())
    salesEndDate?.addEventListener('change', () => applySalesFilter())
    resetSalesBtn?.addEventListener('click', (e) => {
      e.preventDefault()
      resetSalesFilter()
    })
    refreshAlertsBtn?.addEventListener('click', (e) => {
      e.preventDefault()
      loadExpirationAlerts()
    })
    exportAlertsBtn?.addEventListener('click', (e) => {
      e.preventDefault()
      exportAlerts()
    })
    alertFilter?.addEventListener('change', () => loadExpirationAlerts())
    
    // Admin product search listeners
    let adminSearchTimeout = null
    adminProductSearch?.addEventListener('input', () => {
      clearTimeout(adminSearchTimeout)
      adminSearchTimeout = setTimeout(() => {
        loadProductsTable(adminProductSearch.value)
      }, 300)
    })
    adminProductSearchClear?.addEventListener('click', () => {
      if (adminProductSearch) adminProductSearch.value = ''
      loadProductsTable()
    })
    
    // Barcode Scanner listeners
    document.getElementById('barcodeScanBtn')?.addEventListener('click', searchByBarcode)
    document.getElementById('barcodeSearchInput')?.addEventListener('keypress', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault()
        searchByBarcode()
      }
    })
    
    // Add Enter key listener for quantity input
    document.addEventListener('keypress', (e) => {
      if (e.key === 'Enter' && e.target.id === 'barcodeQuantityInput') {
        e.preventDefault()
        const saleBtn = document.getElementById('barcodeSaleBtn')
        if (saleBtn) saleBtn.click()
      }
    })
    
    document.querySelectorAll('input[name="paymentMethod"]').forEach((method) => {
      method.addEventListener('change', function () {
        if (cardDetails) cardDetails.style.display = this.value === 'card' ? 'block' : 'none'
        if (transferDetails) transferDetails.style.display = this.value === 'transfer' ? 'block' : 'none'
        if (paypalDetails) paypalDetails.style.display = this.value === 'paypal' ? 'block' : 'none'
        updateQrPanel(this.value)
      })
    })

    // Panel admin: configuración de métodos de pago (QR)
    const paymentConfigForm = document.getElementById('paymentConfigForm')
    if (paymentConfigForm) {
      paymentConfigForm.addEventListener('submit', handleSavePaymentConfig)
    }
    const previewPaymentBtn = document.getElementById('previewPaymentConfigBtn')
    if (previewPaymentBtn) {
      previewPaymentBtn.addEventListener('click', renderPaymentPreviews)
    }

    // Botones de cantidad en modal de detalles
    document.getElementById('decreaseQty')?.addEventListener('click', () => {
      const input = document.getElementById('productQty')
      const value = Number.parseInt(input.value) || 1
      if (value > 1) input.value = value - 1
    })
    document.getElementById('increaseQty')?.addEventListener('click', () => {
      const input = document.getElementById('productQty')
      const value = Number.parseInt(input.value) || 1
      if (value < 99) input.value = value + 1
    })

    // Botón agregar al carrito desde modal
    document.getElementById('addToCartFromModal')?.addEventListener('click', async () => {
      const productId = document.getElementById('addToCartFromModal').dataset.productId
      const quantity = Number.parseInt(document.getElementById('productQty').value) || 1
      const product = products.find((p) => Number(p.id) === Number(productId))
      
      if (!product) {
        showNotification('Producto no encontrado', 'warning')
        return
      }

      // Determinar presentación (caja/unidad) para productos fraccionables
      let tipoVenta = 'unidad'
      let unitPrice = product.price
      if (product.is_fractionable && product.units_per_box > 0) {
        const pres = document.getElementById('addToCartFromModal').dataset.presentation || 'caja'
        tipoVenta = pres === 'caja' ? 'caja' : 'unidad'
        unitPrice = tipoVenta === 'caja' ? (product.box_price || product.price) : (product.unit_price || product.price)
      }

      // Usar addToCart (ya valida stock disponible)
      await addToCart(productId, quantity, { tipoVenta, price: unitPrice })
      // Cerrar modal si se agregó correctamente
      const modal = bootstrap.Modal.getInstance(document.getElementById('productDetailModal'))
      modal?.hide()
    })
  }

  window.showNotification = showNotification
  window.showCategoryProducts = showCategoryProducts
  window.openOrderDetails = function (orderId) {
    const order = orders.find((o) => Number(o.id) === Number(orderId))
    if (!order) {
      showNotification('Pedido no encontrado', 'danger')
      return
    }

    document.getElementById('orderDetailNumber').textContent = order.order_number || 'N/A'
    document.getElementById('orderDetailDate').textContent = order.order_date ? new Date(order.order_date).toLocaleString('es-CO') : 'N/A'
    document.getElementById('orderDetailTotal').textContent = formatCurrency(order.total)
    document.getElementById('orderDetailPayment').textContent = order.payment_method || 'N/A'
    document.getElementById('orderDetailDeliveryInfo').textContent = order.deliveryInfo && typeof order.deliveryInfo === 'object' && Object.keys(order.deliveryInfo).length
      ? Object.entries(order.deliveryInfo).map(([key, value]) => `${key}: ${value}`).join(' · ')
      : 'No disponible'

    const itemsBody = document.getElementById('orderDetailItemsBody')
    const normalizedItems = normalizeOrderItems(order)
    if (itemsBody) {
      itemsBody.innerHTML = normalizedItems.length > 0
        ? normalizedItems.map((item) => `
            <tr>
              <td>
                ${item.product_id ? `<button class="btn btn-link p-0 text-start text-decoration-none" onclick="window.showProductDetail(${item.product_id})">${item.name}</button>` : item.name}
              </td>
              <td>${item.category || '-'}</td>
              <td>${item.quantity}</td>
              <td>${formatCurrency(item.price)}</td>
              <td>${formatCurrency(item.total)}</td>
            </tr>
          `).join('')
        : '<tr><td colspan="5" class="text-center text-muted">No hay artículos registrados</td></tr>'
    }

    let modal = window.bootstrap.Modal.getInstance(document.getElementById('orderDetailModal'))
    if (!modal) modal = new window.bootstrap.Modal(document.getElementById('orderDetailModal'))
    modal.show()
  }

  async function init() {
    initSwipers()
    initEventListeners()
    await loadProducts()
    await checkAuthStatus()
    await loadCart()
  }

  await init()
}

// Adjust CSS header offset variable so sticky table headers align with fixed header
document.addEventListener('DOMContentLoaded', () => {
  function setHeaderOffset() {
    const header = document.querySelector('.site-header')
    const offset = header ? header.offsetHeight + 8 : 72
    document.documentElement.style.setProperty('--header-offset', offset + 'px')
  }

  setHeaderOffset()
  window.addEventListener('resize', setHeaderOffset)

  // Smart header hide on scroll down (especially on mobile/responsive views)
  let lastScrollY = Math.max(0, window.scrollY || document.documentElement.scrollTop)
  let scrollTicking = false

  function handleHeaderScroll() {
    const header = document.querySelector('.site-header')
    if (!header) {
      scrollTicking = false
      return
    }

    // Do not hide header if a modal is open or search input is focused
    const isModalOpen = document.body.classList.contains('modal-open')
    const searchInput = document.getElementById('searchInput')
    const isSearchFocused = searchInput && document.activeElement === searchInput

    if (isModalOpen || isSearchFocused) {
      header.classList.remove('header-hidden')
      scrollTicking = false
      return
    }

    const currentScrollY = Math.max(0, window.scrollY || document.documentElement.scrollTop)
    const delta = currentScrollY - lastScrollY

    if (currentScrollY <= 50) {
      header.classList.remove('header-hidden')
    } else if (delta > 8 && currentScrollY > 80) {
      header.classList.add('header-hidden')
    } else if (delta < -5) {
      header.classList.remove('header-hidden')
    }

    lastScrollY = currentScrollY
    scrollTicking = false
  }

  window.addEventListener('scroll', () => {
    if (!scrollTicking) {
      window.requestAnimationFrame(handleHeaderScroll)
      scrollTicking = true
    }
  }, { passive: true })
})

// --- Chat Widget ---
function initChat() {
  const fab = document.getElementById('chatFab')
  const chatWin = document.getElementById('chatWindow')
  const form = document.getElementById('chatForm')
  const input = document.getElementById('chatInput')
  const messages = document.getElementById('chatMessages')
  const close = document.getElementById('chatCloseBtn')
  let history = []

  fab.addEventListener('click', () => {
    chatWin.classList.toggle('open')
    if (chatWin.classList.contains('open')) {
      messages.scrollTop = messages.scrollHeight
    }
  })

  close.addEventListener('click', () => chatWin.classList.remove('open'))

  form.addEventListener('submit', async (e) => {
    e.preventDefault()
    const text = input.value.trim()
    if (!text) return

    // User message
    const userBubble = document.createElement('div')
    userBubble.className = 'chat-message user'
    userBubble.innerHTML = '<div class="chat-bubble">' + escapeHtml(text) + '</div>'
    messages.appendChild(userBubble)
    messages.scrollTop = messages.scrollHeight
    history.push({ role: 'user', content: text })
    input.value = ''
    input.disabled = true

    // Typing indicator
    const typing = document.createElement('div')
    typing.className = 'chat-message bot'
    typing.id = 'chatTyping'
    typing.innerHTML = '<div class="chat-bubble typing"><span></span><span></span><span></span></div>'
    messages.appendChild(typing)
    messages.scrollTop = messages.scrollHeight

    try {
      const res = await fetch('chat.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: text, history: history.slice(0, -1) }),
      })
      const data = await res.json()
      document.getElementById('chatTyping')?.remove()

      const botBubble = document.createElement('div')
      botBubble.className = 'chat-message bot'
      if (data.success) {
        botBubble.innerHTML = '<div class="chat-bubble">' + escapeHtml(data.reply) + '</div>'
        history.push({ role: 'assistant', content: data.reply })
      } else {
        botBubble.innerHTML = '<div class="chat-bubble error">Error: ' + escapeHtml(data.error || 'No se pudo generar respuesta') + '</div>'
      }
      messages.appendChild(botBubble)
    } catch (err) {
      document.getElementById('chatTyping')?.remove()
      const errBubble = document.createElement('div')
      errBubble.className = 'chat-message bot'
      errBubble.innerHTML = '<div class="chat-bubble error">Error de conexión. Intenta de nuevo.</div>'
      messages.appendChild(errBubble)
    }

    input.disabled = false
    input.focus()
    messages.scrollTop = messages.scrollHeight
  })
}

function escapeHtml(str) {
  const div = document.createElement('div')
  div.textContent = str
  return div.innerHTML
}

document.addEventListener('DOMContentLoaded', initChat)
document.addEventListener('DOMContentLoaded', initFooterLinks)

// Conecta los enlaces del footer a funcionalidades reales
function initFooterLinks() {
  const chatWin = document.getElementById('chatWindow')
  const openChat = () => {
    if (chatWin) chatWin.classList.add('open')
  }

  const bind = (id, handler) => {
    const el = document.getElementById(id)
    if (el) {
      el.addEventListener('click', (e) => {
        e.preventDefault()
        handler()
      })
    }
  }

  // Chat en Vivo y soporte -> abren el chat en vivo
  bind('footerLiveChat', openChat)
  bind('footerHelpCenter', openChat)
  bind('footerTechSupport', openChat)
  bind('footerServiceStatus', openChat)

  // Preguntas Frecuentes -> modal FAQ
  bind('footerFaq', () => {
    const modalEl = document.getElementById('faqModal')
    if (modalEl) {
      let m = bootstrap.Modal.getInstance(modalEl)
      if (!m) m = new bootstrap.Modal(modalEl)
      m.show()
    }
  })

  // ===== Integración WhatsApp =====
  const WA_NUMERO = '573115631854' // debe coincidir con config.php whatsapp.numero

  const formatCurrency = (n) => {
    const v = Number(n) || 0
    return '$' + v.toLocaleString('es-CO', { maximumFractionDigits: 0 })
  }

  const buildWaLink = (numero, texto) => {
    const url = 'https://wa.me/' + numero + '?text=' + encodeURIComponent(texto)
    return url
  }

  const openWaLink = (numero, texto) => {
    const url = buildWaLink(numero, texto)
    const w = window.open('', '_blank')
    if (w) { w.location.href = url } else { window.location.href = url }
    return url
  }

  const buildAlertText = (data) => {
    let txt = `📋 *ALERTAS DE INVENTARIO - SERVIFARMACIA RK*\n\n`
    const bs = data.bajo_stock || []
    const pv = data.por_vencer || []
    if (bs.length) {
      txt += `*🔴 BAJO STOCK (umbral < ${data.umbral ?? 5} unidades):*\n`
      bs.forEach((p) => { txt += `- ${p.name}: ${p.stock} uds (${p.category})\n` })
      txt += `\n`
    } else {
      txt += `*✅ Sin productos con bajo stock.*\n\n`
    }
    if (pv.length) {
      txt += `*⏰ POR VENCER (próximos ${data.dias ?? 30} días):*\n`
      pv.forEach((p) => { txt += `- ${p.name}: vence ${p.expiry_date} (${p.stock} uds)\n` })
    } else {
      txt += `*✅ Sin productos por vencer próximamente.*\n`
    }
    return txt
  }

  // (c) Cliente confirma su pedido por WhatsApp hacia la farmacia
  const confirmOrderByWhatsapp = document.getElementById('confirmOrderByWhatsapp')
  if (confirmOrderByWhatsapp) {
    confirmOrderByWhatsapp.addEventListener('click', (e) => {
      const o = window.__lastOrder
      if (!o) return
      let txt = `¡Hola SERVIFARMACIA RK! Quiero confirmar mi pedido *${o.orderNumber}*.\n\n`
      txt += `*Datos de entrega:*\n`
      txt += `Nombre: ${o.deliveryInfo?.name || 'N/A'}\n`
      txt += `Teléfono: ${o.deliveryInfo?.phone || 'N/A'}\n`
      txt += `Dirección: ${o.deliveryInfo?.address || 'N/A'}\n`
      txt += `Método de pago: ${o.paymentMethod || 'N/A'}\n\n`
      txt += `*Productos:*\n`
      ;(o.items || []).forEach((it) => {
        txt += `- ${it.name} x${it.quantity} = ${formatCurrency(it.price * it.quantity)}\n`
      })
      txt += `\n*Total:* ${formatCurrency(o.total)}`
      openWaLink(WA_NUMERO, txt)
    })
  }

  // Cargar alertas y armar botón "Notificar a la farmacia por WhatsApp"
  const loadInventoryAlerts = async () => {
    try {
      const res = await apiClient.getInventoryAlerts ? apiClient.getInventoryAlerts() : apiClient.request('obtenerAlertasInventario')
      if (!res || !res.success) return null
      return res
    } catch (err) {
      return null
    }
  }

  const notifyAlertsByWhatsapp = document.getElementById('notifyAlertsByWhatsapp')
  if (notifyAlertsByWhatsapp) {
    notifyAlertsByWhatsapp.addEventListener('click', async (e) => {
      e.preventDefault()
      const w = window.open('', '_blank')
      const data = await loadInventoryAlerts()
      const url = data ? buildAlertText(data) : buildWaLink(WA_NUMERO, 'Hola, quiero consultar las alertas de inventario.')
      if (w) { w.location.href = url } else { window.location.href = url }
    })
  }

  // (c) Desde el panel de pedidos: la farmacia confirma/envía estado al cliente por WhatsApp
  const attachOrderWhatsappButtons = () => {
    document.querySelectorAll('[data-order-wa]').forEach((btn) => {
      if (btn.dataset.bound) return
      btn.dataset.bound = '1'
      btn.addEventListener('click', (e) => {
        e.preventDefault()
        const phone = (btn.dataset.phone || '').replace(/[^0-9]/g, '')
        const orderNum = btn.dataset.order || ''
        const total = btn.dataset.total || ''
        if (!phone) { alert('El pedido no tiene teléfono del cliente.'); return }
        const txt = `¡Hola! Soy de *SERVIFARMACIA RK*. Confirmamos su pedido *${orderNum}* (total ${formatCurrency(total)}). Nos comunicaremos para coordinar la entrega. ¡Gracias!`
        openWaLink('57' + phone, txt)
      })
    })
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', appInit)
} else {
  appInit()
}
