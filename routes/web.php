<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ImportacionController;
use App\Http\Controllers\CatalogoImportacionController;
use App\Http\Controllers\UnidadAdquiridaController;
use App\Http\Controllers\RecepcionLoteController;
use App\Http\Controllers\EnvioImportacionController;
use App\Models\Producto;
use App\Models\Moneda;
use App\Http\Controllers\AnulacionVentaController;
use App\Http\Controllers\PrecioEquipoController;
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\PagoVentaController;
use App\Http\Controllers\CostoLoteController;
use App\Http\Controllers\IntervencionUnidadAdquiridaController;
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
->middleware('permiso:importacion.gestionar')
->name('importaciones.unidades.editar');


Route::patch(
    '/importaciones/unidades/{unidad}',
    [RecepcionLoteController::class,'actualizar']
)
->middleware('permiso:importacion.gestionar')
->name('importaciones.unidades.actualizar');
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
->middleware('permiso:importacion.gestionar')
->name('importaciones.costos.update');


Route::patch(
'importaciones/costos/{costo}/anular',
[ CostoLoteController::class,'anular']
)
->middleware('permiso:importacion.gestionar')
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
->middleware('permiso:importacion.gestionar')
->name(
    'importaciones.unidades.anular'
);




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

Route::patch(
    '/unidades-adquiridas/{unidad}/reabrir-preparacion',
    [UnidadAdquiridaController::class, 'reabrirPreparacion']
)
->middleware('permiso:importacion.gestionar')
->name('unidades-adquiridas.reabrir-preparacion');

   Route::post(
    '/unidades-adquiridas/{unidad}/intervenciones/componente-externo',
    [
        IntervencionUnidadAdquiridaController::class,
        'componenteExterno',
    ]
)
->middleware('permiso:importacion.gestionar')
->name(
    'unidades-adquiridas.intervenciones.componente-externo'
);


Route::post(
    '/unidades-adquiridas/{unidad}/intervenciones/componente-stock',
    [
        IntervencionUnidadAdquiridaController::class,
        'componenteStock',
    ]
)
->middleware('permiso:importacion.gestionar')
->name(
    'unidades-adquiridas.intervenciones.componente-stock'
);


Route::post(
    '/unidades-adquiridas/{unidad}/intervenciones/servicio',
    [
        IntervencionUnidadAdquiridaController::class,
        'servicio',
    ]
)
->middleware('permiso:importacion.gestionar')
->name(
    'unidades-adquiridas.intervenciones.servicio'
); 
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
| Envíos de importación Cochabamba -> Oruro
|--------------------------------------------------------------------------
*/

Route::get(
    '/envios-importacion',
    [
        EnvioImportacionController::class,
        'index',
    ]
)
->middleware('permiso:importacion.ver')
->name('envios-importacion.index');


Route::post(
    '/envios-importacion',
    [
        EnvioImportacionController::class,
        'store',
    ]
)
->middleware('permiso:importacion.gestionar')
->name('envios-importacion.store');


Route::get(
    '/envios-importacion/{envio}',
    [
        EnvioImportacionController::class,
        'show',
    ]
)
->middleware('permiso:importacion.ver')
->name('envios-importacion.show');


Route::post(
    '/envios-importacion/{envio}/unidades/{unidad}',
    [
        EnvioImportacionController::class,
        'agregarUnidad',
    ]
)
->middleware('permiso:importacion.gestionar')
->name('envios-importacion.unidades.agregar');


Route::delete(
    '/envios-importacion/{envio}/unidades/{unidad}',
    [
        EnvioImportacionController::class,
        'quitarUnidad',
    ]
)
->middleware('permiso:importacion.gestionar')
->name('envios-importacion.unidades.quitar');


Route::post(
    '/envios-importacion/{envio}/unidades/{unidad}/cargador',
    [
        EnvioImportacionController::class,
        'actualizarCargadorUnidad',
    ]
)
->middleware('permiso:importacion.gestionar')
->name('envios-importacion.unidades.cargador');


Route::post(
    '/envios-importacion/{envio}/preparar',
    [
        EnvioImportacionController::class,
        'preparar',
    ]
)
->middleware('permiso:importacion.gestionar')
->name('envios-importacion.preparar');


Route::post(
    '/envios-importacion/{envio}/reabrir',
    [
        EnvioImportacionController::class,
        'reabrir',
    ]
)
->middleware('permiso:importacion.gestionar')
->name('envios-importacion.reabrir');


Route::post(
    '/envios-importacion/{envio}/cancelar',
    [
        EnvioImportacionController::class,
        'cancelar',
    ]
)
->middleware('permiso:importacion.gestionar')
->name('envios-importacion.cancelar');


Route::post(
    '/envios-importacion/{envio}/despachar',
    [
        EnvioImportacionController::class,
        'despachar',
    ]
)
->middleware('permiso:importacion.gestionar')
->name('envios-importacion.despachar');


Route::post(
    '/envios-importacion/{envio}/verificar-recepcion',
    [
        EnvioImportacionController::class,
        'verificarRecepcion',
    ]
)
->middleware('permiso:importacion.gestionar')
->name('envios-importacion.verificar-recepcion');


Route::post(
    '/envios-importacion/{envio}/unidades/{unidad}/recibir',
    [
        EnvioImportacionController::class,
        'recibirUnidad',
    ]
)
->middleware('permiso:importacion.gestionar')
->name('envios-importacion.unidades.recibir');


Route::post(
    '/envios-importacion/{envio}/unidades/{unidad}/faltante',
    [
        EnvioImportacionController::class,
        'marcarFaltante',
    ]
)
->middleware('permiso:importacion.gestionar')
->name('envios-importacion.unidades.faltante');


Route::post(
    '/envios-importacion/{envio}/unidades/{unidad}/incidencia',
    [
        EnvioImportacionController::class,
        'registrarIncidencia',
    ]
)
->middleware('permiso:importacion.gestionar')
->name('envios-importacion.unidades.incidencia');


Route::post(
    '/envios-importacion/{envio}/cerrar-recepcion',
    [
        EnvioImportacionController::class,
        'cerrarRecepcion',
    ]
)
->middleware('permiso:importacion.gestionar')
->name('envios-importacion.cerrar-recepcion');

/*
|--------------------------------------------------------------------------
| RESERVAS
|--------------------------------------------------------------------------
*/


Route::get(
    '/reservas',
    [ReservaController::class,'index']
)
->middleware('permiso:reservas.ver')
->name('reservas.index');


Route::get(
    '/reservas/crear',
    [ReservaController::class,'create']
)
->middleware('permiso:reservas.gestionar')
->name('reservas.create');


Route::post(
    '/reservas',
    [ReservaController::class,'store']
)
->middleware('permiso:reservas.gestionar')
->name('reservas.store');


Route::get(
    '/reservas/{reserva}',
    [ReservaController::class,'show']
)
->middleware('permiso:reservas.ver')
->name('reservas.show');


Route::post(
    '/reservas/{reserva}/cancelar',
    [ReservaController::class,'cancelar']
)
->middleware('permiso:reservas.gestionar')
->name('reservas.cancelar');


Route::post(
    '/reservas/{reserva}/convertir-venta',
    [ReservaController::class,'convertirVenta']
)
->middleware('permiso:ventas.crear')
->name('reservas.convertirVenta');

/*
|--------------------------------------------------------------------------
| VENTAS Y PAGOS
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Listado de ventas
|--------------------------------------------------------------------------
*/

Route::get(
    '/ventas',
    [VentaController::class, 'index']
)
->middleware('permiso:ventas.ver')
->name('ventas.index');


/*
|--------------------------------------------------------------------------
| Registrar venta directa
|--------------------------------------------------------------------------
|
| IMPORTANTE:
| Esta ruta debe ir antes de /ventas/{venta}
|
*/

Route::get(
    '/ventas/crear',
    [VentaController::class, 'create']
)
->middleware('permiso:ventas.crear')
->name('ventas.create');


/*
|--------------------------------------------------------------------------
| Evaluar precio de venta
|--------------------------------------------------------------------------
|
| Esta ruta permite validar precio, descuento y GANANCIA
| antes de registrar definitivamente la venta.
|
*/

Route::post(
    '/ventas/equipos/{equipo:codigo_interno}/evaluar-json',
    [VentaController::class, 'evaluarPrecio']
)
->middleware('permiso:ventas.crear')
->name('ventas.equipos.evaluar-json');


/*
|--------------------------------------------------------------------------
| Guardar venta directa
|--------------------------------------------------------------------------
*/

Route::post(
    '/ventas',
    [VentaController::class, 'store']
)
->middleware('permiso:ventas.crear')
->name('ventas.store');


/*
|--------------------------------------------------------------------------
| Detalle de venta
|--------------------------------------------------------------------------
|
| La ruta dinámica debe quedar después de las rutas específicas.
|
*/

Route::get(
    '/ventas/{venta}',
    [VentaController::class, 'show']
)
->middleware('permiso:ventas.ver')
->name('ventas.show');
Route::post(
    '/ventas/{venta}/anular',
    [AnulacionVentaController::class, 'store']
)
->middleware('permiso:ventas.anular')
->name('ventas.anular');

/*
|--------------------------------------------------------------------------
| Pagos de venta
|--------------------------------------------------------------------------
*/

Route::post(
    '/ventas/{venta}/pagos',
    [PagoVentaController::class, 'store']
)
->middleware('permiso:pagos.registrar')
->name('ventas.pagos.store');
/*
|--------------------------------------------------------------------------
| IMPORTANTE:
| La ruta dinámica siempre al final
|--------------------------------------------------------------------------
*/
/*
|--------------------------------------------------------------------------
| Nota de venta y garantía
|--------------------------------------------------------------------------
*/

Route::get(
    '/ventas/{venta}/boleta',
    [VentaController::class, 'boleta']
)
->middleware('permiso:ventas.ver')
->name('ventas.boleta');
/*
|--------------------------------------------------------------------------
| FASE 7.3C2 - TIPO DE CAMBIO COMERCIAL
|--------------------------------------------------------------------------
|
|
*/

Route::post(
    '/inventario/{equipo:codigo_interno}/precio/tipo-cambio',
    [PrecioEquipoController::class, 'actualizarTipoCambio']
)
->middleware('permiso:precios.modificar')
->name('precios.equipos.tipo-cambio');
/*
|--------------------------------------------------------------------------
| FASE 7.3C3 - EVALUACIÓN AJAX Y TC GLOBAL
|--------------------------------------------------------------------------
|
| Agregar junto a las demás rutas de precios y antes de:
| /inventario/{equipo:codigo_interno}
|
*/

Route::post(
    '/inventario/{equipo:codigo_interno}/precio/evaluar-json',
    [PrecioEquipoController::class, 'evaluarAjax']
)
->middleware('permiso:precios.ver')
->name('precios.equipos.evaluar-json');

Route::post(
    '/precios/tipo-cambio',
    [PrecioEquipoController::class, 'actualizarTipoCambioGlobal']
)
->middleware('permiso:precios.modificar')
->name('precios.tipo-cambio.store');

/*
|--------------------------------------------------------------------------
| IMPORTANTE
|--------------------------------------------------------------------------
|
| Si agregaste en 7.3C2 la ruta anterior:
|
| /inventario/{equipo:codigo_interno}/precio/tipo-cambio
| -> precios.equipos.tipo-cambio
|
| puedes ELIMINARLA. El tipo de cambio ahora es explícitamente GLOBAL,
| no pertenece a un equipo.
|
*/

/*
|--------------------------------------------------------------------------
| PRECIOS DE EQUIPOS
|--------------------------------------------------------------------------

|
*/
Route::get(
    '/inventario/{equipo:codigo_interno}/precio',
    [PrecioEquipoController::class, 'show']
)
->middleware('permiso:precios.ver')
->name('precios.equipos.show');

Route::post(
    '/inventario/{equipo:codigo_interno}/precio/evaluar',
    [PrecioEquipoController::class, 'evaluar']
)
->middleware('permiso:precios.ver')
->name('precios.equipos.evaluar');

Route::post(
    '/inventario/{equipo:codigo_interno}/precio',
    [PrecioEquipoController::class, 'store']
)
->middleware('permiso:precios.modificar')
->name('precios.equipos.store');


Route::get(
    '/inventario/{equipo:codigo_interno}',
    [InventarioController::class,'show']
)
->middleware('permiso:inventario.ver')
->name('inventario.show');



});




require __DIR__.'/auth.php';
