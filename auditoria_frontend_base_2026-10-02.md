# Auditoría base del rediseño frontend

Fecha: 2026-10-02
Entorno: XAMPP local `servifarmacia_rk`
Producción: no consultada ni modificada
Docker: no ejecutado

## Estado inicial

`git status --short` mostró cambios previos en `script.js` y archivos nuevos de auditoría/imágenes:

- `M script.js`
- `?? auditoria_imagenes_productos_2026-10-02.csv`
- `?? cobertura_imagenes_productos_2026-10-02.md`
- `?? images/products/producto-1.svg`, `producto-2.svg`, `producto-3.svg`, `producto-4.svg`, `producto-5.svg`, `producto-6.svg`, `producto-9.svg`, `producto-12.svg`, `producto-13.svg`, `producto-14.svg`, `producto-16.svg`, `producto-17.svg`

Estos cambios se conservaron y no se revirtieron.

## Columnas actuales de `productos`

La consulta local `SHOW COLUMNS FROM productos` devolvió las columnas existentes en español:

`id`, `nombre`, `precio`, `stock`, `categoria`, `imagen`, `codigo_barras`, `lote`, `fecha_ingreso`, `fecha_vencimiento`, `cantidad_lote`, `descripcion`, `umbral_stock`, `es_fraccionable`, `unidades_por_caja`, `precio_caja`, `precio_unidad`, `stock_total_unidades`, `creado_en`, `actualizado_en`.

Las columnas nuevas de descuentos todavía no existen al comenzar esta fase:

- `precio_anterior`
- `descuento_porcentaje`

## Archivos revisados

- `views/index.php`
- `views/partials/encabezado.php`
- `views/partials/productos.php`
- `views/partials/modales.php`
- `views/partials/administracion.php`
- `style.css`
- `script.js`
- `api-client.js`
- `models/Producto.php`
- `controllers/ProductoControlador.php`
- `models/Pedido.php`

## Restricciones aplicadas

- No se consultó producción.
- No se ejecutó Docker ni se modificaron archivos Docker/Caddy.
- No se cambiaron nombres de tablas o columnas existentes.
