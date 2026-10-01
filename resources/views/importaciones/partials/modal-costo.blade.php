@php
    $tiposCosto = \App\Models\TipoCosto::query()
        ->where('activo', true)
        ->whereIn('ambito', ['LOTE', 'AMBOS'])
        ->orderBy('nombre')
        ->get();

    $monedasCosto = isset($monedas)
        ? $monedas
        : \App\Models\Moneda::query()
            ->whereIn('codigo', ['BOB', 'USD', 'USDT'])
            ->get();
@endphp

<div
    id="modalCosto"
    class="fixed inset-0 z-50 hidden bg-slate-950/50 p-4 backdrop-blur-sm"
    onclick="cerrarModalCostoDesdeFondo(event)"
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
                            Registrar costo
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Añade un gasto asociado a esta importación.
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    onclick="cerrarModalCosto()"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-500 transition hover:bg-blue-100 hover:text-slate-900"
                    aria-label="Cerrar"
                >
                    <x-ui.icon name="x" size="17" />
                </button>
            </div>

            <form
                id="formRegistrarCosto"
                method="POST"
                action="{{ route('importaciones.costos.store', $lote) }}"
                class="flex min-h-0 flex-1 flex-col"
            >
                @csrf

                <div class="min-h-0 flex-1 overflow-y-auto p-6">
                    <div class="space-y-6">
                        <section>
                            <p class="text-xs font-bold uppercase tracking-[0.12em] text-oneshop-primary">
                                Datos del costo
                            </p>

                            <p class="mt-1 text-sm text-slate-500">
                                Registra el importe y la fecha en que se generó el gasto.
                            </p>

                            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                <div class="sm:col-span-2">
                                    <label class="mb-2 block text-sm font-bold text-slate-700">
                                        Tipo de costo
                                        <span class="text-red-700">*</span>
                                    </label>

                                    <select
                                        name="tipo_costo_id"
                                        required
                                        class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-oneshop-primary focus:outline-none focus:ring-4 focus:ring-blue-100"
                                    >
                                        <option value="">
                                            Seleccionar tipo
                                        </option>

                                        @foreach($tiposCosto as $tipo)
                                            <option value="{{ $tipo->id }}">
                                                {{ $tipo->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label
                                        for="monedaCosto"
                                        class="mb-2 block text-sm font-bold text-slate-700"
                                    >
                                        Moneda
                                        <span class="text-red-700">*</span>
                                    </label>

                                    <select
                                        id="monedaCosto"
                                        name="moneda_id"
                                        required
                                        class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-oneshop-primary focus:outline-none focus:ring-4 focus:ring-blue-100"
                                    >
                                        <option value="">
                                            Seleccionar
                                        </option>

                                        @foreach($monedasCosto as $moneda)
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
                                    <label class="mb-2 block text-sm font-bold text-slate-700">
                                        Monto
                                        <span class="text-red-700">*</span>
                                    </label>

                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        name="monto_origen"
                                        required
                                        placeholder="0.00"
                                        class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-oneshop-primary focus:outline-none focus:ring-4 focus:ring-blue-100"
                                    >
                                </div>

                                <div class="sm:col-span-2">
                                    <label class="mb-2 block text-sm font-bold text-slate-700">
                                        Fecha del costo
                                        <span class="text-red-700">*</span>
                                    </label>

                                    <input
                                        type="date"
                                        name="fecha_costo"
                                        value="{{ date('Y-m-d') }}"
                                        required
                                        class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-oneshop-primary focus:outline-none focus:ring-4 focus:ring-blue-100"
                                    >
                                </div>
                            </div>
                        </section>

                        {{-- TIPO DE CAMBIO --}}
                        <section
                            id="grupoTipoCambioCosto"
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
                                                Referencia disponible en OneShop.
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
                                        id="usarReferenciaCosto"
                                        class="mt-3 hidden rounded-lg border border-blue-200 bg-white px-3 py-2 text-xs font-bold text-oneshop-dark transition hover:bg-blue-50"
                                    >
                                        Usar referencia
                                    </button>

                                    <p
                                        id="avisoReferenciaUsdtCosto"
                                        class="mt-3 hidden text-xs leading-5 text-slate-500"
                                    >
                                        Para USDT la referencia USD/BOB es solo informativa. Registra el tipo de cambio realmente aplicado.
                                    </p>
                                </div>

                                <div>
                                    <label
                                        for="tipoCambioCosto"
                                        class="mb-2 block text-sm font-bold text-slate-700"
                                    >
                                        Tipo de cambio aplicado
                                        <span class="text-red-700">*</span>
                                    </label>

                                    <input
                                        id="tipoCambioCosto"
                                        type="number"
                                        step="0.000001"
                                        min="0.000001"
                                        name="tipo_cambio"
                                        placeholder="0.000000"
                                        class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-oneshop-primary focus:outline-none focus:ring-4 focus:ring-blue-100"
                                    >

                                    <p class="mt-2 text-xs leading-5 text-slate-500">
                                        Guarda el tipo de cambio que realmente se utilizó para este gasto.
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
                                    <label class="mb-2 block text-sm font-bold text-slate-700">
                                        Referencia
                                    </label>

                                    <input
                                        name="referencia"
                                        placeholder="Ej. factura, recibo, guía o comprobante"
                                        class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-oneshop-primary focus:outline-none focus:ring-4 focus:ring-blue-100"
                                    >
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-bold text-slate-700">
                                        Observación
                                    </label>

                                    <textarea
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
                        onclick="cerrarModalCosto()"
                        class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                    >
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        class="rounded-xl border border-oneshop-primary bg-oneshop-light px-5 py-2.5 text-sm font-bold text-oneshop-dark shadow-sm transition hover:bg-blue-100"
                    >
                        Guardar costo
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
            'modalCosto'
        );

    const formulario =
        document.getElementById(
            'formRegistrarCosto'
        );

    const moneda =
        document.getElementById(
            'monedaCosto'
        );

    const grupoTipoCambio =
        document.getElementById(
            'grupoTipoCambioCosto'
        );

    const tipoCambio =
        document.getElementById(
            'tipoCambioCosto'
        );

    const usarReferencia =
        document.getElementById(
            'usarReferenciaCosto'
        );

    const avisoUsdt =
        document.getElementById(
            'avisoReferenciaUsdtCosto'
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

    window.abrirModalCosto =
        function () {
            if (
                !modal
                || !formulario
            ) {
                return;
            }

            formulario.reset();
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
                    formulario
                        .querySelector(
                            '[name="tipo_costo_id"]'
                        )
                        ?.focus();
                },
                80
            );
        };

    window.cerrarModalCosto =
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

    window.cerrarModalCostoDesdeFondo =
        function (event) {
            if (event.target === modal) {
                window
                    .cerrarModalCosto();
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
                    .cerrarModalCosto();
            }
        }
    );

    actualizarTipoCambio();
})();
</script>
@endpush
