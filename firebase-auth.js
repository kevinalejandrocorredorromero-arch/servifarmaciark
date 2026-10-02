// Inicializa Firebase Authentication y expone window.rkFirebase.
// Se carga como módulo ES (diferido), así que window.RK_FIREBASE_CONFIG ya está definido.

import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.12.2/firebase-app.js'
import {
  getAuth,
  GoogleAuthProvider,
  GithubAuthProvider,
  signInWithPopup,
  signOut,
} from 'https://www.gstatic.com/firebasejs/10.12.2/firebase-auth.js'

const MENSAJES = {
  'auth/popup-blocked': 'El navegador bloqueó la ventana emergente. Permítelas para este sitio e inténtalo de nuevo.',
  'auth/unauthorized-domain': 'Este dominio no está autorizado en Firebase. Agrégalo en Configuración → Dominios autorizados.',
  'auth/operation-not-allowed': 'Este método de acceso no está habilitado en la consola de Firebase.',
  'auth/network-request-failed': 'No se pudo conectar con Firebase. Revisa tu conexión a internet.',
  'auth/user-disabled': 'Esta cuenta fue deshabilitada.',
  'auth/account-exists-with-different-credential': 'Ya existe una cuenta con ese correo que usa otro método de inicio de sesión.',
  'auth/invalid-api-key': 'La API key de Firebase no es válida.',
}

// El usuario puede cerrar la ventana sin más: no es un error que merezca un aviso.
const CANCELACIONES = ['auth/popup-closed-by-user', 'auth/cancelled-popup-request']

function traducir(error) {
  const codigo = error?.code || ''
  const nuevo = new Error(MENSAJES[codigo] || error?.message || 'No se pudo iniciar sesión con este proveedor.')
  nuevo.code = codigo
  nuevo.cancelado = CANCELACIONES.includes(codigo)
  return nuevo
}

const config = window.RK_FIREBASE_CONFIG || {}
const listo = Boolean(config.apiKey && config.authDomain && config.projectId)

if (!listo) {
  window.rkFirebase = { disponible: false }
} else {
  const auth = getAuth(initializeApp(config))
  const proveedores = {
    google: new GoogleAuthProvider(),
    github: new GithubAuthProvider(),
  }

  window.rkFirebase = {
    disponible: true,

    // Devuelve el ID token que el backend verifica contra las claves públicas de Google.
    async iniciarSesion(nombre) {
      const proveedor = proveedores[nombre]
      if (!proveedor) throw new Error('Proveedor no admitido')
      try {
        const credencial = await signInWithPopup(auth, proveedor)
        return await credencial.user.getIdToken()
      } catch (error) {
        throw traducir(error)
      }
    },

    async cerrarSesion() {
      try {
        await signOut(auth)
      } catch (_) { /* sin sesión de Firebase que cerrar */ }
    },
  }
}

document.dispatchEvent(new CustomEvent('rk-firebase-ready'))
