<?php

use App\Http\Controllers\InventarioController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

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