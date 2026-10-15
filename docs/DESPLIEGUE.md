# Despliegue de OneShop en producción

Esta guía publica OneShop en un servidor Linux mediante Docker Compose. La base de datos no se expone a Internet y Nginx solo escucha en `127.0.0.1:8080`, detrás del proxy HTTPS del servidor.

## Requisitos

- Servidor Ubuntu LTS con al menos 1 vCPU, 2 GB de RAM y 50 GB de almacenamiento.
- Docker Engine y el complemento Docker Compose.
- Dominio o subdominio apuntando a la dirección IP del servidor.
- Proxy HTTPS en el servidor, por ejemplo Caddy, conectado a `127.0.0.1:8080`.

## Preparación inicial

1. Clonar el repositorio en el servidor.
2. Copiar `.env.production.example` como `.env.production`.
3. Reemplazar el dominio y todas las contraseñas de ejemplo.
4. Construir las imágenes de producción:

```bash
docker compose --env-file .env.production -f compose.production.yaml build
```

5. Generar la clave de Laravel:

```bash
docker compose --env-file .env.production -f compose.production.yaml run --rm app php artisan key:generate --show
```

6. Guardar el valor generado en `APP_KEY` dentro de `.env.production`.

El archivo `.env.production` no debe versionarse ni compartirse.

## Construcción y arranque

```bash
docker compose --env-file .env.production -f compose.production.yaml up -d mysql
docker compose --env-file .env.production -f compose.production.yaml run --rm app php artisan migrate --force
docker compose --env-file .env.production -f compose.production.yaml up -d
docker compose --env-file .env.production -f compose.production.yaml exec app php artisan optimize
```

Para una instalación vacía, cargar los catálogos y permisos mediante los seeders revisados. Para una prueba con los datos actuales, restaurar un respaldo validado en lugar de ejecutar seeders generales. Nunca cargar datos demostrativos ni contraseñas conocidas en producción.

## HTTPS y dominio

Con Caddy instalado en el servidor, un archivo `/etc/caddy/Caddyfile` mínimo es:

```caddyfile
sistema.ejemplo.com {
    reverse_proxy 127.0.0.1:8080
}
```

Después de sustituir el dominio, recargar Caddy. El puerto 3306 debe permanecer cerrado; solo se publican 80 y 443 para HTTP/HTTPS y el puerto SSH restringido para administración.

## Verificación posterior

```bash
docker compose --env-file .env.production -f compose.production.yaml ps
docker compose --env-file .env.production -f compose.production.yaml exec app php artisan about
docker compose --env-file .env.production -f compose.production.yaml exec app php artisan schedule:list
docker compose --env-file .env.production -f compose.production.yaml exec app php artisan backup:database
```

Comprobar además el inicio de sesión, permisos por rol, importaciones, inventario, una venta de prueba, garantías, reportes y trazabilidad por número de serie.

## Actualización segura

```bash
docker compose --env-file .env.production -f compose.production.yaml exec app php artisan backup:database
docker compose --env-file .env.production -f compose.production.yaml build
docker compose --env-file .env.production -f compose.production.yaml run --rm app php artisan migrate --force
docker compose --env-file .env.production -f compose.production.yaml up -d
docker compose --env-file .env.production -f compose.production.yaml exec app php artisan optimize
```

Si una actualización falla, detener escrituras y aplicar el procedimiento de `docs/RECUPERACION.md`.

## Respaldos externos

El volumen `oneshop_storage` conserva los respaldos generados por la aplicación, pero sigue ubicado en el mismo servidor. Se debe copiar diariamente al menos un archivo `.sql.gz` a un almacenamiento externo cifrado y comprobar mensualmente una restauración aislada.

