@php
    $tiposCostoEditar = \App\Models\TipoCosto::query()
        ->where('activo', true)
        ->whereIn('ambito', ['LOTE', 'AMBOS'])
        ->orderBy('nombre')
        ->get();

    $monedasCostoEditar = isset($monedas)
        ? $monedas
        : \App\Models\Moneda::query()
            ->whereIn('codigo', ['BOB', 'USD', 'USDT'])
            ->get();
@endphp

<div
    id="modalEditarCosto"
    class="fixed inset-0 z-50 hidden bg-slate-950/50 p-4 backdrop-blur-sm"
    onclick="cerrarEditarCostoDesdeFondo(event)"
>
    <div class="flex min-h-full items-center justify-center">
        <div class="flex max-h-[94vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-blue-100 bg-white shadow-2xl">
            {{-- HEADER --}}
            <div class="flex shrink-0 items-start justify-between gap-4 border-b border-blue-100 bg-gradient-to-r from-blue-50 via-oneshop-soft to-white px-6 py-5">
                <div class="flex items-start gap-3">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-blue-200 bg-white text-xs font-black text-oneshop-primary shadow-sm">
                        Bs
                    </div>

                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-oneshop-primary">
                            Control financiero
                        </p>

                        <h2 class="mt-0.5 text-xl font-bold text-slate-950">
                            Editar costo
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Actualiza los datos del gasto registrado.
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    onclick="cerrarEditarCosto()"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-500 transition hover:bg-blue-100 hover:text-slate-900"
                    aria-label="Cerrar"
                >
                    <x-ui.icon name="x" size="17" />
                </button>
            </div>

            <form
                id="formEditarCosto"
                method="POST"
                class="flex min-h-0 flex-1 flex-col"
            >
                @csrf
                @method('PUT')

                <div class="min-h-0 flex-1 overflow-y-auto p-6">
                    <div class="space-y-6">
                        <section>
                            <p class="text-xs font-bold uppercase tracking-[0.12em] text-oneshop-primary">
                                Datos del costo
                            </p>

                            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                <div class="sm:col-span-2">
                                    <label class="mb-2 block text-sm font-bold text-slate-700">
                                        Tipo de costo
                                        <span class="text-red-700">*</span>
                                    </label>

                                    <select
                                        id="editar_tipo"
                                        name="tipo_costo_id"
                                        required
                                        class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-oneshop-primary focus:outline-none focus:ring-4 focus:ring-blue-100"
                                    >
                                        @foreach($tiposCostoEditar as $tipo)
                                            <option value="{{ $tipo->id }}">
                                                {{ $tipo->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label
                                        for="editar_moneda"
                                        class="mb-2 block text-sm font-bold text-slate-700"
                                    >
                                        Moneda
                                        <span class="text-red-700">*</span>
                                    </label>

                                    <select
                                        id="editar_moneda"
                                        name="moneda_id"
                                        required
                                        class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-oneshop-primary focus:outline-none focus:ring-4 focus:ring-blue-100"
                                    >
                                        @foreach($monedasCostoEditar as $moneda)
                                            <option
                                                value="{{ $moneda->id }}"
                                                data-codigo="{{ $moneda->codigo }}"
                                            >
                                                {{ $moneda->codigo }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label
                                        for="editar_monto"
                                        class="mb-2 block text-sm font-bold text-slate-700"
                                    >
                                        Monto
                                        <span class="text-red-700">*</span>
                                    </label>

                                    <input
                                        id="editar_monto"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        name="monto_origen"
                                        required
                                        class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-oneshop-primary focus:outline-none focus:ring-4 focus:ring-blue-100"
                                    >
                                </div>

                                <div class="sm:col-span-2">
                                    <label
                                        for="editar_fecha"
                                        class="mb-2 block text-sm font-bold text-slate-700"
                                    >
                                        Fecha del costo
                                        <span class="text-red-700">*</span>
                                    </label>

                                    <input
                                        id="editar_fecha"
                                        type="date"
                                        name="fecha_costo"
                                        required
                                        class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-oneshop-primary focus:outline-none focus:ring-4 focus:ring-blue-100"
                                    >
                                </div>
                            </div>
                        </section>

                        {{-- TIPO DE CAMBIO --}}
                        <section
                            id="grupoTipoCambioEditarCosto"
                            class="hidden rounded-xl border border-blue-100 bg-blue-50/40 p-4"
                        >
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-[0.12em] text-oneshop-primary">
                                        Referencia cambiaria
                                    </p>

                                    <div class="mt-2 flex items-end justify-between gap-3">
                                        <div>
                                            <p class="text-sm font-bold text-slate-800">
                                                USD / BOB
                                            </p>

                                            <p class="mt-0.5 text-xs text-slate-500">
                                                Referencia actual disponible.
                                            </p>
                                        </div>

                                        <p class="text-lg font-black text-oneshop-dark">
                                            @if(isset($referenciaUsdBob) && $referenciaUsdBob)
                                                {{ number_format((float) $referenciaUsdBob['tipo_cambio']->valor, 6) }}
                                            @else
                                                —
                                            @endif
                                        </p>
                                    </div>

                                    <button
                                        type="button"
                                        id="usarReferenciaEditarCosto"
                                        class="mt-3 hidden rounded-lg border border-blue-200 bg-white px-3 py-2 text-xs font-bold text-oneshop-dark transition hover:bg-blue-50"
                                    >
                                        Usar referencia
                                    </button>

                                    <p
                                        id="avisoReferenciaUsdtEditarCosto"
                                        class="mt-3 hidden text-xs leading-5 text-slate-500"
                                    >
                                        Para USDT la referencia USD/BOB es solo informativa.
                                    </p>
                                </div>

                                <div>
                                    <label
                                        for="editar_tipo_cambio"
                                        class="mb-2 block text-sm font-bold text-slate-700"
                                    >
                                        Tipo de cambio aplicado
                                        <span class="text-red-700">*</span>
                                    </label>

                                    <input
                                        id="editar_tipo_cambio"
                                        type="number"
                                        step="0.000001"
                                        min="0.000001"
                                        name="tipo_cambio"
                                        class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-oneshop-primary focus:outline-none focus:ring-4 focus:ring-blue-100"
                                    >

                                    <p class="mt-2 text-xs leading-5 text-slate-500">
                                        Se carga el tipo de cambio histórico del costo. Modifícalo solo si necesitas corregir el registro.
                                    </p>
                                </div>
                            </div>
                        </section>

                        <section>
                            <p class="text-xs font-bold uppercase tracking-[0.12em] text-oneshop-primary">
                                Información adicional
                            </p>

                            <div class="mt-4 space-y-4">
                                <div>
                                    <label
                                        for="editar_referencia"
                                        class="mb-2 block text-sm font-bold text-slate-700"
                                    >
                                        Referencia
                                    </label>

                                    <input
                                        id="editar_referencia"
                                        name="referencia"
                                        placeholder="Ej. factura, recibo, guía o comprobante"
                                        class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-oneshop-primary focus:outline-none focus:ring-4 focus:ring-blue-100"
                                    >
                                </div>

                                <div>
                                    <label
                                        for="editar_observacion"
                                        class="mb-2 block text-sm font-bold text-slate-700"
                                    >
                                        Observación
                                    </label>

                                    <textarea
                                        id="editar_observacion"
                                        name="observacion"
                                        rows="3"
                                        placeholder="Detalle opcional del costo"
                                        class="w-full resize-none rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-oneshop-primary focus:outline-none focus:ring-4 focus:ring-blue-100"
                                    ></textarea>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>

                {{-- FOOTER --}}
                <div class="flex shrink-0 flex-col-reverse gap-3 border-t border-blue-100 bg-blue-50/40 px-6 py-4 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        onclick="cerrarEditarCosto()"
                        class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                    >
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        class="rounded-xl border border-oneshop-primary bg-oneshop-light px-5 py-2.5 text-sm font-bold text-oneshop-dark shadow-sm transition hover:bg-blue-100"
                    >
                        Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const modal =
        document.getElementById(
            'modalEditarCosto'
        );

    const formulario =
        document.getElementById(
            'formEditarCosto'
        );

    const moneda =
        document.getElementById(
            'editar_moneda'
        );

    const grupoTipoCambio =
        document.getElementById(
            'grupoTipoCambioEditarCosto'
        );

    const tipoCambio =
        document.getElementById(
            'editar_tipo_cambio'
        );

    const usarReferencia =
        document.getElementById(
            'usarReferenciaEditarCosto'
        );

    const avisoUsdt =
        document.getElementById(
            'avisoReferenciaUsdtEditarCosto'
        );

    function codigoMoneda() {
        return moneda
            ?.options[
                moneda.selectedIndex
            ]
            ?.dataset
            ?.codigo
            ?? '';
    }

    function actualizarTipoCambio() {
        const codigo =
            codigoMoneda();

        const requiere =
            codigo === 'USD'
            || codigo === 'USDT';

        grupoTipoCambio
            ?.classList
            .toggle(
                'hidden',
                !requiere
            );

        if (tipoCambio) {
            tipoCambio.required =
                requiere;

            if (!requiere) {
                tipoCambio.value = '';
            }
        }

        usarReferencia
            ?.classList
            .toggle(
                'hidden',
                codigo !== 'USD'
            );

        avisoUsdt
            ?.classList
            .toggle(
                'hidden',
                codigo !== 'USDT'
            );
    }

    window.editarCosto =
        function (
            id,
            tipo,
            monedaId,
            monto,
            fecha,
            referencia,
            observacion,
            tipoCambioValor
        ) {
            if (
                !modal
                || !formulario
            ) {
                return;
            }

            formulario.action =
                '/importaciones/costos/'
                + id;

            document
                .getElementById(
                    'editar_tipo'
                )
                .value =
                    tipo;

            moneda.value =
                monedaId;

            document
                .getElementById(
                    'editar_monto'
                )
                .value =
                    monto;

            document
                .getElementById(
                    'editar_fecha'
                )
                .value =
                    fecha;

            document
                .getElementById(
                    'editar_referencia'
                )
                .value =
                    referencia ?? '';

            document
                .getElementById(
                    'editar_observacion'
                )
                .value =
                    observacion ?? '';

            tipoCambio.value =
                tipoCambioValor ?? '';

            actualizarTipoCambio();

            modal.classList.remove(
                'hidden'
            );

            document
                .body
                .classList
                .add(
                    'overflow-hidden'
                );

            window.setTimeout(
                () => {
                    document
                        .getElementById(
                            'editar_tipo'
                        )
                        ?.focus();
                },
                80
            );
        };

    window.editarCostoDesdeBoton =
        function (boton) {
            const datos =
                boton.dataset;

            window.editarCosto(
                datos.costoId,
                datos.tipo,
                datos.moneda,
                datos.monto,
                datos.fecha,
                datos.referencia || '',
                datos.observacion || '',
                datos.tipoCambio || ''
            );
        };

    window.cerrarEditarCosto =
        function () {
            modal
                ?.classList
                .add(
                    'hidden'
                );

            document
                .body
                .classList
                .remove(
                    'overflow-hidden'
                );
        };

    window.cerrarEditarCostoDesdeFondo =
        function (event) {
            if (event.target === modal) {
                window
                    .cerrarEditarCosto();
            }
        };

    moneda
        ?.addEventListener(
            'change',
            actualizarTipoCambio
        );

    usarReferencia
        ?.addEventListener(
            'click',
            function () {
                @if(isset($referenciaUsdBob) && $referenciaUsdBob)
                    if (tipoCambio) {
                        tipoCambio.value =
                            @js(
                                (string)
                                $referenciaUsdBob[
                                    'tipo_cambio'
                                ]->valor
                            );

                        tipoCambio.focus();
                    }
                @endif
            }
        );

    document.addEventListener(
        'keydown',
        function (event) {
            if (
                event.key === 'Escape'
                && modal
                && !modal
                    .classList
                    .contains('hidden')
            ) {
                window
                    .cerrarEditarCosto();
            }
        }
    );
})();
</script>
@endpush
