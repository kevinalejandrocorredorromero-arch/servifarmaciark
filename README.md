# SERVIFARMACIA RK

Tienda online de farmacia construida en PHP plano con arquitectura MVC ligera, MySQL/MariaDB y JavaScript vanilla en el frontend.

## Funcionalidades

- Catálogo de productos con categorías, buscador y carrusel (Swiper)
- Carrito de compras y generación de pedidos con control de stock
- Panel de administración: productos, usuarios, pedidos y QR de pago (Nequi/Daviplata)
- Autenticación propia (contraseña + CSRF) y login con Google vía Firebase Authentication
- Verificación de correo electrónico con Brevo
- Notificaciones de inventario por Telegram (stock bajo y vencimientos)
- Asistente de chat con IA (API de HuggingFace)

## Requisitos

- PHP 8.1+ con extensiones `mysqli`, `curl` y `mbstring`
- MySQL 5.7+ / MariaDB 10.4+
- Servidor web (Apache/XAMPP o Docker)

## Instalación local (XAMPP)

1. Clonar el proyecto dentro de `htdocs`.
2. Copiar `config/config.example.php` a `config/config.php` y completar credenciales (BD, HuggingFace, Firebase, Telegram, Brevo). Este archivo nunca se sube al repositorio.
3. Crear la base de datos y cargar el esquema:

   ```bash
   mysql -u root < esquema.sql   # o importar desde phpMyAdmin
   ```

4. Ejecutar las migraciones en orden desde `migrations/` (son manuales, idempotentes).
5. Entrar a `http://localhost/<carpeta-del-proyecto>/index.php`.

## Estructura

```
api.php               Punto de entrada de la API AJAX
index.php             Front controller
controllers/          Controladores (uno por módulo)
models/               Acceso a datos
core/                 Núcleo: Database, Auth, Firebase, Mailer
views/                Vistas y partials
migrations/           SQL de migraciones (aplicar manualmente)
config/               Configuración (real ignorada por git)
```

## Despliegue

Opciones soportadas: hosting compartido con PHP+MySQL (ver `config/config.production.example.php`) o Docker (`docker-compose.yml`, incluye Caddy y cron de alertas). Consultar `DEPLOY.md`.

## Seguridad

- `config/config.php` contiene secretos y está excluido del repositorio; usar los archivos `*.example.php` como plantilla.
- Los volcados de base de datos (`database/*.sql`) no se versionan porque pueden contener datos de usuarios.
