<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InventarioController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
Route::get(
    '/inventario/{equipo:codigo_interno}',
    [InventarioController::class, 'show']
)
    ->middleware('permiso:inventario.ver')
    ->name('inventario.show');
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware([
    'auth',
    'usuario.activo',
])->group(function () {

    Route::get(
        '/inventario',
        [InventarioController::class, 'index']
    )
        ->middleware('permiso:inventario.ver')
        ->name('inventario.index');

});
require __DIR__.'/auth.php';
