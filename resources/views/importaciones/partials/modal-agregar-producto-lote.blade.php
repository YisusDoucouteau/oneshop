<div
    id="modalAgregarProductoLote"
    class="fixed inset-0 z-40 hidden bg-slate-950/50 p-4 backdrop-blur-sm"
    onclick="cerrarModalAgregarProductoLoteDesdeFondo(event)"
>
    <div class="flex min-h-full items-center justify-center">
        <div id="ventanaAgregarProductoLote" class="flex max-h-[94vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl border border-blue-100 bg-white shadow-2xl">
            <div class="flex shrink-0 items-start justify-between gap-4 border-b border-blue-100 bg-gradient-to-r from-blue-50 via-oneshop-soft to-white px-6 py-5">
                <div class="flex items-start gap-3">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-blue-200 bg-white text-oneshop-primary shadow-sm">
                        <x-ui.icon name="package" size="19" />
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-oneshop-primary">Composición del lote</p>
                        <h2 class="mt-0.5 text-xl font-bold text-slate-950">Agregar producto</h2>
                        <p class="mt-1 text-sm text-slate-500">Define el producto, cantidad esperada y costo de compra.</p>
                    </div>
                </div>

                <button type="button" onclick="cerrarModalAgregarProductoLote()" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-500 transition hover:bg-blue-100 hover:text-slate-900">
                    <x-ui.icon name="x" size="17" />
                </button>
            </div>

            <form id="formAgregarProducto" class="flex min-h-0 flex-1 flex-col">
                @csrf

                <div class="min-h-0 flex-1 overflow-y-auto p-6">
                    <div class="space-y-6">
                        <div id="errorAgregarProductoLote" class="hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800"></div>

                        <section>
                            <div class="mb-4">
                                <p class="text-xs font-bold uppercase tracking-[0.12em] text-oneshop-primary">Producto y cantidad</p>
                                <p class="mt-1 text-sm text-slate-500">Registra qué esperas recibir dentro de este lote.</p>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-12">
                                <div class="relative xl:col-span-6">
                                    <label for="buscarProductoLote" class="mb-2 block text-sm font-bold text-slate-700">
                                        Producto <span class="text-red-700">*</span>
                                    </label>

                                    <select id="selectProducto" name="producto_id" class="hidden" tabindex="-1" aria-hidden="true" required>
                                        <option value="">Seleccionar producto</option>
                                        <option value="crear">+ Crear producto nuevo</option>
                                        @foreach($productos as $producto)
                                            <option value="{{ $producto->id }}">
                                                {{ $producto->marca?->nombre }} {{ $producto->nombre }} {{ $producto->modelo }}
                                            </option>
                                        @endforeach
                                    </select>

                                    <div id="productoSeleccionadoLote" class="hidden rounded-xl border border-blue-200 bg-blue-50/50 px-4 py-3">
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="text-xs font-bold uppercase tracking-wide text-oneshop-primary">Seleccionado</p>
                                                <p id="productoSeleccionadoTexto" class="mt-0.5 truncate text-sm font-bold text-slate-900"></p>
                                            </div>
                                            <button type="button" id="cambiarProductoLote" class="shrink-0 rounded-lg border border-blue-200 bg-white px-3 py-1.5 text-xs font-bold text-oneshop-dark hover:bg-blue-50">Cambiar</button>
                                        </div>
                                    </div>

                                    <div id="buscadorProductoLoteContenedor">
                                        <div class="relative">
                                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                                <x-ui.icon name="search" size="16" />
                                            </div>
                                            <input
                                                type="search"
                                                id="buscarProductoLote"
                                                autocomplete="off"
                                                placeholder="Buscar por marca, producto o modelo..."
                                                class="w-full rounded-xl border border-slate-300 bg-white py-2.5 pl-10 pr-4 text-sm text-slate-900 placeholder:text-slate-400 focus:border-oneshop-primary focus:outline-none focus:ring-4 focus:ring-blue-100"
                                            >
                                        </div>

                                        <div id="listaProductosLote" class="absolute left-0 right-0 z-20 mt-2 hidden max-h-72 overflow-y-auto rounded-xl border border-slate-200 bg-white p-2 shadow-xl">
                                            <button
                                                type="button"
                                                id="crearProductoDesdeLote"
                                                class="mb-1 flex w-full items-center justify-between rounded-lg border border-blue-100 bg-blue-50 px-3 py-2.5 text-left text-sm font-bold text-oneshop-dark hover:bg-blue-100"
                                            >
                                                <span>+ Crear producto nuevo</span>
                                                <span class="text-xs font-semibold text-blue-700">Catálogo</span>
                                            </button>

                                            <div id="sinProductosCoincidentes" class="hidden px-3 py-5 text-center text-sm text-slate-500">
                                                No se encontraron productos.
                                            </div>

                                            @foreach($productos as $producto)
                                                @php
                                                    $nombreProducto = trim(collect([
                                                        $producto->marca?->nombre,
                                                        $producto->nombre,
                                                        $producto->modelo,
                                                    ])->filter()->implode(' '));
                                                @endphp
                                                <button
                                                    type="button"
                                                    data-producto-opcion
                                                    data-id="{{ $producto->id }}"
                                                    data-nombre="{{ $nombreProducto }}"
                                                    data-search="{{ strtolower($nombreProducto) }}"
                                                    class="w-full rounded-lg px-3 py-2.5 text-left hover:bg-slate-50"
                                                >
                                                    <p class="text-sm font-bold text-slate-800">{{ $producto->marca?->nombre }} {{ $producto->nombre }}</p>
                                                    <p class="mt-0.5 text-xs text-slate-500">{{ $producto->modelo ?? 'Sin modelo' }}</p>
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <div class="xl:col-span-2">
                                    <label class="mb-2 block text-sm font-bold text-slate-700">Cantidad <span class="text-red-700">*</span></label>
                                    <input type="number" name="cantidad_esperada" value="1" min="1" required class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-oneshop-primary focus:outline-none focus:ring-4 focus:ring-blue-100">
                                </div>

                                <div class="xl:col-span-2">
                                    <label class="mb-2 block text-sm font-bold text-slate-700">Costo unitario</label>
                                    <input type="number" name="costo_unitario_origen" min="0" step="0.01" placeholder="0.00" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-oneshop-primary focus:outline-none focus:ring-4 focus:ring-blue-100">
                                </div>

                                <div class="xl:col-span-2">
                                    <label for="monedaDetalleLote" class="mb-2 block text-sm font-bold text-slate-700">Moneda</label>
                                    <select name="moneda_id" id="monedaDetalleLote" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-oneshop-primary focus:outline-none focus:ring-4 focus:ring-blue-100">
                                        <option value="">Seleccionar</option>
                                        @foreach($monedas as $moneda)
                                            <option value="{{ $moneda->id }}" data-codigo="{{ $moneda->codigo }}">{{ $moneda->codigo }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div id="grupoTipoCambioDetalle" class="mt-4 hidden rounded-xl border border-blue-100 bg-blue-50/40 p-4">
                                <div class="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <p class="text-xs font-bold uppercase tracking-[0.12em] text-oneshop-primary">Referencia cambiaria</p>
                                        @if($referenciaUsdBob)
                                            <div class="mt-2 flex items-center justify-between gap-3">
                                                <div>
                                                    <p class="text-sm font-bold text-slate-800">USD / BOB</p>
                                                    <p class="mt-0.5 text-xs text-slate-500">Referencia disponible por OneShop.</p>
                                                </div>
                                                <span class="text-lg font-bold text-oneshop-dark">{{ $referenciaUsdBob['tipo_cambio']->valor }}</span>
                                            </div>
                                            <button type="button" id="usarReferenciaDetalle" class="mt-3 rounded-lg border border-blue-200 bg-white px-3 py-2 text-xs font-bold text-oneshop-dark hover:bg-blue-50">Usar referencia</button>
                                        @else
                                            <p class="mt-2 text-sm text-slate-500">No hay una referencia USD/BOB disponible en este momento.</p>
                                            <button type="button" id="usarReferenciaDetalle" class="hidden"></button>
                                        @endif
                                    </div>

                                    <div>
                                        <label for="tipoCambioDetalleLote" class="mb-2 block text-sm font-bold text-slate-700">Tipo de cambio aplicado *</label>
                                        <input
                                            type="number"
                                            name="tipo_cambio_aplicado"
                                            id="tipoCambioDetalleLote"
                                            min="0.0001"
                                            step="0.0001"
                                            placeholder="0.0000"
                                            class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-oneshop-primary focus:outline-none focus:ring-4 focus:ring-blue-100"
                                        >
                                        <p id="notaTipoCambioDetalle" class="mt-2 text-xs text-slate-500">Guarda el tipo de cambio que realmente se aplicó a la compra.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4">
                                <label class="mb-2 block text-sm font-bold text-slate-700">Observación de la línea</label>
                                <textarea name="observacion" rows="2" placeholder="Ej. lote mixto, detalle informado por proveedor..." class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-oneshop-primary focus:outline-none focus:ring-4 focus:ring-blue-100"></textarea>
                            </div>
                        </section>

                        <details class="group overflow-hidden rounded-xl border border-slate-200">
                            <summary class="flex cursor-pointer list-none items-center justify-between px-5 py-4 transition hover:bg-slate-50">
                                <div>
                                    <p class="text-sm font-bold text-slate-800">Características y accesorios esperados</p>
                                    <p class="mt-0.5 text-xs text-slate-500">Opcional · información declarada por el proveedor.</p>
                                </div>
                                <span class="text-lg font-bold text-oneshop-primary transition-transform group-open:rotate-45">+</span>
                            </summary>

                            <div class="border-t border-slate-200 bg-slate-50/40 p-5">
                                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                    @foreach([
                                        ['procesador', 'Procesador'],
                                        ['generacion_procesador', 'Generación'],
                                        ['tarjeta_grafica', 'Tarjeta gráfica'],
                                        ['resolucion', 'Resolución'],
                                        ['sistema_operativo', 'Sistema operativo'],
                                    ] as [$campo, $label])
                                        <input
                                            name="especificacion_esperada[{{ $campo }}]"
                                            placeholder="{{ $label }}"
                                            class="rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-oneshop-primary focus:outline-none focus:ring-4 focus:ring-blue-100"
                                        >
                                    @endforeach

                                    <input type="number" min="0" name="especificacion_esperada[ram_gb]" placeholder="RAM (GB)" class="rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm">
                                    <input type="number" min="0" name="especificacion_esperada[almacenamiento_gb]" placeholder="Almacenamiento (GB)" class="rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm">
                                    <select name="especificacion_esperada[tipo_almacenamiento]" class="rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm">
                                        <option value="">Tipo de almacenamiento</option>
                                        <option value="SSD">SSD</option>
                                        <option value="HDD">HDD</option>
                                        <option value="NVME">NVMe</option>
                                        <option value="EMMC">eMMC</option>
                                    </select>
                                    <input type="number" min="0" step="0.1" name="especificacion_esperada[pantalla_pulgadas]" placeholder="Pantalla (pulgadas)" class="rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm">
                                </div>

                                <div class="mt-5 border-t border-slate-200 pt-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <p class="text-sm font-bold text-slate-800">Accesorios por equipo</p>
                                            <p class="mt-0.5 text-xs text-slate-500">Ej. cargador, adaptador o cable.</p>
                                        </div>
                                        <button type="button" id="agregarComponenteEsperado" class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-bold text-oneshop-dark hover:bg-blue-100">+ Agregar accesorio</button>
                                    </div>
                                    <div id="componentesEsperados" class="mt-3 space-y-3"></div>
                                </div>
                            </div>
                        </details>
                    </div>
                </div>

                <div class="flex shrink-0 flex-col-reverse gap-3 border-t border-blue-100 bg-blue-50/40 px-6 py-4 sm:flex-row sm:justify-end">
                    <button type="button" onclick="cerrarModalAgregarProductoLote()" class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-100">Cancelar</button>
                    <button type="submit" id="btnAgregarProductoLote" class="inline-flex items-center justify-center gap-2 rounded-xl border border-oneshop-primary bg-oneshop-light px-5 py-2.5 text-sm font-bold text-oneshop-dark shadow-sm transition hover:bg-blue-100">
                        <x-ui.icon name="plus" size="16" />
                        Agregar al lote
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const modal = document.getElementById('modalAgregarProductoLote');
    const formulario = document.getElementById('formAgregarProducto');
    const selectProducto = document.getElementById('selectProducto');
    const buscadorProducto = document.getElementById('buscarProductoLote');
    const contenedorBuscador = document.getElementById('buscadorProductoLoteContenedor');
    const listaProductos = document.getElementById('listaProductosLote');
    const opcionesProducto = Array.from(document.querySelectorAll('[data-producto-opcion]'));
    const sinCoincidencias = document.getElementById('sinProductosCoincidentes');
    const productoSeleccionado = document.getElementById('productoSeleccionadoLote');
    const productoSeleccionadoTexto = document.getElementById('productoSeleccionadoTexto');
    const cambiarProducto = document.getElementById('cambiarProductoLote');
    const monedaDetalle = document.getElementById('monedaDetalleLote');
    const grupoTipoCambioDetalle = document.getElementById('grupoTipoCambioDetalle');
    const tipoCambioDetalle = document.getElementById('tipoCambioDetalleLote');
    const usarReferenciaDetalle = document.getElementById('usarReferenciaDetalle');
    const notaTipoCambioDetalle = document.getElementById('notaTipoCambioDetalle');
    const componentesEsperados = document.getElementById('componentesEsperados');
    const errorCaja = document.getElementById('errorAgregarProductoLote');
    const botonGuardar = document.getElementById('btnAgregarProductoLote');
    let indiceComponenteEsperado = 0;

    function normalizar(valor) {
        return (valor ?? '')
            .toString()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .trim();
    }

    function limpiarError() {
        if (!errorCaja) {
            return;
        }
        errorCaja.textContent = '';
        errorCaja.classList.add('hidden');
    }

    function mostrarError(mensaje) {
        if (!errorCaja) {
            return;
        }
        errorCaja.textContent = mensaje;
        errorCaja.classList.remove('hidden');
    }

    function abrirListaProductos() {
        listaProductos?.classList.remove('hidden');
    }

    function cerrarListaProductos() {
        listaProductos?.classList.add('hidden');
    }

    function filtrarProductos() {
        const termino = normalizar(buscadorProducto?.value);
        let visibles = 0;

        opcionesProducto.forEach((opcion) => {
            const coincide = normalizar(opcion.dataset.search).includes(termino);
            opcion.classList.toggle('hidden', !coincide);
            if (coincide) {
                visibles++;
            }
        });

        sinCoincidencias?.classList.toggle('hidden', visibles !== 0);
    }

    function seleccionarProducto(id, nombre) {
        selectProducto.value = id;
        productoSeleccionadoTexto.textContent = nombre;
        productoSeleccionado.classList.remove('hidden');
        contenedorBuscador.classList.add('hidden');
        cerrarListaProductos();
    }

    function limpiarProductoSeleccionado() {
        selectProducto.value = '';
        productoSeleccionadoTexto.textContent = '';
        productoSeleccionado.classList.add('hidden');
        contenedorBuscador.classList.remove('hidden');
        buscadorProducto.value = '';
        filtrarProductos();
        window.setTimeout(() => buscadorProducto?.focus(), 50);
    }

    function abrirCatalogoProducto() {
        cerrarListaProductos();

        if (typeof window.abrirModalProducto !== 'function') {
            mostrarError('No fue posible abrir el catálogo de productos.');
            return;
        }

        /*
         * Este modal permanece en z-40 y el catálogo usa una capa superior.
         * Así, al cerrar el catálogo, Hugo vuelve exactamente al registro
         * del lote sin perder cantidad, costo, moneda ni características.
         */
        window.abrirModalProducto();

        window.setTimeout(() => {
            if (modal && !modal.classList.contains('hidden')) {
                document.body.classList.add('overflow-hidden');
            }
        }, 80);
    }

    function actualizarTipoCambioDetalle() {
        if (!monedaDetalle) {
            return;
        }

        const codigo = monedaDetalle.options[monedaDetalle.selectedIndex]?.dataset.codigo;
        const requiereTipoCambio = codigo === 'USD' || codigo === 'USDT';

        grupoTipoCambioDetalle?.classList.toggle('hidden', !requiereTipoCambio);
        if (tipoCambioDetalle) {
            tipoCambioDetalle.required = requiereTipoCambio;
        }
        usarReferenciaDetalle?.classList.toggle('hidden', codigo !== 'USD');

        if (notaTipoCambioDetalle) {
            notaTipoCambioDetalle.textContent = codigo === 'USDT'
                ? 'La referencia USD/BOB es solo informativa. Confirma el tipo de cambio realmente aplicado a USDT.'
                : 'Guarda el tipo de cambio que realmente se aplicó a esta compra.';
        }

        if (!requiereTipoCambio && tipoCambioDetalle) {
            tipoCambioDetalle.value = '';
        }
    }

    function agregarComponente() {
        const indice = indiceComponenteEsperado++;
        const fila = document.createElement('div');
        fila.className = 'grid gap-3 rounded-xl border border-slate-200 bg-white p-3 md:grid-cols-[2fr_100px_2fr_auto]';
        fila.innerHTML = `
            <input name="componentes_esperados[${indice}][nombre]" placeholder="Ej. cargador" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm" required>
            <input type="number" min="1" value="1" name="componentes_esperados[${indice}][cantidad_por_unidad]" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm" required>
            <input name="componentes_esperados[${indice}][observacion]" placeholder="Observación opcional" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
            <button type="button" class="quitar-componente rounded-lg px-3 py-2 text-sm font-bold text-red-700 hover:bg-red-50">Quitar</button>
            <input type="hidden" name="componentes_esperados[${indice}][incluido_en_compra]" value="1">
        `;
        fila.querySelector('.quitar-componente')?.addEventListener('click', () => fila.remove());
        componentesEsperados?.appendChild(fila);
        fila.querySelector('input')?.focus();
    }

    window.abrirModalAgregarProductoLote = function () {
        if (!modal || !formulario) {
            return;
        }

        formulario.reset();
        limpiarError();
        componentesEsperados.innerHTML = '';
        indiceComponenteEsperado = 0;
        limpiarProductoSeleccionado();
        actualizarTipoCambioDetalle();
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        window.setTimeout(() => buscadorProducto?.focus(), 80);
    };

    window.cerrarModalAgregarProductoLote = function () {
        modal?.classList.add('hidden');
        cerrarListaProductos();
        document.body.classList.remove('overflow-hidden');
    };

    window.cerrarModalAgregarProductoLoteDesdeFondo = function (event) {
        if (event.target === modal) {
            window.cerrarModalAgregarProductoLote();
        }
    };

    buscadorProducto?.addEventListener('focus', () => {
        abrirListaProductos();
        filtrarProductos();
    });
    buscadorProducto?.addEventListener('input', () => {
        abrirListaProductos();
        filtrarProductos();
    });

    opcionesProducto.forEach((opcion) => {
        opcion.addEventListener('click', () => seleccionarProducto(opcion.dataset.id, opcion.dataset.nombre));
    });

    selectProducto?.addEventListener('change', function () {
        if (this.value === 'crear') {
            this.value = '';
            abrirCatalogoProducto();
            return;
        }

        if (this.value) {
            const opcion = this.options[this.selectedIndex];
            seleccionarProducto(this.value, opcion?.textContent?.trim() ?? 'Producto');
        }
    });

    if (selectProducto) {
        const observadorCatalogo = new MutationObserver(() => {
            if (selectProducto.value && selectProducto.value !== 'crear') {
                const opcion = selectProducto.options[selectProducto.selectedIndex];
                seleccionarProducto(selectProducto.value, opcion?.textContent?.trim() ?? 'Producto');
            }
        });
        observadorCatalogo.observe(selectProducto, { childList: true, subtree: true });
    }

    cambiarProducto?.addEventListener('click', limpiarProductoSeleccionado);

    document.getElementById('crearProductoDesdeLote')?.addEventListener('click', function () {
        abrirCatalogoProducto();
    });

    document.addEventListener('click', function (event) {
        if (modal && !modal.classList.contains('hidden') && !event.target.closest('#buscadorProductoLoteContenedor')) {
            cerrarListaProductos();
        }
    });

    monedaDetalle?.addEventListener('change', actualizarTipoCambioDetalle);

    usarReferenciaDetalle?.addEventListener('click', function () {
        @if($referenciaUsdBob)
            tipoCambioDetalle.value = @js((string) $referenciaUsdBob['tipo_cambio']->valor);
            tipoCambioDetalle.focus();
        @endif
    });

    document.getElementById('agregarComponenteEsperado')?.addEventListener('click', agregarComponente);

    formulario?.addEventListener('submit', async function (event) {
        event.preventDefault();
        limpiarError();

        if (!selectProducto?.value || selectProducto.value === 'crear') {
            mostrarError('Debe seleccionar un producto del catálogo.');
            buscadorProducto?.focus();
            return;
        }

        const costo = formulario.querySelector('[name="costo_unitario_origen"]')?.value;
        if (costo !== '' && !monedaDetalle?.value) {
            mostrarError('Debe seleccionar la moneda del costo unitario.');
            monedaDetalle?.focus();
            return;
        }

        botonGuardar.disabled = true;
        botonGuardar.classList.add('opacity-60', 'cursor-not-allowed');

        try {
            const respuesta = await fetch("{{ route('importaciones.detalles.store', $lote) }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: new FormData(formulario),
            });

            const json = await respuesta.json();
            if (!respuesta.ok || !json.ok) {
                mostrarError(json.message ?? 'No fue posible agregar el producto al lote.');
                return;
            }

            window.location.reload();
        } catch (error) {
            console.error(error);
            mostrarError('No fue posible comunicarse con el servidor.');
        } finally {
            botonGuardar.disabled = false;
            botonGuardar.classList.remove('opacity-60', 'cursor-not-allowed');
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
            if (listaProductos && !listaProductos.classList.contains('hidden')) {
                cerrarListaProductos();
                return;
            }
            window.cerrarModalAgregarProductoLote();
        }
    });

    actualizarTipoCambioDetalle();
})();
</script>
@endpush
