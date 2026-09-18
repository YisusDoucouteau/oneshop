<x-layouts.oneshop title="Crear reserva | OneShop" page-title="Nueva reserva">
<div
    class="space-y-6"
    x-data="{
        clienteBusqueda: '',
        equipoBusqueda: '',
        seleccionados: @js(array_map('intval', old('equipos', []))),
        coincide(texto, busqueda) {
            return texto.toLowerCase().includes(busqueda.trim().toLowerCase());
        }
    }"
>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a href="{{ route('reservas.index') }}" class="mb-2 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-800">
                <span aria-hidden="true">←</span> Volver a reservas
            </a>
            <h1 class="text-2xl font-bold text-slate-900">Registrar nueva reserva</h1>
            <p class="mt-1 max-w-2xl text-sm text-slate-500">
                Selecciona el cliente y los equipos. OneShop conservará el precio vigente y bloqueará las unidades para evitar una doble venta.
            </p>
        </div>

        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <span class="font-semibold" x-text="seleccionados.length"></span>
            <span x-text="seleccionados.length === 1 ? ' equipo seleccionado' : ' equipos seleccionados'"></span>
        </div>
    </div>

    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-700" role="alert">
            <p class="font-semibold">Revisa la información antes de continuar.</p>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('reservas.store') }}" class="space-y-6">
        @csrf
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <div class="space-y-6 xl:col-span-2">
                <x-ui.card>
                    <div class="mb-5 flex items-start gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-900 text-sm font-bold text-white">1</div>
                        <div>
                            <h2 class="font-bold text-slate-900">Seleccionar cliente</h2>
                            <p class="text-sm text-slate-500">Busca por nombre o teléfono para evitar recorrer toda la lista.</p>
                        </div>
                    </div>

                    <label for="cliente_busqueda" class="block text-sm font-semibold text-slate-700">Buscar cliente</label>
                    <input
                        id="cliente_busqueda"
                        type="search"
                        x-model="clienteBusqueda"
                        placeholder="Ej. Juan Pérez o 70000000"
                        class="mt-2 w-full rounded-xl border-slate-300"
                        autocomplete="off"
                    >

                    <label for="cliente_id" class="mt-4 block text-sm font-semibold text-slate-700">Cliente de la reserva</label>
                    <select id="cliente_id" name="cliente_id" class="mt-2 w-full rounded-xl border-slate-300" required>
                        <option value="">Seleccione un cliente</option>
                        @foreach($clientes as $cliente)
                            <option
                                value="{{ $cliente->id }}"
                                x-show="coincide(@js($cliente->nombre_completo.' '.$cliente->telefono), clienteBusqueda)"
                                {{ (string) old('cliente_id') === (string) $cliente->id ? 'selected' : '' }}
                            >
                                {{ $cliente->nombre_completo }}{{ $cliente->telefono ? ' · '.$cliente->telefono : '' }}
                            </option>
                        @endforeach
                    </select>
                </x-ui.card>

                <x-ui.card>
                    <div class="mb-5 flex items-start gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-900 text-sm font-bold text-white">2</div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <h2 class="font-bold text-slate-900">Elegir equipos disponibles</h2>
                                    <p class="text-sm text-slate-500">Busca por código interno, producto, modelo o número de serie.</p>
                                </div>
                                <span class="whitespace-nowrap text-sm font-semibold text-oneshop-primary">
                                    <span x-text="seleccionados.length"></span> seleccionados
                                </span>
                            </div>
                        </div>
                    </div>

                    <label for="equipo_busqueda" class="sr-only">Buscar equipos</label>
                    <input
                        id="equipo_busqueda"
                        type="search"
                        x-model="equipoBusqueda"
                        placeholder="Buscar equipo disponible..."
                        class="w-full rounded-xl border-slate-300"
                        autocomplete="off"
                    >

                    <div class="mt-4 grid max-h-[34rem] grid-cols-1 gap-3 overflow-y-auto pr-1 lg:grid-cols-2">
                        @forelse($equipos as $equipo)
                            @php
                                $textoBusqueda = implode(' ', array_filter([
                                    $equipo->codigo_interno,
                                    $equipo->serial_fabricante,
                                    $equipo->producto?->nombre,
                                    $equipo->producto?->modelo,
                                ]));
                            @endphp
                            <label
                                x-show="coincide(@js($textoBusqueda), equipoBusqueda)"
                                class="relative flex cursor-pointer gap-3 rounded-xl border p-4 transition hover:border-slate-400 hover:bg-slate-50"
                                :class="seleccionados.includes({{ $equipo->id }}) ? 'border-oneshop-primary bg-blue-50 ring-1 ring-oneshop-primary' : 'border-slate-200 bg-white'"
                            >
                                <input
                                    type="checkbox"
                                    name="equipos[]"
                                    value="{{ $equipo->id }}"
                                    x-model.number="seleccionados"
                                    class="mt-1 rounded border-slate-300 text-oneshop-primary focus:ring-oneshop-primary"
                                    {{ in_array($equipo->id, array_map('intval', old('equipos', [])), true) ? 'checked' : '' }}
                                >
                                <span class="min-w-0">
                                    <span class="block font-bold text-slate-900">{{ $equipo->codigo_interno }}</span>
                                    <span class="mt-1 block text-sm text-slate-600">
                                        {{ $equipo->producto?->nombre ?? 'Equipo sin producto' }} {{ $equipo->producto?->modelo }}
                                    </span>
                                    @if($equipo->serial_fabricante)
                                        <span class="mt-2 block truncate text-xs text-slate-500">Serie: {{ $equipo->serial_fabricante }}</span>
                                    @endif
                                </span>
                            </label>
                        @empty
                            <div class="col-span-full rounded-xl border border-dashed border-slate-300 px-6 py-10 text-center">
                                <p class="font-semibold text-slate-700">No hay equipos disponibles.</p>
                                <p class="mt-1 text-sm text-slate-500">Revisa el inventario o la preparación técnica antes de crear una reserva.</p>
                            </div>
                        @endforelse
                    </div>
                </x-ui.card>
            </div>

            <div class="space-y-6">
                <x-ui.card>
                    <div class="mb-5 flex items-start gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-900 text-sm font-bold text-white">3</div>
                        <div>
                            <h2 class="font-bold text-slate-900">Vigencia y observación</h2>
                            <p class="text-sm text-slate-500">Define hasta cuándo se mantendrán separados los equipos.</p>
                        </div>
                    </div>

                    <label for="fecha_expiracion" class="block text-sm font-semibold text-slate-700">Fecha de vencimiento</label>
                    <input
                        id="fecha_expiracion"
                        type="datetime-local"
                        name="fecha_expiracion"
                        value="{{ old('fecha_expiracion', now()->addDay()->format('Y-m-d\TH:i')) }}"
                        min="{{ now()->format('Y-m-d\TH:i') }}"
                        max="{{ now()->addDays(7)->format('Y-m-d\TH:i') }}"
                        class="mt-2 w-full rounded-xl border-slate-300"
                        required
                    >
                    <p class="mt-2 text-xs text-slate-500">Plazo máximo estándar: 7 días.</p>

                    <label for="observacion" class="mt-5 block text-sm font-semibold text-slate-700">Observación opcional</label>
                    <textarea
                        id="observacion"
                        name="observacion"
                        rows="4"
                        maxlength="1000"
                        placeholder="Ej. Cliente deja adelanto y recogerá el viernes."
                        class="mt-2 w-full rounded-xl border-slate-300"
                    >{{ old('observacion') }}</textarea>
                </x-ui.card>

                <div class="sticky top-6 rounded-2xl bg-slate-900 p-5 text-white shadow-lg">
                    <p class="text-sm font-semibold text-slate-300">Resumen de la reserva</p>
                    <p class="mt-2 text-3xl font-bold" x-text="seleccionados.length"></p>
                    <p class="text-sm text-slate-300" x-text="seleccionados.length === 1 ? 'equipo será bloqueado' : 'equipos serán bloqueados'"></p>
                    <button
                        type="submit"
                        class="mt-5 w-full rounded-xl bg-oneshop-primary px-5 py-3 font-semibold text-white transition hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="seleccionados.length === 0"
                    >
                        Guardar reserva
                    </button>
                    <a href="{{ route('reservas.index') }}" class="mt-3 block w-full rounded-xl border border-slate-600 px-5 py-3 text-center font-medium text-slate-200 hover:bg-slate-800">
                        Cancelar
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
</x-layouts.oneshop>
