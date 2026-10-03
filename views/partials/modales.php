<div class="offcanvas offcanvas-end rk-cart-drawer" tabindex="-1" id="cartDrawer" aria-labelledby="cartDrawerLabel">
  <div class="offcanvas-header border-bottom">
    <div><span class="section-eyebrow">Tu selección</span><h2 class="offcanvas-title h4 mb-0" id="cartDrawerLabel"><i class="fa-solid fa-cart-shopping me-2 text-primary"></i>Tu carrito <span id="cartDrawerCount" class="badge bg-primary rounded-pill">0</span></h2></div>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar carrito"></button>
  </div>
  <div class="offcanvas-body d-flex flex-column">
    <div class="rk-shipping-progress mb-3" aria-live="polite"><div class="d-flex justify-content-between small mb-1"><span id="shippingMessage">Agrega productos para calcular el envío</span><strong id="shippingProgressValue">$0</strong></div><div class="progress" role="progressbar" aria-label="Progreso para envío gratis" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><div id="shippingProgressBar" class="progress-bar"></div></div></div>
    <div id="cartDrawerEmpty" class="text-center py-5 rk-empty-state"><i class="fa-solid fa-basket-shopping fa-3x mb-3"></i><p class="mb-1">Tu carrito está vacío</p><small>Agrega productos para verlos aquí.</small></div>
    <div id="cartDrawerItems" class="cart-items-list flex-grow-1"></div>
    <div class="rk-cart-summary border-top pt-3 mt-3"><div class="d-flex justify-content-between align-items-center"><span>Subtotal</span><strong id="cartDrawerTotal" class="fs-4 text-primary">$0</strong></div><button type="button" class="btn btn-primary w-100 mt-3" id="proceedToCheckoutDrawer"><i class="fa-solid fa-arrow-right me-2"></i>Proceder al pago</button></div>
  </div>
</div>

<div class="modal fade" id="cartModal" tabindex="-1" aria-labelledby="cartModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl modal-cart">
    <div class="modal-content cart-modal-content">
      <div class="modal-header cart-modal-header">
        <h5 class="modal-title d-flex align-items-center gap-2" id="cartModalLabel">
          <i class="fa-solid fa-cart-shopping text-primary"></i>
          Tu Carrito
          <span class="badge bg-primary rounded-pill" id="cartCountBadge">0</span>
        </h5>
        <button class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body cart-modal-body">
        <div id="cartEmpty" class="text-center py-5">
          <i class="fa-solid fa-cart-shopping fa-3x text-muted mb-3"></i>
          <p class="text-muted mb-0">Tu carrito está vacío</p>
          <small class="text-muted">Agrega productos para verlos aquí</small>
        </div>
        <div id="cartItems" class="cart-items-list"></div>
      </div>
      <div class="modal-footer cart-modal-footer justify-content-between">
        <div class="cart-total-wrap">
          <span class="text-muted small">Total</span>
          <strong class="cart-total-amount" id="cartTotal">$0</strong>
        </div>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Seguir comprando</button>
          <button type="button" class="btn btn-primary btn-lg" id="proceedToCheckout">
            <i class="fa-solid fa-credit-card me-1"></i> Proceder al Pago
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="checkoutModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5>Finalizar Compra</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-5">
            <h6 class="mb-3">Resumen del Pedido</h6>
            <div id="checkoutItems" class="mb-3"></div>
            <hr>
            <div class="d-flex justify-content-between">
              <strong>Total: <span id="checkoutTotal">$0</span></strong>
            </div>
          </div>
          <div class="col-md-7">
            <form id="checkoutForm">
              <h6 class="mb-3">Información de Entrega</h6>
              <div class="row mb-3">
                <div class="col-md-6">
                  <label for="deliveryName" class="form-label">Nombre Completo</label>
                  <input type="text" class="form-control" id="deliveryName" required>
                </div>
                <div class="col-md-6">
                  <label for="deliveryPhone" class="form-label">Teléfono</label>
                  <input type="tel" class="form-control" id="deliveryPhone" required>
                </div>
              </div>
              <div class="mb-3">
                <label for="deliveryAddress" class="form-label">Dirección de Entrega</label>
                <textarea class="form-control" id="deliveryAddress" rows="2" required></textarea>
              </div>
              <h6 class="mb-3">Método de Pago</h6>
              <div class="payment-methods">
                <div class="form-check mb-3">
                  <input class="form-check-input" type="radio" name="paymentMethod" id="payCash" value="cash" checked>
                  <label class="form-check-label" for="payCash">
                    <i class="fa-solid fa-money-bill text-success me-2"></i>Efectivo
                    <small class="text-muted d-block">Paga en efectivo al recibir tu pedido</small>
                  </label>
                </div>
                <div class="form-check mb-3">
                  <input class="form-check-input" type="radio" name="paymentMethod" id="payNequi" value="nequi">
                  <label class="form-check-label" for="payNequi">
                    <i class="fa-solid fa-mobile-screen text-primary me-2"></i>Nequi
                    <small class="text-muted d-block">Paga escaneando nuestro código QR</small>
                  </label>
                </div>
                <div class="form-check mb-3">
                  <input class="form-check-input" type="radio" name="paymentMethod" id="payDaviplata" value="daviplata">
                  <label class="form-check-label" for="payDaviplata">
                    <i class="fa-solid fa-wallet text-info me-2"></i>Daviplata
                    <small class="text-muted d-block">Paga escaneando nuestro código QR</small>
                  </label>
                </div>
              </div>
              <div id="qrDetails" class="qr-details" style="display: none;">
                <div class="alert alert-light border text-center">
                  <h6 class="mb-2"><i class="fa-solid fa-qrcode me-2"></i><span id="qrMethodName">Escanea para pagar</span></h6>
                  <div id="qrImageWrap" class="mb-2">
                    <img id="qrImage" src="" alt="Código QR de pago" class="img-fluid rounded" style="max-width: 220px; display: none;">
                  </div>
                  <p id="qrEmpty" class="text-muted small mb-0">Aún no hay un código QR configurado. Contáctanos para coordinar el pago.</p>
                  <p class="small text-muted mb-0 mt-2">Escanea con la app de <strong id="qrAppName">tu billetera</strong> y envíanos el comprobante por WhatsApp.</p>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success" id="confirmOrder">
          <i class="fa-solid fa-check me-2"></i>Confirmar Pedido
        </button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="orderConfirmationModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5><i class="fa-solid fa-check-circle me-2"></i>¡Pedido Confirmado!</h5>
        <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <div class="mb-3">
          <i class="fa-solid fa-check-circle fa-4x text-success mb-3"></i>
          <h6>Tu pedido ha sido procesado exitosamente</h6>
          <p class="text-muted">Número de pedido: <strong id="orderNumber">#12345</strong></p>
        </div>
        <div class="alert alert-info">
          <p class="mb-0">Recibirás una confirmación por email y te contactaremos para coordinar la entrega.</p>
        </div>
      </div>
      <div class="modal-footer">
        <a href="#" id="confirmOrderByWhatsapp" target="_blank" rel="noopener" class="btn btn-success">
          <i class="fa-brands fa-whatsapp me-1"></i>Confirmar pedido por WhatsApp
        </a>
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Entendido</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="orderDetailModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Detalles del Pedido</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row mb-3">
          <div class="col-md-6"><p><strong>Orden:</strong> <span id="orderDetailNumber">-</span></p></div>
          <div class="col-md-6"><p><strong>Fecha:</strong> <span id="orderDetailDate">-</span></p></div>
          <div class="col-md-6"><p><strong>Total:</strong> <span id="orderDetailTotal">-</span></p></div>
          <div class="col-md-6"><p><strong>Método de pago:</strong> <span id="orderDetailPayment">-</span></p></div>
          <div class="col-12"><p><strong>Info de entrega:</strong> <span id="orderDetailDeliveryInfo">-</span></p></div>
        </div>
        <div class="table-responsive">
          <table class="table table-sm table-bordered">
            <thead class="table-light">
              <tr>
                <th>Producto</th>
                <th>Categoría</th>
                <th>Cantidad</th>
                <th>Precio</th>
                <th>Total</th>
              </tr>
            </thead>
            <tbody id="orderDetailItemsBody"></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="tycModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5>T&eacute;rminos y Condiciones</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <h6>1. Aceptaci&oacute;n de los T&eacute;rminos</h6>
        <p>Al acceder y utilizar este sitio web, aceptas cumplir con estos t&eacute;rminos y condiciones. Si no est&aacute;s de acuerdo, no uses este sitio.</p>

        <h6>2. Uso del Servicio</h6>
        <p>Este sitio ofrece informaci&oacute;n sobre productos farmac&eacute;uticos y de cuidado personal. La informaci&oacute;n proporcionada no sustituye el consejo m&eacute;dico profesional.</p>

        <h6>3. Responsabilidad</h6>
        <p>SERVIFARMACIA RK no se hace responsable por el uso indebido de los productos adquiridos. Consulte siempre a un profesional de la salud antes de usar cualquier medicamento.</p>

        <h6>4. Precios y Disponibilidad</h6>
        <p>Los precios y la disponibilidad de los productos est&aacute;n sujetos a cambios sin previo aviso. Nos reservamos el derecho de modificar o descontinuar productos en cualquier momento.</p>

        <h6>5. Privacidad</h6>
        <p>Tus datos personales ser&aacute;n tratados conforme a nuestra Pol&iacute;tica de Privacidad. No compartiremos tu informaci&oacute;n con terceros sin tu consentimiento.</p>

        <h6>6. Propiedad Intelectual</h6>
        <p>Todo el contenido del sitio (textos, im&aacute;genes, logotipos) es propiedad de SERVIFARMACIA RK y est&aacute; protegido por derechos de autor.</p>

        <h6>7. Legislaci&oacute;n Aplicable</h6>
        <p>Estos t&eacute;rminos se rigen por las leyes de la Rep&uacute;blica de Colombia. Cualquier disputa ser&aacute; resuelta en los tribunales de Bogot&aacute;.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="privacyModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5>Pol&iacute;tica de Privacidad</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <h6>1. Informaci&oacute;n que Recopilamos</h6>
        <p>Recopilamos informaci&oacute;n personal como nombre, direcci&oacute;n de correo electr&oacute;nico, n&uacute;mero de tel&eacute;fono y direcci&oacute;n de env&iacute;o cuando realizas un pedido o te registras en nuestro sitio.</p>

        <h6>2. Uso de la Informaci&oacute;n</h6>
        <p>Utilizamos tu informaci&oacute;n para procesar pedidos, mejorar nuestros servicios, enviar comunicaciones relacionadas con tu compra y cumplir con requisitos legales.</p>

        <h6>3. Protecci&oacute;n de Datos</h6>
        <p>Implementamos medidas de seguridad t&eacute;cnicas y organizativas para proteger tus datos personales contra acceso no autorizado, p&eacute;rdida o alteraci&oacute;n.</p>

        <h6>4. Compartir Informaci&oacute;n</h6>
        <p>No vendemos ni compartimos tu informaci&oacute;n personal con terceros para fines de marketing. Podemos compartir datos con procesadores de pago y servicios de env&iacute;o necesarios para completar tu pedido.</p>

        <h6>5. Tus Derechos</h6>
        <p>Tienes derecho a acceder, corregir o eliminar tus datos personales en cualquier momento. Para ejercer estos derechos, cont&aacute;ctanos a trav&eacute;s de nuestro correo electr&oacute;nico.</p>

        <h6>6. Cookies</h6>
        <p>Utilizamos cookies para mejorar tu experiencia de navegaci&oacute;n. Puedes configurar tu navegador para rechazar cookies, aunque esto puede afectar la funcionalidad del sitio.</p>

        <h6>7. Contacto</h6>
        <p>Si tienes preguntas sobre nuestra pol&iacute;tica de privacidad, cont&aacute;ctanos al correo info@servifarmaciark.com o al tel&eacute;fono 3115631854.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="faqModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="fa-solid fa-circle-question me-2"></i>Preguntas Frecuentes</h5>
        <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="accordion" id="faqAccordion">
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                ¿Cómo realizo un pedido?
              </button>
            </h2>
            <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
              <div class="accordion-body">
                Agrega los productos a tu carrito, pulsa "Proceder al Pago", completa tus datos de entrega y elige tu método de pago (Efectivo, Nequi o Daviplata). Recibirás la confirmación de tu pedido al instante.
              </div>
            </div>
          </div>
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                ¿Qué métodos de pago aceptan?
              </button>
            </h2>
            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
              <div class="accordion-body">
                Aceptamos <strong>Efectivo</strong> (al recibir tu pedido), <strong>Nequi</strong> y <strong>Daviplata</strong>. Para Nequi y Daviplata te mostramos un código QR para realizar el pago.
              </div>
            </div>
          </div>
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                ¿Cuál es el tiempo de entrega?
              </button>
            </h2>
            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
              <div class="accordion-body">
                Las entregas se coordinan según tu ubicación. Nuestro equipo te contactará vía WhatsApp para confirmar la hora de entrega.
              </div>
            </div>
          </div>
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                ¿Puedo hacer seguimiento a mi pedido?
              </button>
            </h2>
            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
              <div class="accordion-body">
                Sí. Desde "Mi Cuenta" puedes ver el estado de tus pedidos. También puedes escribirnos por el chat en vivo o por WhatsApp.
              </div>
            </div>
          </div>
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                ¿Cómo contacto a soporte?
              </button>
            </h2>
            <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
              <div class="accordion-body">
                Puedes usar el <strong>Chat en Vivo</strong> (ícono de chat en la esquina), escribirnos al WhatsApp <a href="https://wa.me/573115631854" target="_blank" rel="noopener">311 563 1854</a> o enviarnos un correo a info@servifarmaciark.com.
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <a href="https://wa.me/573115631854?text=Hola%20SERVIFARMACIA%20RK,%20tengo%20una%20consulta" target="_blank" rel="noopener" class="btn btn-success"><i class="fa-brands fa-whatsapp me-1"></i>Escribir por WhatsApp</a>
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Mi cuenta -->
<div class="modal fade" id="myAccountModal" tabindex="-1" aria-labelledby="myAccountModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="myAccountModalLabel"><i class="fa-solid fa-user me-2"></i>Mi cuenta</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h6 class="mb-0">Mis pedidos</h6>
          <button type="button" class="btn btn-outline-primary btn-sm" id="refreshMyOrdersBtn"><i class="fa-solid fa-rotate me-1"></i>Actualizar</button>
        </div>
        <div id="myOrdersContainer"><div class="text-center text-muted py-4">Cargando pedidos...</div></div>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Editar mi cuenta -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="editProfileModalLabel"><i class="fa-solid fa-user-pen me-2"></i>Editar mi cuenta</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <form id="editProfileForm">
        <div class="modal-body">
          <div class="mb-3">
            <label for="epName" class="form-label">Nombre completo</label>
            <input type="text" class="form-control" id="epName" required>
          </div>
          <div class="mb-3">
            <label for="epEmail" class="form-label">Correo electrónico</label>
            <input type="email" class="form-control" id="epEmail" required>
          </div>
          <div class="mb-3">
            <label for="epUsername" class="form-label">Usuario</label>
            <input type="text" class="form-control" id="epUsername" required>
          </div>
          <hr>
          <p class="text-muted small mb-2">Deja la contraseña en blanco si no quieres cambiarla.</p>
          <div class="mb-3">
            <label for="epPassword" class="form-label">Nueva contraseña</label>
            <input type="password" class="form-control" id="epPassword" placeholder="Mínimo 8 caracteres" minlength="8">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i>Guardar cambios</button>
        </div>
      </form>
    </div>
  </div>
</div>
