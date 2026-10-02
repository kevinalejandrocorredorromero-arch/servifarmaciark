/**
 * API Client para SERVIFARMACIA RK
 * Maneja todas las comunicaciones con el servidor
 */

class APIClient {
  constructor(baseURL = 'api.php') {
    this.baseURL = baseURL
  }

  /**
   * Realiza una solicitud GET o POST al API
   */
  async request(action, method = 'GET', data = null) {
    try {
      let url = `${this.baseURL}?action=${action}`
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || ''
      let options = {
        method: method,
        credentials: 'same-origin',
        cache: 'no-store',
        headers: {
          'Content-Type': 'application/json',
          ...(csrfToken ? { 'X-CSRF-Token': csrfToken } : {}),
        },
      }

      if (method === 'POST') {
        // En algunos entornos Apache/PHP las cabeceras personalizadas no llegan
        // como HTTP_X_CSRF_TOKEN. Enviar también el token en el JSON evita ese
        // problema sin eliminar la protección de la cabecera.
        const payload = data && typeof data === 'object' ? { ...data } : {}
        if (csrfToken) payload.csrf_token = csrfToken
        options.body = JSON.stringify(payload)
      }

      const response = await fetch(url, options)

      if (!response.ok) {
        // Leer el cuerpo de la respuesta para mostrar el mensaje real del servidor
        let msg = `Error ${response.status}`
        try {
          const body = await response.json()
          msg = body.error || body.message || msg
        } catch (_) { /* ignorar si no es JSON */ }
        const err = new Error(msg)
        err.status = response.status
        throw err
      }

      // Try to parse JSON; if it fails, return raw text for debugging
      let text = await response.text()
      try {
        const result = JSON.parse(text)
        return result
      } catch (err) {
        console.error(`API (${action}) returned non-JSON response:`, text)
        const e = new Error('Invalid JSON response from server')
        e.raw = text
        throw e
      }
    } catch (error) {
      console.error(`Error en API call (${action}):`, error)
      throw error
    }
  }

  // Productos
  async getProducts() {
    const res = await this.request('getProducts', 'GET')
    // some endpoints return { success: true, products: [...] }
    if (res && typeof res === 'object' && res.success && Array.isArray(res.products)) return res.products
    // or return array directly
    return Array.isArray(res) ? res : []
  }

  async addProduct(productData) {
    return this.request('addProduct', 'POST', productData)
  }

  async updateProduct(productData) {
    return this.request('updateProduct', 'POST', productData)
  }

  async deleteProduct(productId) {
    return this.request('deleteProduct', 'POST', { id: productId })
  }

  async updateProductStock(productId, change) {
    return this.request('updateProductStock', 'POST', { id: productId, change: change })
  }

  // Usuarios
  async getUsers() {
    const res = await this.request('getUsers', 'GET')
    if (res && typeof res === 'object' && res.success && Array.isArray(res.users)) return res.users
    return Array.isArray(res) ? res : []
  }

  async toggleUserAdmin(userId) {
    return this.request('toggleUserAdmin', 'POST', { id: userId })
  }

  async toggleUserSeller(userId) {
    return this.request('toggleUserSeller', 'POST', { id: userId })
  }

  async deleteUser(userId) {
    return this.request('deleteUser', 'POST', { id: userId })
  }

  async registerUser(userData) {
    return this.request('registerUser', 'POST', userData)
  }

  async loginUser(credentials) {
    return this.request('loginUser', 'POST', credentials)
  }

  async loginFirebase(idToken) {
    return this.request('loginFirebase', 'POST', { idToken })
  }

  async verifyEmail(token) {
    return this.request('verificarCorreo', 'POST', { token })
  }

  async resendVerification(email) {
    return this.request('reenviarVerificacion', 'POST', { email })
  }

  // "¿Hay sesión activa?" es una pregunta legítima: un 401 no es un error.
  async getMe() {
    const response = await fetch(`${this.baseURL}?action=me`, {
      credentials: 'same-origin',
      cache: 'no-store',
    })
    if (response.status === 401) return { success: false }
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }
    return await response.json()
  }

  async logout() {
    return this.request('logout', 'POST')
  }

  async updateProfile(userData) {
    return this.request('updateProfile', 'POST', userData)
  }

  async getOrders() {
    const response = await fetch(`${this.baseURL}?action=getOrders`, { credentials: 'same-origin' })
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }
    const res = await response.json()
    if (res && typeof res === 'object' && res.success && Array.isArray(res.orders)) return res.orders
    return Array.isArray(res) ? res : []
  }

  async confirmOrderDelivery(orderId) {
    return this.request('confirmOrderDelivery', 'POST', { order_id: orderId })
  }

  async cancelOrder(orderId) {
    return this.request('cancelOrder', 'POST', { order_id: orderId })
  }

  async checkout(checkoutData) {
    return this.request('checkout', 'POST', checkoutData)
  }

  // Carrito (BD)
  async addToCart(productData) {
    return this.request('addToCart', 'POST', productData)
  }

  async getCart() {
    return this.request('getCart', 'GET')
  }

  async updateCartItem(cartId, quantity) {
    return this.request('updateCartItem', 'POST', { cart_id: cartId, quantity: quantity })
  }

  async removeFromCart(cartId) {
    return this.request('removeFromCart', 'POST', { cart_id: cartId })
  }

  async clearCart() {
    return this.request('clearCart', 'POST')
  }

  // Configuración de métodos de pago (QR de Nequi/Daviplata)
  async getPaymentConfig() {
    return this.request('getPaymentConfig', 'GET')
  }

  async savePaymentConfig(configData) {
    return this.request('savePaymentConfig', 'POST', configData)
  }
}
