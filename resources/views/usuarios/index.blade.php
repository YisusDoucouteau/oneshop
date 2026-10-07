<x-layouts.oneshop
    title="Usuarios | OneShop"
    page-title="Usuarios"
>

<div class="space-y-6">

    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                Usuarios y accesos
            </h1>

            <p class="mt-1 max-w-3xl text-sm text-slate-500">
                Consulta el personal, sus roles, sede operativa, estado de acceso y último ingreso al sistema.
            </p>
        </div>

        @if(auth()->user()?->tienePermiso('usuarios.gestionar'))
            <a
                href="{{ route('usuarios.create') }}"
                class="inline-flex items-center justify-center rounded-xl bg-oneshop-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:opacity-90"
            >
                Nuevo usuario
            </a>
        @endif
    </div>


    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->has('usuario'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm font-medium text-rose-800">
            {{ $errors->first('usuario') }}
        </div>
    @endif


    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">
                Usuarios registrados
            </p>
            <p class="mt-2 text-3xl font-black text-slate-900">
                {{ $resumen['total'] }}
            </p>
        </div>

        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-emerald-700">
                Activos
            </p>
            <p class="mt-2 text-3xl font-black text-emerald-800">
                {{ $resumen['activos'] }}
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-600">
                Inactivos
            </p>
            <p class="mt-2 text-3xl font-black text-slate-800">
                {{ $resumen['inactivos'] }}
            </p>
        </div>

        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-amber-700">
                Sede pendiente
            </p>
            <p class="mt-2 text-3xl font-black text-amber-800">
                {{ $resumen['sede_pendiente'] }}
            </p>
        </div>

    </div>


    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

        <form
            id="usuarios_filtros"
            method="GET"
            action="{{ route('usuarios.index') }}"
            class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_190px_210px_220px_auto]"
        >

            <div>
                <label
                    for="buscar"
                    class="block text-sm font-semibold text-slate-700"
                >
                    Buscar
                </label>

                <div class="relative mt-2">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <x-ui.icon name="search" size="18"/>
                    </span>

                    <input
                        id="buscar"
                        name="buscar"
                        value="{{ $busqueda }}"
                        class="w-full rounded-xl border-slate-300 pl-10 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Nombre o correo"
                        autocomplete="off"
                        data-auto-submit
                    >
                </div>
            </div>

            <div>
                <label
                    for="estado"
                    class="block text-sm font-semibold text-slate-700"
                >
                    Estado
                </label>

                <select
                    id="estado"
                    name="estado"
                    data-auto-submit
                    class="mt-2 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
                    <option value="">Todos</option>
                    <option value="activo" {{ $estado === 'activo' ? 'selected' : '' }}>
                        Activos
                    </option>
                    <option value="inactivo" {{ $estado === 'inactivo' ? 'selected' : '' }}>
                        Inactivos
                    </option>
                </select>
            </div>

            <div>
                <label
                    for="rol"
                    class="block text-sm font-semibold text-slate-700"
                >
                    Rol
                </label>

                <select
                    id="rol"
                    name="rol"
                    data-auto-submit
                    class="mt-2 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
                    <option value="">Todos los roles</option>

                    @foreach($roles as $rolDisponible)
                        <option
                            value="{{ $rolDisponible->codigo }}"
                            {{ $rol === $rolDisponible->codigo ? 'selected' : '' }}
                        >
                            {{ $rolDisponible->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label
                    for="sede"
                    class="block text-sm font-semibold text-slate-700"
                >
                    Sede operativa
                </label>

                <select
                    id="sede"
                    name="sede"
                    data-auto-submit
                    class="mt-2 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
                    <option value="">Todas las sedes</option>

                    @foreach($almacenes as $almacen)
                        <option
                            value="{{ $almacen->id }}"
                            {{ $sede === (string) $almacen->id ? 'selected' : '' }}
                        >
                            {{ $almacen->nombre }}
                        </option>
                    @endforeach

                    <option value="sin_sede" {{ $sede === 'sin_sede' ? 'selected' : '' }}>
                        Sin sede asignada
                    </option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <div
                    id="usuarios_filtros_estado"
                    class="flex min-h-10 items-center text-xs font-medium text-slate-400"
                >
                    Filtrado automático
                </div>

                @if($busqueda !== '' || $estado !== '' || $rol !== '' || $sede !== '')
                    <a
                        href="{{ route('usuarios.index') }}"
                        class="inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50"
                    >
                        Limpiar
                    </a>
                @endif
            </div>

        </form>

    </div>


    <x-ui.card padding="false">

        <div class="overflow-x-auto">

            <table class="w-full min-w-[980px] text-sm">

                <thead class="border-b bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-4">Usuario</th>
                        <th class="px-5 py-4">Estado</th>
                        <th class="px-5 py-4">Roles</th>
                        <th class="px-5 py-4">Sede operativa</th>
                        <th class="px-5 py-4">Último acceso</th>
                        <th class="px-5 py-4 text-right">Acción</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">

                    @forelse($usuarios as $usuario)

                        <tr class="align-top hover:bg-slate-50/70">

                            <td class="px-5 py-4">
                                <div class="font-semibold text-slate-900">
                                    {{ $usuario->name }}

                                    @if(auth()->id() === $usuario->id)
                                        <span class="ml-1 rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-blue-700">
                                            Tu cuenta
                                        </span>
                                    @endif
                                </div>

                                <div class="mt-1 text-xs text-slate-500">
                                    {{ $usuario->email }}
                                </div>
                            </td>

                            <td class="px-5 py-4">
                                @if($usuario->activo)
                                    <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700">
                                        Activo
                                    </span>
                                @else
                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">
                                        Inactivo
                                    </span>
                                @endif
                            </td>

                            <td class="px-5 py-4">
                                <div class="flex max-w-sm flex-wrap gap-1.5">
                                    @forelse($usuario->roles as $rolUsuario)
                                        <span class="rounded-full border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700">
                                            {{ $rolUsuario->nombre }}
                                        </span>
                                    @empty
                                        <span class="text-xs font-semibold text-rose-600">
                                            Sin rol asignado
                                        </span>
                                    @endforelse
                                </div>
                            </td>

                            <td class="px-5 py-4">
                                @if($usuario->almacenOperativo)
                                    <div class="font-medium text-slate-800">
                                        {{ $usuario->almacenOperativo->nombre }}
                                    </div>
                                    <div class="mt-1 text-xs text-slate-500">
                                        {{ $usuario->almacenOperativo->ciudad }}
                                    </div>
                                @elseif($usuario->tieneRol('ADMINISTRADOR'))
                                    <span class="text-sm font-medium text-slate-700">
                                        Acceso global
                                    </span>
                                @else
                                    <span class="text-sm font-semibold text-amber-700">
                                        Sin sede asignada
                                    </span>
                                @endif
                            </td>

                            <td class="px-5 py-4 text-slate-600">
                                {{ $usuario->ultimo_acceso?->format('d/m/Y H:i') ?? 'Nunca' }}
                            </td>

                            <td class="px-5 py-4 text-right">
                                <a
                                    href="{{ route('usuarios.show', $usuario) }}"
                                    class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                                >
                                    Ver usuario
                                </a>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td
                                colspan="6"
                                class="px-5 py-12 text-center text-sm text-slate-500"
                            >
                                No se encontraron usuarios con los filtros seleccionados.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        @if($usuarios->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">
                {{ $usuarios->links() }}
            </div>
        @endif

    </x-ui.card>

</div>


<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('usuarios_filtros');

    if (!form) {
        return;
    }

    const estado = document.getElementById('usuarios_filtros_estado');
    const buscar = form.querySelector('input[data-auto-submit]');
    const selects = form.querySelectorAll('select[data-auto-submit]');

    let timer = null;

    const enviar = () => {
        if (estado) {
            estado.textContent = 'Actualizando...';
        }

        form.requestSubmit();
    };

    if (buscar) {
        buscar.addEventListener('input', () => {
            window.clearTimeout(timer);
            timer = window.setTimeout(enviar, 320);
        });
    }

    selects.forEach((select) => {
        select.addEventListener('change', enviar);
    });
});
</script>

</x-layouts.oneshop>
