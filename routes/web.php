<?php

use App\Http\Controllers\InventarioController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImportacionController;
use App\Http\Controllers\CatalogoImportacionController;
Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})
    ->middleware([
        'auth',
        'usuario.activo',
    ])
    ->name('dashboard');


Route::middleware('auth')->group(function () {

    Route::get(
        '/profile',
        [ProfileController::class, 'edit']
    )->name('profile.edit');

    Route::patch(
        '/profile',
        [ProfileController::class, 'update']
    )->name('profile.update');

});


Route::middleware([
    'auth',
    'usuario.activo',
])->group(function () {
/*
|--------------------------------------------------------------------------
| Importaciones
|--------------------------------------------------------------------------
*/

Route::get(
    '/importaciones',
    [ImportacionController::class, 'index']
)
    ->middleware('permiso:importacion.ver')
    ->name('importaciones.index');


Route::get(
    '/importaciones/crear',
    [ImportacionController::class, 'create']
)
    ->middleware('permiso:importacion.gestionar')
    ->name('importaciones.create');


Route::post(
    '/importaciones',
    [ImportacionController::class, 'store']
)
    ->middleware('permiso:importacion.gestionar')
    ->name('importaciones.store');


Route::post(
    '/importaciones/{lote}/detalles',
    [ImportacionController::class, 'storeDetalle']
)
    ->middleware('permiso:importacion.gestionar')
    ->name('importaciones.detalles.store');


Route::get(
    '/importaciones/{lote}',
    [ImportacionController::class, 'show']
)
    ->middleware('permiso:importacion.ver')
    ->name('importaciones.show');
    Route::post(
    '/importaciones/catalogo/productos',
    [CatalogoImportacionController::class, 'storeProducto']
)
    ->middleware('permiso:importacion.gestionar')
    ->name('importaciones.catalogo.productos.store');


Route::post(
    '/importaciones/catalogo/proveedores',
    [CatalogoImportacionController::class, 'storeProveedor']
)
    ->middleware('permiso:importacion.gestionar')
    ->name('importaciones.catalogo.proveedores.store');
    /*
    |--------------------------------------------------------------------------
    | Inventario
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/inventario',
        [InventarioController::class, 'index']
    )
        ->middleware('permiso:inventario.ver')
        ->name('inventario.index');


    Route::get(
        '/inventario/crear',
        [InventarioController::class, 'create']
    )
        ->middleware('permiso:inventario.registrar')
        ->name('inventario.create');


    Route::post(
        '/inventario',
        [InventarioController::class, 'store']
    )
        ->middleware('permiso:inventario.registrar')
        ->name('inventario.store');


    /*
     * La ruta dinámica siempre queda al final.
     */
    Route::get(
        '/inventario/{equipo:codigo_interno}',
        [InventarioController::class, 'show']
    )
        ->middleware('permiso:inventario.ver')
        ->name('inventario.show');

});


require __DIR__.'/auth.php';