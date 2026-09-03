<?php

namespace App\Services;

use App\Exceptions\ReglaNegocioException;
use App\Models\Cliente;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\ParametroSistema;
use App\Models\Reserva;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReservaService
{
    public function __construct(
          private readonly EstadoEquipoService $estadoEquipoService,
    private readonly MovimientoInventarioService $movimientoInventarioService


    ) {
    }

    public function crearReserva(
        int $clienteId,
        int $usuarioId,
        array $equiposIds,
        CarbonInterface $fechaVencimiento,
        ?string $observacion = null
    ): Reserva {
        return DB::transaction(function () use (
            $clienteId,
            $usuarioId,
            $equiposIds,
            $fechaVencimiento,
            $observacion
        ) {
            $cliente = Cliente::query()
                ->where('activo', true)
                ->find($clienteId);

            if (!$cliente) {
                throw new ReglaNegocioException(
                    'El cliente no existe o se encuentra inactivo.'
                );
            }

            $equiposIds = array_values(
                array_unique(
                    array_map('intval', $equiposIds)
                )
            );

            sort($equiposIds);

            if (empty($equiposIds)) {
                throw new ReglaNegocioException(
                    'La reserva debe contener al menos un equipo.'
                );
            }

            $this->validarFechaVencimiento(
                $fechaVencimiento
            );

            /*
             * Bloqueamos los equipos en un orden determinista.
             * Esto reduce conflictos si dos operaciones intentan
             * trabajar simultáneamente sobre varios equipos.
             */
            $equipos = Equipo::query()
                ->whereIn('id', $equiposIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($equipos->count() !== count($equiposIds)) {
                throw new ReglaNegocioException(
                    'Uno o más equipos seleccionados no existen.'
                );
            }

            $estadoDisponible = EstadoEquipo::query()
                ->where('codigo', 'DISPONIBLE')
                ->where('activo', true)
                ->firstOrFail();

            foreach ($equipos as $equipo) {
                if (!$equipo->activo) {
                    throw new ReglaNegocioException(
                        "El equipo {$equipo->codigo_interno} se encuentra inactivo."
                    );
                }

                if ($equipo->estado_actual_id !== $estadoDisponible->id) {
                    throw new ReglaNegocioException(
                        "El equipo {$equipo->codigo_interno} no está disponible para reserva."
                    );
                }

                if (
                    $equipo->detallesReservas()
                        ->whereHas('reserva', function ($query) {
                            $query->where('estado', 'ACTIVA');
                        })
                        ->exists()
                ) {
                    throw new ReglaNegocioException(
                        "El equipo {$equipo->codigo_interno} ya posee una reserva activa."
                    );
                }

                $precio = $equipo->precios()
                    ->where('vigente', true)
                    ->orderByDesc('vigente_desde')
                    ->first();

                if (!$precio) {
                    throw new ReglaNegocioException(
                        "El equipo {$equipo->codigo_interno} no posee un precio vigente."
                    );
                }
            }

            $reserva = Reserva::create([
            'numero' => $this->generarNumero(),
                'cliente_id' => $cliente->id,
                'registrado_por_id' => $usuarioId,
                 'estado' => 'ACTIVA',
                 'fecha_reserva' => now(),
                'fecha_expiracion' => $fechaVencimiento,
                'fecha_cierre' => null,
                 'observacion' => $observacion,
        ]);

            foreach ($equipos as $equipo) {
                $precio = $equipo->precios()
                    ->where('vigente', true)
                    ->orderByDesc('vigente_desde')
                    ->firstOrFail();

                $reserva->detalles()->create([
                    'equipo_id' => $equipo->id,
                    'precio_acordado' => $precio->precio_publico,
                    'descuento_acordado' => 0,
                    'observacion' => null,
                ]);

                $this->movimientoInventarioService
    ->registrarReserva(
        productoId: $equipo->producto_id,
        almacenId: $equipo->almacen_actual_id,
        cantidad: 1,
        usuarioId: $usuarioId,
        tipoReferencia: 'RESERVA',
        referenciaId: $reserva->id,
        observacion: "Reserva {$reserva->numero}"
    );
            }

           return $reserva->fresh([
           'cliente',
          'registradoPor',
            'detalles.equipo.estadoActual',
             'detalles.equipo.producto',
             ]);
        }, 3);
    }

    private function validarFechaVencimiento(
        CarbonInterface $fechaVencimiento
    ): void {
        $ahora = now();

        if ($fechaVencimiento->lessThanOrEqualTo($ahora)) {
            throw new ReglaNegocioException(
                'La fecha de vencimiento debe ser posterior a la fecha actual.'
            );
        }

        $parametro = ParametroSistema::query()
            ->where(
                'codigo',
                'RESERVA_DIAS_MAXIMOS_ESTANDAR'
            )
            ->where('activo', true)
            ->first();

        if (!$parametro) {
            throw new ReglaNegocioException(
                'No está configurado el plazo máximo estándar de reservas.'
            );
        }

        $fechaMaxima = $ahora->copy()->addDays(
            $parametro->valorEntero()
        );

        if ($fechaVencimiento->greaterThan($fechaMaxima)) {
            throw new ReglaNegocioException(
                sprintf(
                    'La reserva estándar no puede superar %d días.',
                    $parametro->valorEntero()
                )
            );
        }
    }

    private function generarNumero(): string
    {
        return 'RES-' .
            now()->format('Ymd') .
            '-' .
            Str::upper(Str::ulid());
    }
    public function liberarReserva(
    int $reservaId,
    int $usuarioId
): Reserva {


    return DB::transaction(function () use (
        $reservaId,
        $usuarioId
    ) {


        $reserva = Reserva::query()
            ->with('detalles.equipo')
            ->lockForUpdate()
            ->find($reservaId);



        if (!$reserva) {

            throw new ReglaNegocioException(
                'La reserva no existe.'
            );

        }



        if ($reserva->estado !== 'ACTIVA') {

            throw new ReglaNegocioException(
                'Solo se pueden liberar reservas activas.'
            );

        }



        foreach ($reserva->detalles as $detalle) {


            $equipo = $detalle->equipo;


            $this->movimientoInventarioService
                ->registrarLiberacionReserva(

                    productoId:
                        $equipo->producto_id,

                    almacenId:
                        $equipo->almacen_actual_id,

                    cantidad:
                        1,

                    usuarioId:
                        $usuarioId,

                    tipoReferencia:
                        'LIBERACION_RESERVA',

                    referenciaId:
                        $reserva->id,

                    observacion:
                        "Liberación reserva {$reserva->numero}"

                );

        }



        $reserva->update([

            'estado' =>
                'LIBERADA',

            'fecha_cierre' =>
                now(),

        ]);



        return $reserva->fresh();

    });

}
}