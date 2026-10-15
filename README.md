# OneShop

Sistema web para gestionar importaciones, recepción, inventario, reservas, ventas, garantías y trazabilidad individual de equipos.

## Ejecución local

Requisitos: Docker Desktop, Docker Compose y Node.js con npm.

```bash
cp .env.example .env
docker compose build
docker compose run --rm app composer install
docker compose up -d
docker exec oneshop_app php artisan key:generate
docker exec oneshop_app php artisan migrate --seed
npm install
npm run build
```

La aplicación queda disponible en `http://localhost:8080`.

## Servicios

- `oneshop_nginx`: servidor web.
- `oneshop_app`: aplicación Laravel/PHP.
- `oneshop_mysql`: MySQL 8.4 con volumen persistente.
- `oneshop_scheduler`: ejecuta las tareas programadas, incluido el respaldo diario.

## Pruebas

```bash
docker exec oneshop_app php artisan test
```

## Respaldos

El sistema genera diariamente, a las 02:00 de `America/La_Paz`, un respaldo comprimido en `storage/app/backups` y conserva los 30 más recientes.

Para generar uno manualmente:

```bash
docker exec oneshop_app php artisan backup:database
```

El procedimiento completo y seguro de validación y restauración se encuentra en [docs/RECUPERACION.md](docs/RECUPERACION.md).

## Seguridad de configuración

No se deben versionar `.env`, respaldos SQL ni credenciales. En producción se debe usar `APP_ENV=production`, `APP_DEBUG=false`, contraseñas únicas y HTTPS.

La configuración local no debe publicarse directamente. El despliegue seguro usa `compose.production.yaml` y el procedimiento descrito en [docs/DESPLIEGUE.md](docs/DESPLIEGUE.md).
