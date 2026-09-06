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


        if (!File::exists($directorio)) {

            File::makeDirectory(
                $directorio,
                0755,
                true
            );

        }


        $nombreBase =
            'oneshop_backup_' .
            now()->format('Y-m-d_H-i-s');


        $archivoSql =
            $directorio .
            '/' .
            $nombreBase .
            '.sql';


        $archivoGzip =
            $archivoSql .
            '.gz';



        $database = env('DB_DATABASE');
        $usuario = env('DB_USERNAME');
        $password = env('DB_PASSWORD');
        $host = env('DB_HOST');



        $comando = sprintf(

            'mysqldump --skip-ssl --no-tablespaces -h %s -u %s -p%s %s > %s',

            $host,

            $usuario,

            $password,

            $database,

            $archivoSql

        );



        $proceso = Process::fromShellCommandline(
            $comando
        );


        $proceso->setTimeout(300);


        $proceso->run();



        if (!$proceso->isSuccessful()) {


            if (File::exists($archivoSql)) {

                File::delete($archivoSql);

            }


            $this->error(
                'No se pudo generar el backup.'
            );


            $this->error(
                $proceso->getErrorOutput()
            );


            return Command::FAILURE;

        }



        /*
         * Compresión del respaldo
         */

        $contenido = File::get(
            $archivoSql
        );


        File::put(
            $archivoGzip,
            gzencode($contenido, 9)
        );


        File::delete(
            $archivoSql
        );



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