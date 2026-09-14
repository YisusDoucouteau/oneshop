<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ImportacionController;
use App\Http\Controllers\CatalogoImportacionController;
use App\Http\Controllers\UnidadAdquiridaController;
use App\Http\Controllers\RecepcionLoteController;
use App\Http\Controllers\CostoLoteController;
use App\Services\UnidadAdquiridaService;
use Illuminate\Support\Facades\Route;



Route::get('/', function () {
    return view('welcome');
});



Route::get(
    '/dashboard',
    [DashboardController::class, 'index']
)
->middleware([
    'auth',
    'usuario.activo',
])
->name('dashboard');





Route::middleware('auth')->group(function () {


    Route::get(
        '/profile',
        [ProfileController::class, 'edit']
    )
    ->name('profile.edit');


    Route::patch(
        '/profile',
        [ProfileController::class, 'update']
    )
    ->name('profile.update');


});






Route::middleware([
    'auth',
    'usuario.activo',
])->group(function () {



/*
|--------------------------------------------------------------------------
| IMPORTACIONES
|--------------------------------------------------------------------------
*/

Route::get(
    '/importaciones/unidades/{unidad}/editar',
    [RecepcionLoteController::class,'editar']
)
->name('importaciones.unidades.editar');


Route::patch(
    '/importaciones/unidades/{unidad}',
    [RecepcionLoteController::class,'actualizar']
)
->name('importaciones.unidades.actualizar');



Route::get(
    '/importaciones/unidades/{unidad}/editar',
    [RecepcionLoteController::class,'editar']
)->name('importaciones.unidades.editar');


Route::patch(
    '/importaciones/unidades/{unidad}',
    [RecepcionLoteController::class,'actualizar']
)->name('importaciones.unidades.actualizar');
Route::post(
    '/importaciones/{lote}/costos',
    [
        CostoLoteController::class,
        'store'
    ]
)
->middleware('permiso:importacion.gestionar')
->name('importaciones.costos.store');

Route::get(
    '/importaciones',
    [ImportacionController::class, 'index']
)
->middleware('permiso:importacion.ver')
->name('importaciones.index');


Route::put(
'importaciones/costos/{costo}',
[ CostoLoteController::class,'update']
)
->name('importaciones.costos.update');


Route::patch(
'importaciones/costos/{costo}/anular',
[ CostoLoteController::class,'anular']
)
->name('importaciones.costos.anular');


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





Route::get(
    '/importaciones/{lote}',
    [ImportacionController::class, 'show']
)
->middleware('permiso:importacion.ver')
->name('importaciones.show');





Route::post(
    '/importaciones/{lote}/detalles',
    [ImportacionController::class, 'storeDetalle']
)
->middleware('permiso:importacion.gestionar')
->name('importaciones.detalles.store');


Route::post(
    '/importaciones/{lote}/costos/distribuir',
    [
        CostoLoteController::class,
        'distribuir'
    ]
)
->name(
    'importaciones.costos.distribuir'
);


/*
|--------------------------------------------------------------------------
| REGISTRO DE EQUIPOS RECIBIDOS POR HUGO
|--------------------------------------------------------------------------
*/



Route::post(
    '/importaciones/{lote}/unidades',
    [
        ImportacionController::class,
        'storeUnidad'
    ]
)
->middleware('permiso:importacion.gestionar')
->name('importaciones.unidades.store');

Route::patch(
    'importaciones/unidades/{unidad}/anular',
    [
        ImportacionController::class,
        'anularUnidad'
    ]
)
->name(
    'importaciones.unidades.anular'
);




/*
|--------------------------------------------------------------------------
| RECEPCIÓN ANTIGUA (mantener por ahora)
|--------------------------------------------------------------------------
*/



Route::get(
    '/importaciones/{lote}/detalles/{detalle}/recepcion',
    [RecepcionLoteController::class,'create']
)
->middleware([
    'permiso:importacion.gestionar',
    'permiso:inventario.registrar',
])
->name('importaciones.recepcion.create');





Route::post(
    '/importaciones/{lote}/detalles/{detalle}/recepcion',
    [RecepcionLoteController::class,'store']
)
->middleware([
    'permiso:importacion.gestionar',
    'permiso:inventario.registrar',
])
->name('importaciones.recepcion.store');







/*
|--------------------------------------------------------------------------
| CATÁLOGOS RÁPIDOS
|--------------------------------------------------------------------------
*/



Route::post(
    '/importaciones/catalogo/productos',
    [CatalogoImportacionController::class,'storeProducto']
)
->middleware('permiso:importacion.gestionar')
->name('importaciones.catalogo.productos.store');





Route::post(
    '/importaciones/catalogo/proveedores',
    [CatalogoImportacionController::class,'storeProveedor']
)
->middleware('permiso:importacion.gestionar')
->name('importaciones.catalogo.proveedores.store');







/*
|--------------------------------------------------------------------------
| UNIDADES ADQUIRIDAS
|--------------------------------------------------------------------------
*/


Route::get(
    '/unidades-adquiridas',
    [UnidadAdquiridaController::class,'index']
)
->middleware('permiso:importacion.ver')
->name('unidades-adquiridas.index');



Route::get(
    '/unidades-adquiridas/crear',
    [UnidadAdquiridaController::class,'create']
)
->middleware('permiso:importacion.gestionar')
->name('unidades-adquiridas.create');



Route::post(
    '/unidades-adquiridas',
    [UnidadAdquiridaController::class,'store']
)
->middleware('permiso:importacion.gestionar')
->name('unidades-adquiridas.store');



Route::get(
    '/unidades-adquiridas/{unidad}',
    [UnidadAdquiridaController::class,'show']
)
->middleware('permiso:importacion.ver')
->name('unidades-adquiridas.show');

Route::post(
    '/unidades-adquiridas/{unidad}/incorporar',
    [UnidadAdquiridaController::class, 'incorporar']
)
->middleware('permiso:inventario.registrar')
->name('unidades-adquiridas.incorporar');

Route::patch(
    '/unidades-adquiridas/{unidad}/revision',
    [UnidadAdquiridaController::class, 'revision']
)
->middleware('permiso:importacion.gestionar')
->name('unidades-adquiridas.revision');

/*
|--------------------------------------------------------------------------
| INVENTARIO
|--------------------------------------------------------------------------
*/



Route::get(
    '/inventario',
    [InventarioController::class,'index']
)
->middleware('permiso:inventario.ver')
->name('inventario.index');





Route::get(
    '/inventario/crear',
    [InventarioController::class,'create']
)
->middleware('permiso:inventario.registrar')
->name('inventario.create');





Route::post(
    '/inventario',
    [InventarioController::class,'store']
)
->middleware('permiso:inventario.registrar')
->name('inventario.store');





/*
|--------------------------------------------------------------------------
| IMPORTANTE:
| La ruta dinámica siempre al final
|--------------------------------------------------------------------------
*/



Route::get(
    '/inventario/{equipo:codigo_interno}',
    [InventarioController::class,'show']
)
->middleware('permiso:inventario.ver')
->name('inventario.show');



});




require __DIR__.'/auth.php';