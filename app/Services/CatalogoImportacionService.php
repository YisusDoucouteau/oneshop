<?php

namespace App\Services;

use App\Exceptions\ReglaNegocioException;
use App\Models\CategoriaProducto;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CatalogoImportacionService
{
    /**
     * Crea rápidamente un producto desde el flujo
     * de importaciones.
     *
     * Puede utilizar categoría/marca existentes
     * o crear nuevas en la misma operación.
     */
    public function crearProductoRapido(
        int $usuarioId,
        array $datos
    ): Producto {
        return DB::transaction(function () use (
            $usuarioId,
            $datos
        ) {
            $this->obtenerUsuarioAutorizado(
                $usuarioId
            );

            $categoria = $this->resolverCategoria(
                $datos
            );

            $marca = $this->resolverMarca(
                $datos
            );

            $nombre = trim(
                (string) ($datos['nombre'] ?? '')
            );

            $modelo = $this->normalizarNullable(
                $datos['modelo'] ?? null
            );

            $descripcion = $this->normalizarNullable(
                $datos['descripcion'] ?? null
            );

            /*
            |--------------------------------------------------------------------------
            | Validación principal
            |--------------------------------------------------------------------------
            */

            if ($nombre === '') {
                throw ValidationException::withMessages([
                    'nombre' =>
                        'El nombre del producto es obligatorio.',
                ]);
            }

            if (mb_strlen($nombre) > 150) {
                throw ValidationException::withMessages([
                    'nombre' =>
                        'El nombre del producto no puede superar 150 caracteres.',
                ]);
            }

            if (
                $modelo !== null
                && mb_strlen($modelo) > 120
            ) {
                throw ValidationException::withMessages([
                    'modelo' =>
                        'El modelo no puede superar 120 caracteres.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Evitar duplicados de catálogo
            |--------------------------------------------------------------------------
            |
            | Dos líneas del mismo lote sí pueden utilizar el mismo producto.
            | Lo que evitamos aquí es crear dos productos de catálogo idénticos.
            |
            */

            $duplicado = Producto::query()
                ->where(
                    'categoria_producto_id',
                    $categoria->id
                )
                ->where(
                    'marca_id',
                    $marca?->id
                )
                ->where(
                    'nombre',
                    $nombre
                )
                ->when(
                    $modelo === null,
                    fn ($query) =>
                        $query->whereNull('modelo'),
                    fn ($query) =>
                        $query->where(
                            'modelo',
                            $modelo
                        )
                )
                ->first();

            if ($duplicado) {
                throw ValidationException::withMessages([
                    'nombre' =>
                        'Ya existe un producto con la misma categoría, marca, nombre y modelo.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Código interno del producto
            |--------------------------------------------------------------------------
            */

            $codigo = $this->generarCodigoProducto(
                $categoria,
                $marca,
                $nombre,
                $modelo
            );

            /*
            |--------------------------------------------------------------------------
            | Registro
            |--------------------------------------------------------------------------
            */

            $producto = Producto::create([
                'categoria_producto_id' =>
                    $categoria->id,

                'marca_id' =>
                    $marca?->id,

                'codigo' =>
                    $codigo,

                'nombre' =>
                    $nombre,

                'modelo' =>
                    $modelo,

                'descripcion' =>
                    $descripcion,

                /*
                 * Para equipos importados el comportamiento
                 * por defecto será serializado.
                 */
                'es_serializado' =>
                    array_key_exists(
                        'es_serializado',
                        $datos
                    )
                        ? (bool) $datos['es_serializado']
                        : true,

                'activo' =>
                    true,
            ]);

            return $producto->fresh([
                'categoria',
                'marca',
            ]);
        }, 3);
    }

    /**
     * Alta rápida de proveedor.
     */
    public function crearProveedorRapido(
        int $usuarioId,
        array $datos
    ): Proveedor {
        return DB::transaction(function () use (
            $usuarioId,
            $datos
        ) {
            $this->obtenerUsuarioAutorizado(
                $usuarioId
            );

            $nombre = trim(
                (string) ($datos['nombre'] ?? '')
            );

            $pais = $this->normalizarNullable(
                $datos['pais'] ?? null
            );

            $ciudad = $this->normalizarNullable(
                $datos['ciudad'] ?? null
            );

            $telefono = $this->normalizarNullable(
                $datos['telefono'] ?? null
            );

            $correo = $this->normalizarNullable(
                $datos['correo'] ?? null
            );

            $contacto = $this->normalizarNullable(
                $datos['contacto'] ?? null
            );

            $observacion = $this->normalizarNullable(
                $datos['observacion'] ?? null
            );

            /*
            |--------------------------------------------------------------------------
            | Validaciones
            |--------------------------------------------------------------------------
            */

            if ($nombre === '') {
                throw ValidationException::withMessages([
                    'nombre' =>
                        'El nombre del proveedor es obligatorio.',
                ]);
            }

            if (mb_strlen($nombre) > 150) {
                throw ValidationException::withMessages([
                    'nombre' =>
                        'El nombre del proveedor no puede superar 150 caracteres.',
                ]);
            }

            if (
                $pais !== null
                && mb_strlen($pais) > 100
            ) {
                throw ValidationException::withMessages([
                    'pais' =>
                        'El país no puede superar 100 caracteres.',
                ]);
            }

            if (
                $ciudad !== null
                && mb_strlen($ciudad) > 100
            ) {
                throw ValidationException::withMessages([
                    'ciudad' =>
                        'La ciudad no puede superar 100 caracteres.',
                ]);
            }

            if (
                $telefono !== null
                && mb_strlen($telefono) > 30
            ) {
                throw ValidationException::withMessages([
                    'telefono' =>
                        'El teléfono no puede superar 30 caracteres.',
                ]);
            }

            if (
                $correo !== null
                && !filter_var(
                    $correo,
                    FILTER_VALIDATE_EMAIL
                )
            ) {
                throw ValidationException::withMessages([
                    'correo' =>
                        'El correo electrónico no tiene un formato válido.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Evitar duplicados evidentes
            |--------------------------------------------------------------------------
            */

            $duplicado = Proveedor::query()
                ->where(
                    'nombre',
                    $nombre
                )
                ->when(
                    $pais === null,
                    fn ($query) =>
                        $query->whereNull('pais'),
                    fn ($query) =>
                        $query->where(
                            'pais',
                            $pais
                        )
                )
                ->first();

            if ($duplicado) {
                throw ValidationException::withMessages([
                    'nombre' =>
                        'Ya existe un proveedor con el mismo nombre y país.',
                ]);
            }

            return Proveedor::create([
                'nombre' =>
                    $nombre,

                'pais' =>
                    $pais,

                'ciudad' =>
                    $ciudad,

                'telefono' =>
                    $telefono,

                'correo' =>
                    $correo,

                'contacto' =>
                    $contacto,

                'observacion' =>
                    $observacion,

                'activo' =>
                    true,
            ]);
        }, 3);
    }

    /**
     * Obtiene categoría existente o crea una nueva.
     */
    private function resolverCategoria(
        array $datos
    ): CategoriaProducto {
        $categoriaId =
            $datos['categoria_producto_id']
            ?? null;

        $nuevaCategoriaNombre = trim(
            (string) (
                $datos['nueva_categoria_nombre']
                ?? ''
            )
        );

        /*
         * Si el usuario escribió una nueva categoría,
         * esta opción tiene prioridad.
         */
        if ($nuevaCategoriaNombre !== '') {

            if (
                mb_strlen(
                    $nuevaCategoriaNombre
                ) > 100
            ) {
                throw ValidationException::withMessages([
                    'nueva_categoria_nombre' =>
                        'La categoría no puede superar 100 caracteres.',
                ]);
            }

            /*
             * Si ya existe, reutilizamos el catálogo.
             */
            $existente = CategoriaProducto::query()
                ->where(
                    'nombre',
                    $nuevaCategoriaNombre
                )
                ->first();

            if ($existente) {

                if (!$existente->activo) {
                    $existente->update([
                        'activo' => true,
                    ]);
                }

                return $existente;
            }

            return CategoriaProducto::create([
                'codigo' =>
                    $this->generarCodigoCategoria(
                        $nuevaCategoriaNombre
                    ),

                'nombre' =>
                    $nuevaCategoriaNombre,

                'descripcion' =>
                    null,

                'activo' =>
                    true,
            ]);
        }

        if (!$categoriaId) {
            throw ValidationException::withMessages([
                'categoria_producto_id' =>
                    'Debe seleccionar una categoría o registrar una nueva.',
            ]);
        }

        $categoria = CategoriaProducto::query()
            ->where('activo', true)
            ->find($categoriaId);

        if (!$categoria) {
            throw ValidationException::withMessages([
                'categoria_producto_id' =>
                    'La categoría seleccionada no existe o se encuentra inactiva.',
            ]);
        }

        return $categoria;
    }

    /**
     * Obtiene marca existente o crea una nueva.
     *
     * La marca sigue siendo nullable porque así está
     * diseñado actualmente el catálogo productos.
     */
    private function resolverMarca(
        array $datos
    ): ?Marca {
        $marcaId =
            $datos['marca_id']
            ?? null;

        $nuevaMarcaNombre = trim(
            (string) (
                $datos['nueva_marca_nombre']
                ?? ''
            )
        );

        if ($nuevaMarcaNombre !== '') {

            if (
                mb_strlen(
                    $nuevaMarcaNombre
                ) > 100
            ) {
                throw ValidationException::withMessages([
                    'nueva_marca_nombre' =>
                        'La marca no puede superar 100 caracteres.',
                ]);
            }

            $existente = Marca::query()
                ->where(
                    'nombre',
                    $nuevaMarcaNombre
                )
                ->first();

            if ($existente) {

                if (!$existente->activo) {
                    $existente->update([
                        'activo' => true,
                    ]);
                }

                return $existente;
            }

            return Marca::create([
                'nombre' =>
                    $nuevaMarcaNombre,

                'descripcion' =>
                    null,

                'activo' =>
                    true,
            ]);
        }

        /*
         * La marca puede omitirse.
         */
        if (!$marcaId) {
            return null;
        }

        $marca = Marca::query()
            ->where('activo', true)
            ->find($marcaId);

        if (!$marca) {
            throw ValidationException::withMessages([
                'marca_id' =>
                    'La marca seleccionada no existe o se encuentra inactiva.',
            ]);
        }

        return $marca;
    }

    /**
     * Genera código único de catálogo.
     *
     * Ejemplo:
     * LAPTOP-DELL-LATITUDE-5420
     */
    private function generarCodigoProducto(
        CategoriaProducto $categoria,
        ?Marca $marca,
        string $nombre,
        ?string $modelo
    ): string {
        $partes = array_filter([
            $categoria->codigo,
            $marca?->nombre,
            $nombre,
            $modelo,
        ]);

        $base = strtoupper(
            Str::slug(
                implode(
                    '-',
                    $partes
                ),
                '-'
            )
        );

        if ($base === '') {
            $base = 'PRODUCTO';
        }

        /*
         * productos.codigo = varchar(50)
         * Reservamos espacio para sufijos.
         */
        $base = mb_substr(
            $base,
            0,
            44
        );

        $codigo = $base;
        $secuencia = 2;

        while (
            Producto::query()
                ->where(
                    'codigo',
                    $codigo
                )
                ->exists()
        ) {
            $codigo =
                mb_substr(
                    $base,
                    0,
                    44
                )
                . '-'
                . $secuencia;

            $secuencia++;
        }

        return $codigo;
    }

    /**
     * Genera código único para nuevas categorías.
     *
     * Ejemplo:
     * "Consola de videojuegos"
     * →
     * CONSOLA_DE_VIDEOJUEGOS
     */
    private function generarCodigoCategoria(
        string $nombre
    ): string {
        $base = strtoupper(
            Str::slug(
                $nombre,
                '_'
            )
        );

        if ($base === '') {
            $base = 'CATEGORIA';
        }

        $base = mb_substr(
            $base,
            0,
            44
        );

        $codigo = $base;
        $secuencia = 2;

        while (
            CategoriaProducto::query()
                ->where(
                    'codigo',
                    $codigo
                )
                ->exists()
        ) {
            $codigo =
                mb_substr(
                    $base,
                    0,
                    44
                )
                . '_'
                . $secuencia;

            $secuencia++;
        }

        return $codigo;
    }

    /**
     * Seguridad de dominio.
     */
    private function obtenerUsuarioAutorizado(
        int $usuarioId
    ): User {
        $usuario = User::query()
            ->where('activo', true)
            ->find($usuarioId);

        if (!$usuario) {
            throw new ReglaNegocioException(
                'El usuario no existe o se encuentra inactivo.'
            );
        }

        if (
            !$usuario->tienePermiso(
                'importacion.gestionar'
            )
        ) {
            throw new ReglaNegocioException(
                'El usuario no cuenta con permiso para gestionar catálogos desde importaciones.'
            );
        }

        return $usuario;
    }

    private function normalizarNullable(
        mixed $valor
    ): ?string {
        if ($valor === null) {
            return null;
        }

        $valor = trim(
            (string) $valor
        );

        return $valor === ''
            ? null
            : $valor;
    }
}