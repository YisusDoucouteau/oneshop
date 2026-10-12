# Plan de respaldo y recuperación de OneShop

## Objetivo

Restablecer la base de datos de OneShop después de una pérdida, corrupción o despliegue fallido, conservando la trazabilidad de importaciones, equipos, ventas y garantías.

## Política implementada

- Respaldo automático todos los días a las 02:00, zona horaria `America/La_Paz`.
- Archivos comprimidos en `storage/app/backups/oneshop_backup_AAAA-MM-DD_HH-mm-ss.sql.gz`.
- Retención automática de los 30 respaldos más recientes.
- Ejecución mediante el contenedor `oneshop_scheduler`.
- Generación manual disponible con `docker exec oneshop_app php artisan backup:database`.

Los archivos guardados en el mismo servidor no protegen contra pérdida total del equipo. En producción se debe copiar al menos un respaldo diario a almacenamiento externo cifrado y restringido.

## Verificación rutinaria

1. Confirmar los servicios con `docker compose ps`.
2. Confirmar la programación con `docker exec oneshop_app php artisan schedule:list`.
3. Generar un respaldo manual antes de migraciones o despliegues con `docker exec oneshop_app php artisan backup:database`.
4. Verificar el archivo indicado por el comando con `docker exec oneshop_app gzip -t /var/www/storage/app/backups/NOMBRE.sql.gz`.

Una prueba de recuperación completa debe realizarse mensualmente en una base temporal, nunca sobre la base principal.

## Restauración segura

### Preparación

- Avisar a los usuarios y detener operaciones de escritura.
- Identificar el respaldo correcto y comprobarlo con `gzip -t`.
- Generar, si todavía es posible, un respaldo de emergencia del estado actual.
- Conservar una copia externa del archivo elegido.

### Ensayo aislado obligatorio

Crear una base temporal con un nombre exclusivo:

```bash
docker exec -e MYSQL_PWD=CLAVE_ROOT oneshop_app mysql --skip-ssl -hmysql -uroot \
  -e "CREATE DATABASE oneshop_restore_validation CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
```

Restaurar el archivo en esa base:

```bash
docker exec oneshop_app sh -lc \
  "gzip -dc /var/www/storage/app/backups/NOMBRE.sql.gz | MYSQL_PWD=CLAVE_ROOT mysql --skip-ssl -hmysql -uroot oneshop_restore_validation"
```

Comprobar tablas y registros críticos:

```bash
docker exec -e MYSQL_PWD=CLAVE_ROOT oneshop_app mysql --skip-ssl -hmysql -uroot \
  -e "SELECT COUNT(*) AS tablas FROM information_schema.tables WHERE table_schema='oneshop_restore_validation'; \
      SELECT COUNT(*) AS migraciones FROM oneshop_restore_validation.migrations; \
      SELECT COUNT(*) AS equipos FROM oneshop_restore_validation.equipos; \
      SELECT COUNT(*) AS ventas FROM oneshop_restore_validation.ventas;"
```

Eliminar la base temporal solo después de documentar el resultado:

```bash
docker exec -e MYSQL_PWD=CLAVE_ROOT oneshop_app mysql --skip-ssl -hmysql -uroot \
  -e "DROP DATABASE oneshop_restore_validation"
```

### Recuperación de producción

La restauración sobre producción es destructiva y debe realizarla el responsable técnico después de un ensayo aislado satisfactorio.

1. Activar modo mantenimiento: `docker exec oneshop_app php artisan down`.
2. Respaldar el estado actual.
3. Restaurar el archivo validado en una base nueva.
4. Cambiar la configuración para apuntar a la base recuperada.
5. Ejecutar `docker exec oneshop_app php artisan optimize:clear`.
6. Verificar acceso, inventario, una venta, una garantía y una trazabilidad.
7. Desactivar mantenimiento: `docker exec oneshop_app php artisan up`.

No se recomienda vaciar ni sobrescribir directamente la base vigente. El cambio hacia una base restaurada permite volver atrás si la validación funcional falla.

## Evidencia de la última prueba

El 11 de octubre de 2026 se generó y restauró un respaldo en una base temporal aislada. La validación recuperó 91 tablas, 127 migraciones, 16 equipos y 6 ventas. La base temporal fue eliminada al finalizar y la base principal no fue modificada.

## Responsables y periodicidad

- Diario: comprobar que exista un respaldo reciente.
- Antes de cada despliegue: crear respaldo manual.
- Semanal: copiar un respaldo fuera del servidor.
- Mensual: ejecutar una restauración aislada y registrar fecha, responsable, archivo y resultado.
- Ante un incidente: preservar evidencias, suspender escrituras y aplicar este procedimiento.
