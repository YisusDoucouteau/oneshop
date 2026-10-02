<x-layouts.oneshop
    title="Envíos a Oruro | OneShop"
    page-title="Envíos a Oruro"
>

@php
    $colorEstado = function (?string $estado): string {
        return match ($estado) {
            \App\Models\EnvioImportacion::ESTADO_BORRADOR => 'gray',
            \App\Models\EnvioImportacion::ESTADO_PREPARADO => 'blue',
            \App\Models\EnvioImportacion::ESTADO_DESPACHADO => 'yellow',
            \App\Models\EnvioImportacion::ESTADO_RECIBIDO_PARCIAL => 'yellow',
            \App\Models\EnvioImportacion::ESTADO_RECIBIDO => 'green',
            \App\Models\EnvioImportacion::ESTADO_CANCELADO => 'red',
            default => 'gray',
        };
    };

    $nombreEstado = function (?string $estado): string {
        return match ($estado) {
            \App\Models\EnvioImportacion::ESTADO_BORRADOR => 'Borrador',
            \App\Models\EnvioImportacion::ESTADO_PREPARADO => 'Preparado',
            \App\Models\EnvioImportacion::ESTADO_DESPACHADO => 'En tránsito',
            \App\Models\EnvioImportacion::ESTADO_RECIBIDO_PARCIAL => 'Recibido con diferencias',
            \App\Models\EnvioImportacion::ESTADO_RECIBIDO => 'Recibido completo',
            \App\Models\EnvioImportacion::ESTADO_CANCELADO => 'Cancelado',
            default => str_replace('_', ' ', $estado ?? 'Sin estado'),
        };
    };
@endphp

<div
    x-data="{
        modalCrear: false,
        guardando: false,
        errores: {},
        errorGeneral: '',
        filtros: {
            q: '',
            estado: ''
        },
        registros: @js(
            $envios->map(fn ($envio) => [
                'codigo' => $envio->codigo,
                'transportista' => $envio->transportista ?? '',
                'guia' => $envio->numero_guia ?? '',
                'estado' => $envio->estado,
            ])->values()
        ),
        normalizar(valor) {
            return (valor ?? '')
                .toString()
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '');
        },
        coincide(codigo, transportista, guia, estado) {
            const termino = this.normalizar(this.filtros.q.trim());
            const texto = this.normalizar([codigo, transportista, guia].filter(Boolean).join(' '));

            const coincideTexto = termino === '' || texto.includes(termino);
            const coincideEstado = this.filtros.estado === '' || estado === this.filtros.estado;

            return coincideTexto && coincideEstado;
        },
        get hayFiltros() {
            return this.filtros.q.trim() !== '' || this.filtros.estado !== '';
        },
        get hayCoincidencias() {
            return this.registros.some((registro) =>
                this.coincide(
                    registro.codigo,
                    registro.transportista,
                    registro.guia,
                    registro.estado
                )
            );
        },
        limpiarFiltros() {
            this.filtros.q = '';
            this.filtros.estado = '';
        },
        formulario: {
            transportista: '',
            numero_guia: '',
            cantidad_bultos: 1,
            cantidad_cargadores: 0,
            cantidad_accesorios: 0,
            detalle_accesorios: '',
            observacion: ''
        },
        abrirModal() {
            this.errores = {};
            this.errorGeneral = '';
            this.modalCrear = true;
        },
        async crearEnvio() {
            if (this.guardando) return;

            this.guardando = true;
            this.errores = {};
            this.errorGeneral = '';

            try {
                const respuesta = await window.axios.post(
                    @js(route('envios-importacion.store')),
                    this.formulario,
                    { headers: { 'Accept': 'application/json' } }
                );

                if (respuesta.data?.ok && respuesta.data?.redirect) {
                    window.location.href = respuesta.data.redirect;
                    return;
                }

                this.errorGeneral = 'No fue posible crear el envío.';
            } catch (error) {
                if (error.response?.status === 422) {
                    this.errores = error.response.data.errors ?? {};
                    this.errorGeneral = error.response.data.message ?? 'Revise los datos ingresados.';
                } else {
                    this.errorGeneral = error.response?.data?.message ?? 'Ocurrió un error al crear el envío.';
                }
            } finally {
                this.guardando = false;
            }
        }
    }"
    class="space-y-6"
>

    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-center gap-3">
            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-oneshop-light text-oneshop-primary">
                <x-ui.icon name="truck" size="22" />
            </div>

            <div>
                <h1 class="text-2xl font-bold text-slate-900">Envíos Cochabamba → Oruro</h1>
                <p class="mt-1 text-sm text-slate-500">
                    Prepara el manifiesto, consolida unidades listas y controla el despacho físico hacia Oruro.
                </p>
            </div>
        </div>

        @if(auth()->user()?->tienePermiso('importacion.gestionar') && ($puedeCrearEnvio ?? false))
            <button
                type="button"
                @click="abrirModal()"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-oneshop-primary bg-oneshop-light px-5 py-3 text-sm font-semibold text-oneshop-dark transition hover:bg-oneshop-soft"
            >
                <x-ui.icon name="plus" size="18" />
                Nuevo envío
            </button>
        @endif
    </div>

    @if(session('success'))
        <div class="flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 p-4 text-green-800">
            <x-ui.icon name="check" size="20" />
            <span class="text-sm font-medium">{{ session('success') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.card>
            <p class="text-sm text-slate-500">Total de envíos</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ $resumen['total'] ?? 0 }}</p>
            <p class="mt-1 text-xs text-slate-500">Historial completo de traslados</p>
        </x-ui.card>

        <x-ui.card>
            <p class="text-sm text-slate-500">Borradores</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ $resumen['borradores'] ?? 0 }}</p>
            <p class="mt-1 text-xs text-slate-500">Todavía editables en Cochabamba</p>
        </x-ui.card>

        <x-ui.card>
            <p class="text-sm text-slate-500">Preparados</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ $resumen['preparados'] ?? 0 }}</p>
            <p class="mt-1 text-xs text-slate-500">Listos para confirmar despacho</p>
        </x-ui.card>

        <x-ui.card>
            <p class="text-sm text-slate-500">En tránsito</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ $resumen['en_transito'] ?? 0 }}</p>
            <p class="mt-1 text-xs text-slate-500">Despachados y aún no cerrados en Oruro</p>
        </x-ui.card>
    </div>

    @if(($puedeCrearEnvio ?? false) && $unidadesDisponibles->isNotEmpty())
        <div class="flex flex-col gap-3 rounded-xl border border-blue-200 bg-blue-50 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="font-semibold text-blue-900">
                    {{ $unidadesDisponibles->count() }} {{ $unidadesDisponibles->count() === 1 ? 'unidad está' : 'unidades están' }} lista para envío
                </p>
                <p class="mt-1 text-sm text-blue-700">
                    Puedes crear un borrador y agregarlas al manifiesto desde el detalle del envío.
                </p>
            </div>
            <button
                type="button"
                @click="abrirModal()"
                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl border border-blue-300 bg-white px-4 py-2 text-sm font-semibold text-blue-800 transition hover:bg-blue-100"
            >
                <x-ui.icon name="plus" size="16" />
                Crear borrador
            </button>
        </div>
    @endif

    <x-ui.card>
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-[1fr_260px_auto] lg:items-end">
            <div>
                <label for="filtro-envios-q" class="mb-2 block text-sm font-semibold text-slate-700">Buscar envío</label>
                <input
                    id="filtro-envios-q"
                    data-testid="filtro-envios-busqueda"
                    type="search"
                    x-model.debounce.150ms="filtros.q"
                    class="input-oneshop w-full"
                    placeholder="Código, guía o transportista"
                    autocomplete="off"
                >
            </div>

            <div>
                <label for="filtro-envios-estado" class="mb-2 block text-sm font-semibold text-slate-700">Estado</label>
                <select
                    id="filtro-envios-estado"
                    data-testid="filtro-envios-estado"
                    x-model="filtros.estado"
                    class="input-oneshop w-full"
                >
                    <option value="">Todos los estados</option>
                    <option value="BORRADOR">Borrador</option>
                    <option value="PREPARADO">Preparado</option>
                    <option value="DESPACHADO">En tránsito</option>
                    <option value="RECIBIDO_PARCIAL">Recibido con diferencias</option>
                    <option value="RECIBIDO">Recibido completo</option>
                    <option value="CANCELADO">Cancelado</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    x-show="hayFiltros"
                    x-cloak
                    @click="limpiarFiltros()"
                    class="btn-secondary"
                >
                    Limpiar
                </button>

                <span class="text-xs text-slate-500">
                    Filtrado instantáneo
                </span>
            </div>
        </div>
    </x-ui.card>

    <x-ui.card padding="false">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="font-semibold text-slate-900">Registro de envíos</h2>
            <p class="mt-1 text-xs text-slate-500">
                El estado “En tránsito” corresponde a un envío ya despachado que todavía no fue cerrado en destino.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1080px] text-sm">
                <thead class="bg-slate-50">
                    <tr class="border-b border-slate-200 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <th class="px-5 py-4 text-left">Envío</th>
                        <th class="px-5 py-4 text-left">Ruta</th>
                        <th class="px-5 py-4 text-left">Estado</th>
                        <th class="px-5 py-4 text-left">Unidades</th>
                        <th class="px-5 py-4 text-left">Transporte</th>
                        <th class="px-5 py-4 text-left">Último hito</th>
                        <th class="px-5 py-4 text-right">Acción</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($envios as $envio)
                        <tr
                            data-testid="fila-envio"
                            data-estado="{{ $envio->estado }}"
                            x-show="coincide(
                                @js($envio->codigo),
                                @js($envio->transportista ?? ''),
                                @js($envio->numero_guia ?? ''),
                                @js($envio->estado)
                            )"
                            class="transition hover:bg-slate-50"
                        >
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-oneshop-soft text-oneshop-primary">
                                        <x-ui.icon name="truck" size="19" />
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-900">{{ $envio->codigo }}</p>
                                        <p class="mt-1 text-xs text-slate-500">Registro #{{ $envio->id }}</p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-5 py-4">
                                <p class="font-medium text-slate-800">{{ $envio->almacenOrigen?->nombre ?? 'Cochabamba' }}</p>
                                <p class="mt-1 text-xs text-slate-500">hacia {{ $envio->almacenDestino?->nombre ?? 'Oruro' }}</p>
                            </td>

                            <td class="px-5 py-4">
                                <x-ui.badge :color="$colorEstado($envio->estado)">
                                    {{ $nombreEstado($envio->estado) }}
                                </x-ui.badge>
                            </td>

                            <td class="px-5 py-4">
                                <p class="font-semibold text-slate-900">{{ $envio->unidades_envio_count }}</p>
                                <p class="text-xs text-slate-500">unidades</p>
                            </td>

                            <td class="px-5 py-4">
                                <p class="text-slate-700">{{ $envio->transportista ?? 'Sin registrar' }}</p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $envio->numero_guia ? 'Guía: '.$envio->numero_guia : 'Guía pendiente' }}
                                </p>
                            </td>

                            <td class="px-5 py-4">
                                @if($envio->fecha_recepcion)
                                    <p class="font-medium text-slate-700">{{ $envio->fecha_recepcion->format('d/m/Y H:i') }}</p>
                                    <p class="text-xs text-slate-500">Recepción</p>
                                @elseif($envio->fecha_despacho)
                                    <p class="font-medium text-slate-700">{{ $envio->fecha_despacho->format('d/m/Y H:i') }}</p>
                                    <p class="text-xs text-slate-500">Despacho</p>
                                @elseif($envio->fecha_preparacion)
                                    <p class="font-medium text-slate-700">{{ $envio->fecha_preparacion->format('d/m/Y H:i') }}</p>
                                    <p class="text-xs text-slate-500">Preparación</p>
                                @else
                                    <p class="font-medium text-slate-700">{{ $envio->created_at?->format('d/m/Y H:i') ?? '-' }}</p>
                                    <p class="text-xs text-slate-500">Creación</p>
                                @endif
                            </td>

                            <td class="px-5 py-4 text-right">
                                <a
                                    href="{{ route('envios-importacion.show', $envio) }}"
                                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 transition hover:border-oneshop-primary hover:bg-oneshop-soft hover:text-oneshop-primary"
                                >
                                    <x-ui.icon name="eye" size="17" />
                                    Ver envío
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                    <x-ui.icon name="truck" size="25" />
                                </div>
                                <h3 class="mt-4 font-semibold text-slate-900">Todavía no existen envíos</h3>
                                <p class="mt-1 text-sm text-slate-500">
                                    Cree un borrador para comenzar un nuevo traslado a Oruro.
                                </p>
                            </td>
                        </tr>
                    @endforelse

                    @if($envios->isNotEmpty())
                        <tr x-show="!hayCoincidencias" x-cloak>
                            <td colspan="7" class="px-6 py-14 text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                    <x-ui.icon name="truck" size="22" />
                                </div>
                                <h3 class="mt-4 font-semibold text-slate-900">Sin coincidencias</h3>
                                <p class="mt-1 text-sm text-slate-500">
                                    Cambia el texto o el estado para volver a mostrar envíos.
                                </p>
                                <button
                                    type="button"
                                    @click="limpiarFiltros()"
                                    class="mt-4 inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                                >
                                    Limpiar filtros
                                </button>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

    </x-ui.card>

    <div
        x-cloak
        x-show="modalCrear"
        x-transition.opacity
        class="fixed inset-0 z-[100] flex items-center justify-center p-4"
    >
        <div
            class="absolute inset-0 bg-slate-950/70 backdrop-blur-sm"
            @click="!guardando && (modalCrear = false)"
        ></div>

        <div
            x-show="modalCrear"
            x-transition
            @click.stop
            class="relative z-10 max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-2xl"
        >
            <div class="flex items-start justify-between border-b border-slate-200 px-6 py-5">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-oneshop-light text-oneshop-primary">
                        <x-ui.icon name="truck" size="20" />
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Nuevo envío a Oruro</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Se creará como borrador editable. Las unidades se agregan después desde el detalle.
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    @click="modalCrear = false"
                    :disabled="guardando"
                    class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                >
                    <x-ui.icon name="x" size="20" />
                </button>
            </div>

            <form @submit.prevent="crearEnvio" class="p-6">
                <div class="space-y-5">
                    <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
                        <p class="text-sm font-semibold text-blue-900">Código automático</p>
                        <p class="mt-1 text-sm text-blue-700">
                            El sistema generará un correlativo como ENV-{{ now()->format('Y') }}-001.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Transportista</label>
                            <input type="text" x-model="formulario.transportista" class="input-oneshop w-full" placeholder="Empresa o persona">
                            <p x-show="errores.transportista" x-text="errores.transportista?.[0]" class="mt-1 text-xs text-red-600"></p>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Número de guía</label>
                            <input type="text" x-model="formulario.numero_guia" class="input-oneshop w-full" placeholder="Opcional">
                            <p x-show="errores.numero_guia" x-text="errores.numero_guia?.[0]" class="mt-1 text-xs text-red-600"></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Cajas / bultos</label>
                            <input type="number" min="1" x-model.number="formulario.cantidad_bultos" class="input-oneshop w-full">
                            <p x-show="errores.cantidad_bultos" x-text="errores.cantidad_bultos?.[0]" class="mt-1 text-xs text-red-600"></p>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Cargadores adicionales</label>
                            <input type="number" min="0" x-model.number="formulario.cantidad_cargadores" class="input-oneshop w-full">
                            <p class="mt-1 text-xs text-slate-500">Sueltos, no asociados a un equipo.</p>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Otros accesorios</label>
                            <input type="number" min="0" x-model.number="formulario.cantidad_accesorios" class="input-oneshop w-full">
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Detalle de accesorios</label>
                        <input
                            type="text"
                            x-model="formulario.detalle_accesorios"
                            class="input-oneshop w-full"
                            placeholder="Ej.: 1 mouse, 2 cables de poder"
                        >
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Observación</label>
                        <textarea
                            x-model="formulario.observacion"
                            rows="3"
                            class="input-oneshop w-full"
                            placeholder="Información adicional del traslado"
                        ></textarea>
                    </div>

                    <div
                        x-show="errorGeneral"
                        x-text="errorGeneral"
                        class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm font-medium text-red-700"
                    ></div>
                </div>

                <div class="mt-7 flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
                    <button type="button" @click="modalCrear = false" :disabled="guardando" class="btn-secondary">Cancelar</button>
                    <button
                        type="submit"
                        :disabled="guardando"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-oneshop-primary bg-oneshop-light px-5 py-2.5 text-sm font-semibold text-oneshop-dark transition hover:bg-oneshop-soft disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <x-ui.icon name="check" size="17" />
                        <span x-text="guardando ? 'Creando...' : 'Crear borrador'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

</x-layouts.oneshop>
