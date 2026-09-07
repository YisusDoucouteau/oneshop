<?php

namespace App\Services;

use App\Models\ParametroSistema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class GeneradorCodigoInventarioService
{

    public function generar(): string
    {
        return DB::transaction(function () {

            $prefijo = ParametroSistema::where(
                'codigo',
                'INVENTARIO_PREFIJO'
            )
            ->where('activo', true)
            ->value('valor');


            $parametroCorrelativo = ParametroSistema::where(
                'codigo',
                'INVENTARIO_ULTIMO_CORRELATIVO'
            )
            ->where('activo', true)
            ->lockForUpdate()
            ->first();


            if (!$parametroCorrelativo) {

                throw new \Exception(
                    'No existe el parámetro de correlativo de inventario.'
                );
            }


            $nuevoNumero = ((int) $parametroCorrelativo->valor) + 1;


            $parametroCorrelativo->update([
                'valor' => $nuevoNumero,
                'modificado_por_id' => Auth::id(),
            ]);


            return $prefijo . $nuevoNumero;

        });
    }

}