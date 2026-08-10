<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'ALTER TABLE detalles_ventas
             ADD CONSTRAINT chk_detalle_venta_cantidad
             CHECK (cantidad > 0)'
        );

        DB::statement(
            'ALTER TABLE detalles_ventas
             ADD CONSTRAINT chk_detalle_venta_montos
             CHECK (
                precio_lista_snapshot >= 0
                AND descuento_unitario >= 0
                AND precio_unitario >= 0
                AND subtotal >= 0
             )'
        );

        DB::statement(
            'ALTER TABLE detalles_reservas
             ADD CONSTRAINT chk_detalle_reserva_precio
             CHECK (
                precio_acordado > 0
                AND descuento_acordado >= 0
             )'
        );

        DB::statement(
            'ALTER TABLE asignaciones_componentes
             ADD CONSTRAINT chk_asignacion_componente_cantidad
             CHECK (cantidad > 0)'
        );
    }

    public function down(): void
    {
        DB::statement(
            'ALTER TABLE detalles_ventas
             DROP CHECK chk_detalle_venta_cantidad'
        );

        DB::statement(
            'ALTER TABLE detalles_ventas
             DROP CHECK chk_detalle_venta_montos'
        );

        DB::statement(
            'ALTER TABLE detalles_reservas
             DROP CHECK chk_detalle_reserva_precio'
        );

        DB::statement(
            'ALTER TABLE asignaciones_componentes
             DROP CHECK chk_asignacion_componente_cantidad'
        );
    }
};