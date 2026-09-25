<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'cambios_equipos',
            function (Blueprint $table) {
                /*
                 * Precio realmente pagado por el equipo
                 * original en la venta histórica.
                 *
                 * Fuente:
                 * detalles_ventas.precio_unitario
                 */
                $table
                    ->decimal(
                        'valor_original_snapshot',
                        12,
                        2
                    )
                    ->nullable()
                    ->after('observacion');

                /*
                 * Precio público vigente del equipo
                 * entregado como reemplazo en el momento
                 * de autorizar el cambio.
                 */
                $table
                    ->decimal(
                        'valor_reemplazo_snapshot',
                        12,
                        2
                    )
                    ->nullable()
                    ->after(
                        'valor_original_snapshot'
                    );

                /*
                 * reemplazo - original
                 *
                 * > 0 : cliente debe pagar
                 * = 0 : no existe diferencia
                 * < 0 : saldo a favor del cliente
                 */
                $table
                    ->decimal(
                        'diferencia_snapshot',
                        12,
                        2
                    )
                    ->nullable()
                    ->after(
                        'valor_reemplazo_snapshot'
                    );

                /*
                 * Moneda utilizada para expresar
                 * el ajuste económico.
                 *
                 * Inicialmente BOB.
                 */
                $table
                    ->string(
                        'moneda_ajuste',
                        3
                    )
                    ->nullable()
                    ->after(
                        'diferencia_snapshot'
                    );

                /*
                 * Valores previstos:
                 *
                 * COBRO_CLIENTE
                 * SIN_DIFERENCIA
                 * SALDO_FAVOR_CLIENTE
                 */
                $table
                    ->string(
                        'tipo_ajuste',
                        30
                    )
                    ->nullable()
                    ->after(
                        'moneda_ajuste'
                    );

                /*
                 * Estado del movimiento económico.
                 *
                 * PENDIENTE
                 * LIQUIDADO
                 *
                 * SIN_DIFERENCIA podrá nacer
                 * directamente como LIQUIDADO.
                 */
                $table
                    ->string(
                        'estado_ajuste',
                        20
                    )
                    ->nullable()
                    ->after(
                        'tipo_ajuste'
                    );
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'cambios_equipos',
            function (Blueprint $table) {
                $table->dropColumn([
                    'valor_original_snapshot',
                    'valor_reemplazo_snapshot',
                    'diferencia_snapshot',
                    'moneda_ajuste',
                    'tipo_ajuste',
                    'estado_ajuste',
                ]);
            }
        );
    }
};