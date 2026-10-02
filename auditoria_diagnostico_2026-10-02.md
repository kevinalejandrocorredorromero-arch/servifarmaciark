# Diagnóstico inicial de SERVIFARMACIA RK

Fecha: 2026-10-02
Entorno: XAMPP local (`127.0.0.1`), base `servifarmacia_rk`
Producción: no conectada ni modificada
Docker/Caddy: fuera de alcance y no ejecutados

## Estado Git

El árbol ya tenía cambios previos a esta auditoría. No se revirtieron ni se mezclaron con ellos. Entre los cambios existentes aparecen `api.php`, `api-client.js`, `script.js`, controladores, modelos, `core/Database.php`, vistas, configuración de ejemplo, migraciones y archivos nuevos de verificación por correo.

## Archivos revisados

- `config/config.php`: leído únicamente con credenciales redactadas en este informe.
- `config/config.example.php`
- `config/config.production.example.php`
- `migrations/001_firebase_auth.sql`
- `migrations/002_relaciones_fk.sql`
- `migrations/003_verificacion_correo.sql`
- `database.sql`: identificado como esquema legacy con nombres ingleses.
- `database/`: contiene migraciones; no se ejecutó ningún servicio Docker.
- `logs/api_errors.log`: contiene errores históricos de esquema, Telegram y Brevo.

## Tablas locales

`SHOW TABLES` devolvió:

- `usuarios`
- `productos`
- `pedidos`
- `detalles_pedido`
- `carrito_compras`
- `intentos_login`

## Columnas relevantes

- `usuarios`: `id`, `nombre`, `correo`, `usuario`, `contrasena`, `es_admin`, `rol`, `creado_en`, `firebase_uid`, `proveedor`, `foto_url`, `email_verified`, `email_token`, `email_token_expira`.
- `productos`: `id`, `nombre`, `precio`, `stock`, `categoria`, `imagen`, `codigo_barras`, `lote`, `fecha_ingreso`, `fecha_vencimiento`, `cantidad_lote`, `descripcion`, `umbral_stock`, `es_fraccionable`, `unidades_por_caja`, `precio_caja`, `precio_unidad`, `stock_total_unidades`, `creado_en`, `actualizado_en`.
- `pedidos`: `id`, `numero_pedido`, `total`, `metodo_pago`, `info_entrega`, `usuario_id`, `vendedor_id`, `estado`, `fecha_pedido`.
- `detalles_pedido`: `id`, `pedido_id`, `producto_id`, `nombre`, `categoria`, `precio`, `cantidad`, `total`, `tipo_venta`, `unidades_descontadas`.
- `carrito_compras`: `id`, `usuario_id`, `sesion_id`, `producto_id`, `nombre`, `precio`, `imagen`, `cantidad`, `tipo_venta`, `creado_en`, `actualizado_en`.

## Hallazgos del diagnóstico

1. El esquema operativo local es español, pero el archivo `database.sql` legacy usa nombres ingleses.
2. La verificación de correo todavía usa columnas inglesas y requiere una migración controlada a nombres españoles.
3. La aplicación tiene cambios previos no atribuibles a esta auditoría; cualquier corrección deberá trabajar sobre ellos sin revertirlos.
4. No se conectó a InfinityFree ni se modificó producción.
5. No se inició ni revisó Docker, Caddy, `docker-compose.yml`, `Dockerfile` ni `docker/`.

## Comandos ejecutados

- `git status --short`
- `SHOW TABLES` sobre la base local
- `SHOW COLUMNS` de las cinco tablas operativas

Los secretos de `config/config.php` no se reproducen aquí.
