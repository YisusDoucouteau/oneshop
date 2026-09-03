<?php

namespace App\Console\Commands;

use App\Models\Reserva;
use App\Services\ReservaService;
use Illuminate\Console\Command;

class VencerReservas extends Command
{
    protected $signature = 'reservas:vencer';

    protected $description = 'Libera reservas vencidas y devuelve inventario al stock disponible';


    public function handle(
        ReservaService $reservaService
    ): int {

        $reservas = Reserva::query()

            ->where('estado', 'ACTIVA')

            ->where(
                'fecha_expiracion',
                '<=',
                now()
            )

            ->get();



        if ($reservas->isEmpty()) {

            $this->info(
                'No existen reservas vencidas.'
            );

            return self::SUCCESS;

        }



        foreach ($reservas as $reserva) {

            try {

                $reservaService->liberarReserva(
                    $reserva->id,
                    null
                );


                $reserva->update([

                    'estado' =>
                        'VENCIDA',

                    'fecha_cierre' =>
                        now(),

                ]);



                $this->info(

                    "Reserva {$reserva->numero} vencida correctamente."

                );


            } catch (\Throwable $e) {


                $this->error(

                    "Error venciendo reserva {$reserva->numero}: "
                    .$e->getMessage()

                );

            }

        }



        return self::SUCCESS;
    }
}