<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database';

    protected $description = 'Genera un respaldo comprimido de la base de datos';

    public function handle(): int
    {
        $this->info(
            'Iniciando respaldo de base de datos...'
        );

        $directorio = storage_path(
            'app/backups'
        );

        if (! File::exists($directorio)) {

            File::makeDirectory(
                $directorio,
                0755,
                true
            );

        }

        $nombreBase =
            'oneshop_backup_'.
            now()->format('Y-m-d_H-i-s');

        $archivoSql =
            $directorio.
            '/'.
            $nombreBase.
            '.sql';

        $archivoGzip =
            $archivoSql.
            '.gz';

        $conexion = config('database.connections.mysql');
        $database = (string) ($conexion['database'] ?? '');
        $usuario = (string) ($conexion['username'] ?? '');
        $password = (string) ($conexion['password'] ?? '');
        $host = (string) ($conexion['host'] ?? '127.0.0.1');
        $puerto = (string) ($conexion['port'] ?? '3306');

        if ($database === '' || $usuario === '') {
            $this->error('La conexión MySQL no tiene base de datos o usuario configurado.');

            return Command::FAILURE;
        }

        $proceso = new Process(
            [
                'mysqldump',
                '--skip-ssl',
                '--no-tablespaces',
                '--single-transaction',
                '--quick',
                '--host='.$host,
                '--port='.$puerto,
                '--user='.$usuario,
                $database,
            ],
            base_path(),
            ['MYSQL_PWD' => $password]
        );

        $proceso->setTimeout(300);

        $salida = fopen($archivoSql, 'wb');

        if ($salida === false) {
            $this->error('No se pudo crear el archivo temporal del respaldo.');

            return Command::FAILURE;
        }

        $errores = '';

        try {
            $proceso->run(function (string $tipo, string $contenido) use ($salida, &$errores): void {
                if ($tipo === Process::OUT) {
                    fwrite($salida, $contenido);

                    return;
                }

                $errores .= $contenido;
            });
        } finally {
            fclose($salida);
        }

        if (! $proceso->isSuccessful()) {

            if (File::exists($archivoSql)) {

                File::delete($archivoSql);

            }

            $this->error(
                'No se pudo generar el backup.'
            );

            $this->error(
                trim($errores) ?: 'mysqldump finalizó con error.'
            );

            return Command::FAILURE;

        }

        /*
         * Compresión del respaldo
         */

        $origen = fopen($archivoSql, 'rb');
        $destino = gzopen($archivoGzip, 'wb9');

        if ($origen === false || $destino === false) {
            if (is_resource($origen)) {
                fclose($origen);
            }

            if (is_resource($destino)) {
                gzclose($destino);
            }

            File::delete([$archivoSql, $archivoGzip]);
            $this->error('No se pudo comprimir el respaldo.');

            return Command::FAILURE;
        }

        try {
            while (! feof($origen)) {
                $bloque = fread($origen, 1024 * 1024);

                if ($bloque === false || gzwrite($destino, $bloque) === false) {
                    throw new \RuntimeException('No se pudo escribir el respaldo comprimido.');
                }
            }
        } catch (\Throwable $exception) {
            File::delete([$archivoSql, $archivoGzip]);
            $this->error($exception->getMessage());

            return Command::FAILURE;
        } finally {
            fclose($origen);
            gzclose($destino);
        }

        File::delete($archivoSql);

        /*
         * Limpieza automática:
         * conservar últimos 30 backups
         */

        $backups = collect(
            File::files($directorio)
        )
            ->filter(function ($archivo) {

                return str_ends_with(
                    $archivo->getFilename(),
                    '.sql.gz'
                );

            })
            ->sortByDesc(function ($archivo) {

                return $archivo->getMTime();

            })
            ->values();

        $backups
            ->slice(30)
            ->each(function ($archivo) {

                File::delete(
                    $archivo->getPathname()
                );

            });

        $this->info(
            'Backup creado correctamente.'
        );

        $this->line(
            $archivoGzip
        );

        return Command::SUCCESS;

    }
}
