<?php

namespace App\Http\Controllers;

use App\Exceptions\ReglaNegocioException;
use App\Services\CatalogoImportacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use Illuminate\Validation\ValidationException;
use Throwable;

class CatalogoImportacionController extends Controller
{
    public function storeProducto(
        Request $request,
        CatalogoImportacionService $service
    ): JsonResponse {
        try {
            $producto = $service->crearProductoRapido(
                $request->user()->id,
                $request->all()
            );

            return response()->json([
                'ok' => true,

                'producto' => [
                    'id' => $producto->id,
                    'codigo' => $producto->codigo,
                    'nombre' => $producto->nombre,
                    'modelo' => $producto->modelo,
                    'marca' => $producto->marca?->nombre,
                    'categoria' => $producto->categoria->nombre,

                    'label' => trim(
                        ($producto->marca?->nombre
                            ? $producto->marca->nombre . ' '
                            : '')
                        . $producto->nombre
                        . ($producto->modelo
                            ? ' — ' . $producto->modelo
                            : '')
                    ),
                ],

                'marca' => $producto->marca
                    ? [
                        'id' => $producto->marca->id,
                        'nombre' => $producto->marca->nombre,
                    ]
                    : null,

                'categoria' => [
                    'id' => $producto->categoria->id,
                    'codigo' => $producto->categoria->codigo,
                    'nombre' => $producto->categoria->nombre,
                ],
            ], 201);

        } catch (ValidationException $exception) {

            return response()->json([
                'ok' => false,
                'message' => 'Revisa los datos del producto.',
                'errors' => $exception->errors(),
            ], 422);

        } catch (ReglaNegocioException $exception) {

            return response()->json([
                'ok' => false,
                'message' => $exception->getMessage(),
                'errors' => [],
            ], 422);

        } catch (Throwable $exception) {

            report($exception);

            return response()->json([
                'ok' => false,
                'message' => 'No fue posible crear el producto.',
                'errors' => [],
            ], 500);
        }
    }


    public function storeProveedor(
        Request $request,
        CatalogoImportacionService $service
    ): JsonResponse {
        try {
            $request->validate([
                'correo' => [
                    'nullable',
                    'email',
                    'max:150',
                ],

                'pais' => [
                    'nullable',
                    'string',
                    'max:100',
                ],

                'ciudad' => [
                    'nullable',
                    'string',
                    'max:100',
                ],

                'telefono' => [
                    'nullable',
                    'string',
                    'max:30',
                ],

                'contacto' => [
                    'nullable',
                    'string',
                    'max:150',
                ],
            ]);

            $proveedor = $service->crearProveedorRapido(
                $request->user()->id,
                $request->all()
            );

            return response()->json([
                'ok' => true,

                'proveedor' => [
                    'id' => $proveedor->id,
                    'nombre' => $proveedor->nombre,
                    'pais' => $proveedor->pais,
                    'ciudad' => $proveedor->ciudad,

                    'label' => trim(
                        $proveedor->nombre
                        . (
                            $proveedor->pais
                                ? ' — ' . $proveedor->pais
                                : ''
                        )
                    ),
                ],
            ], 201);

        } catch (ValidationException $exception) {

            return response()->json([
                'ok' => false,
                'message' => 'Revisa los datos del proveedor.',
                'errors' => $exception->errors(),
            ], 422);

        } catch (ReglaNegocioException $exception) {

            return response()->json([
                'ok' => false,
                'message' => $exception->getMessage(),
                'errors' => [],
            ], 422);

        } catch (Throwable $exception) {

            report($exception);

            return response()->json([
                'ok' => false,
                'message' => 'No fue posible crear el proveedor.',
                'errors' => [],
            ], 500);
        }
    }
}