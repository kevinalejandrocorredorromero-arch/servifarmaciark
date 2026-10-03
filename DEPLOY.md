# Despliegue Docker fuera de InfinityFree

## Requisitos
- VM Linux con Docker Engine y Docker Compose.
- Dominio apuntando al IP público de la VM para HTTPS de Caddy.
- Un archivo `config/config.php` de producción creado desde `config/config.production.example.php`.

## Preparación
```sh
cp .env.example .env
cp config/config.production.example.php config/config.php
# Editar .env y config/config.php con valores reales.
# No subir ninguno de esos dos archivos a GitHub.
```

## Base de datos
Colocar el dump SQL en `database/servifarmacia_rk.sql`. Las migraciones de producción están en `database/migrations/001_production_compatibility.sql`.

## Arranque
```sh
docker compose config
docker compose build
docker compose up -d
```

La web queda detrás de Caddy en `APP_DOMAIN`. El servicio `cron` llama a `notificar_telegram.php` diariamente según `TZ` y guarda el resultado en `logs/telegram_cron.log`.

## Actualización
```sh
git pull --ff-only
docker compose build
docker compose up -d
```

## Comprobaciones
```sh
docker compose ps
docker compose logs --tail=100 web
tail -f logs/telegram_cron.log
```

No usar `notificar_telegram_helper.php` como URL: es una biblioteca interna. No incluir `config/config.php`, `.env`, logs ni tokens en el repositorio.

## Migración manual de descuentos

La aplicación operativa usa las tablas y columnas en español. Antes de activar promociones en una instalación existente, hacer un respaldo de la base y ejecutar manualmente:

```sh
C:\\xampp\\mysql\\bin\\mysql.exe -u root --host=127.0.0.1 servifarmacia_rk < migrations/004_descuentos_productos.sql
```

En producción, reemplazar el host, usuario, contraseña y nombre de base por los valores asignados por el proveedor. No ejecutar esta migración contra producción sin respaldo. Después comprobar:

```sql
SHOW COLUMNS FROM productos;
SELECT COUNT(*), MIN(descuento_porcentaje), MAX(descuento_porcentaje) FROM productos;
```

La migración añade únicamente `precio_anterior` y `descuento_porcentaje`; no renombra columnas existentes ni crea endpoints nuevos.
