<x-layouts.oneshop
    title="{{ $usuario->name }} | Usuarios | OneShop"
    page-title="Detalle de usuario"
>

@php
    $puedeGestionar =
        auth()->user()?->tienePermiso('usuarios.gestionar')
        && auth()->id() !== $usuario->id;

    $esCuentaActual =
        auth()->id() === $usuario->id;

    $rolesSeleccionados =
        old(
            'roles',
            $usuario->roles->pluck('codigo')->all()
        );

    $accionesAuditoria = [
        'CREAR_USUARIO' => 'Usuario creado',
        'ACTUALIZAR_ACCESO_USUARIO' => 'Acceso actualizado',
        'ACTIVAR_USUARIO' => 'Usuario activado',
        'DESACTIVAR_USUARIO' => 'Usuario desactivado',
        'RESTABLECER_CONTRASENA_USUARIO' => 'Contraseña restablecida',
    ];
@endphp

<div class="space-y-6">

    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-2xl font-bold text-slate-900">
                    {{ $usuario->name }}
                </h1>

                @if($usuario->activo)
                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700">
                        Activo
                    </span>
                @else
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">
                        Inactivo
                    </span>
                @endif

                @if($esCuentaActual)
                    <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700">
                        Tu cuenta
                    </span>
                @endif
            </div>

            <p class="mt-1 text-sm text-slate-500">
                {{ $usuario->email }}
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            @if($esCuentaActual)
                <a
                    href="{{ route('profile.edit') }}"
                    class="inline-flex items-center justify-center rounded-xl border border-blue-200 bg-blue-50 px-4 py-2.5 text-sm font-semibold text-blue-700 hover:bg-blue-100"
                >
                    Abrir mi perfil
                </a>
            @endif

            <a
                href="{{ route('usuarios.index') }}"
                class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            >
                Volver a usuarios
            </a>
        </div>
    </div>


    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
            <p class="font-bold">
                No se pudo completar la operación.
            </p>

            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif


    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">
                Estado
            </p>
            <p class="mt-2 text-lg font-bold text-slate-900">
                {{ $usuario->activo ? 'Activo' : 'Inactivo' }}
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">
                Sede operativa
            </p>
            <p class="mt-2 text-lg font-bold text-slate-900">
                @if($usuario->almacenOperativo)
                    {{ $usuario->almacenOperativo->nombre }}
                @elseif($usuario->tieneRol('ADMINISTRADOR'))
                    Acceso global
                @else
                    Sin sede asignada
                @endif
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">
                Último acceso
            </p>
            <p class="mt-2 text-lg font-bold text-slate-900">
                {{ $usuario->ultimo_acceso?->format('d/m/Y H:i') ?? 'Nunca' }}
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">
                Roles asignados
            </p>
            <p class="mt-2 text-lg font-bold text-slate-900">
                {{ $usuario->roles->count() }}
            </p>
        </div>

    </div>


    <x-ui.card>
        <h2 class="text-lg font-bold text-slate-900">
            Roles actuales
        </h2>

        <div class="mt-4 flex flex-wrap gap-2">
            @forelse($usuario->roles as $rolUsuario)
                <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-sm font-semibold text-slate-700">
                    {{ $rolUsuario->nombre }}
                </span>
            @empty
                <span class="text-sm font-semibold text-rose-600">
                    Este usuario no tiene roles asignados.
                </span>
            @endforelse
        </div>
    </x-ui.card>


    @if($esCuentaActual)
        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5 text-sm text-blue-800">
            Los roles, permisos y sede de tu propia cuenta no se modifican desde esta pantalla. Los datos personales y la contraseña se administran desde <strong>Perfil</strong>.
        </div>
    @elseif($puedeGestionar)

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.4fr)_minmax(320px,0.6fr)]">

            <form
                method="POST"
                action="{{ route('usuarios.update', $usuario) }}"
                class="space-y-6"
            >
                @csrf
                @method('PUT')

                <x-ui.card>
                    <h2 class="text-lg font-bold text-slate-900">
                        Acceso, roles y sede
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Los cambios quedan registrados en auditoría.
                    </p>

                    <div class="mt-5 grid gap-5 md:grid-cols-2">

                        <div>
                            <label
                                for="name"
                                class="block text-sm font-semibold text-slate-700"
                            >
                                Nombre completo
                            </label>

                            <input
                                id="name"
                                name="name"
                                value="{{ old('name', $usuario->name) }}"
                                required
                                class="mt-2 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                        </div>

                        <div>
                            <label
                                for="email"
                                class="block text-sm font-semibold text-slate-700"
                            >
                                Correo electrónico
                            </label>

                            <input
                                id="email"
                                name="email"
                                type="email"
                                value="{{ old('email', $usuario->email) }}"
                                required
                                class="mt-2 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                        </div>

                    </div>


                    <div class="mt-6">
                        <p class="text-sm font-semibold text-slate-700">
                            Roles
                        </p>

                        <div class="mt-3 grid gap-3 md:grid-cols-2">
                            @foreach($roles as $rol)
                                <label class="flex cursor-pointer gap-3 rounded-xl border border-slate-200 p-4 hover:border-blue-300">
                                    <input
                                        type="checkbox"
                                        name="roles[]"
                                        value="{{ $rol->codigo }}"
                                        class="mt-1 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                        {{ in_array($rol->codigo, $rolesSeleccionados, true) ? 'checked' : '' }}
                                    >

                                    <span>
                                        <span class="block font-semibold text-slate-900">
                                            {{ $rol->nombre }}
                                        </span>

                                        @if($rol->descripcion)
                                            <span class="mt-1 block text-xs leading-5 text-slate-500">
                                                {{ $rol->descripcion }}
                                            </span>
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>


                    <div class="mt-6">
                        <label
                            for="almacen_operativo_id"
                            class="block text-sm font-semibold text-slate-700"
                        >
                            Sede operativa
                        </label>

                        <select
                            id="almacen_operativo_id"
                            name="almacen_operativo_id"
                            class="mt-2 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="">
                                Acceso global / sin sede
                            </option>

                            @foreach($almacenes as $almacen)
                                <option
                                    value="{{ $almacen->id }}"
                                    {{ (string) old('almacen_operativo_id', $usuario->almacen_operativo_id) === (string) $almacen->id ? 'selected' : '' }}
                                >
                                    {{ $almacen->nombre }} · {{ $almacen->ciudad }}
                                </option>
                            @endforeach
                        </select>

                        <p class="mt-2 text-xs text-slate-500">
                            La sede es obligatoria para perfiles operativos. El Administrador puede mantener acceso global.
                        </p>
                    </div>


                    <div class="mt-6 flex justify-end">
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-xl bg-oneshop-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:opacity-90"
                        >
                            Guardar cambios
                        </button>
                    </div>
                </x-ui.card>
            </form>


            <div class="space-y-6">

                <x-ui.card>
                    <h2 class="text-lg font-bold text-slate-900">
                        Estado de acceso
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Al desactivar una cuenta, la sesión será invalidada al siguiente acceso protegido.
                    </p>

                    <form
                        method="POST"
                        action="{{ route('usuarios.estado', $usuario) }}"
                        class="mt-5"
                    >
                        @csrf
                        @method('PATCH')

                        <input
                            type="hidden"
                            name="activo"
                            value="{{ $usuario->activo ? '0' : '1' }}"
                        >

                        <button
                            type="submit"
                            class="{{
                                $usuario->activo
                                    ? 'w-full rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-700 hover:bg-rose-100'
                                    : 'w-full rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 hover:bg-emerald-100'
                            }}"
                        >
                            {{ $usuario->activo ? 'Desactivar usuario' : 'Activar usuario' }}
                        </button>
                    </form>
                </x-ui.card>


                <x-ui.card>
                    <h2 class="text-lg font-bold text-slate-900">
                        Restablecer contraseña
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        La contraseña nunca se guarda dentro del historial de auditoría.
                    </p>

                    <form
                        method="POST"
                        action="{{ route('usuarios.contrasena', $usuario) }}"
                        class="mt-5 space-y-4"
                    >
                        @csrf
                        @method('PUT')

                        <div>
                            <label
                                for="password"
                                class="block text-sm font-semibold text-slate-700"
                            >
                                Nueva contraseña
                            </label>

                            <input
                                id="password"
                                name="password"
                                type="password"
                                minlength="8"
                                required
                                autocomplete="new-password"
                                class="mt-2 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                        </div>

                        <div>
                            <label
                                for="password_confirmation"
                                class="block text-sm font-semibold text-slate-700"
                            >
                                Confirmar contraseña
                            </label>

                            <input
                                id="password_confirmation"
                                name="password_confirmation"
                                type="password"
                                minlength="8"
                                required
                                autocomplete="new-password"
                                class="mt-2 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                        </div>

                        <button
                            type="submit"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            Restablecer contraseña
                        </button>
                    </form>
                </x-ui.card>

            </div>

        </div>

    @endif


    @if($auditorias->isNotEmpty())

        <x-ui.card>
            <div>
                <h2 class="text-lg font-bold text-slate-900">
                    Actividad administrativa reciente
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Cambios de acceso realizados sobre esta cuenta.
                </p>
            </div>

            <div class="mt-5 divide-y divide-slate-100">
                @foreach($auditorias as $auditoria)
                    <div class="flex flex-col gap-2 py-4 first:pt-0 last:pb-0 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="font-semibold text-slate-900">
                                {{ $accionesAuditoria[$auditoria->accion] ?? 'Cambio administrativo' }}
                            </p>

                            <p class="mt-1 text-sm text-slate-500">
                                Realizado por {{ $auditoria->usuario?->name ?? 'Sistema' }}
                            </p>
                        </div>

                        <p class="text-xs font-medium text-slate-500">
                            {{ $auditoria->fecha_evento?->format('d/m/Y H:i') }}
                        </p>
                    </div>
                @endforeach
            </div>
        </x-ui.card>

    @endif

</div>

</x-layouts.oneshop>
