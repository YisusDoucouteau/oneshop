<x-layouts.oneshop
    title="Nuevo usuario | OneShop"
    page-title="Nuevo usuario"
>

<div class="mx-auto max-w-4xl space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                Nuevo usuario
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Registra una cuenta y define sus responsabilidades y sede operativa desde el inicio.
            </p>
        </div>

        <a
            href="{{ route('usuarios.index') }}"
            class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
        >
            Volver a usuarios
        </a>
    </div>


    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
            <p class="font-bold">
                Revisa los datos ingresados.
            </p>

            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif


    <form
        method="POST"
        action="{{ route('usuarios.store') }}"
        class="space-y-6"
    >
        @csrf

        <x-ui.card>
            <h2 class="text-lg font-bold text-slate-900">
                Datos de acceso
            </h2>

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
                        value="{{ old('name') }}"
                        required
                        class="mt-2 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        autocomplete="name"
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
                        value="{{ old('email') }}"
                        required
                        class="mt-2 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        autocomplete="email"
                    >
                </div>

                <div>
                    <label
                        for="password"
                        class="block text-sm font-semibold text-slate-700"
                    >
                        Contraseña temporal
                    </label>

                    <input
                        id="password"
                        name="password"
                        type="password"
                        required
                        minlength="8"
                        class="mt-2 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        autocomplete="new-password"
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
                        required
                        minlength="8"
                        class="mt-2 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        autocomplete="new-password"
                    >
                </div>

            </div>
        </x-ui.card>


        <x-ui.card>
            <h2 class="text-lg font-bold text-slate-900">
                Roles y sede
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Los usuarios operativos necesitan una sede. El Administrador puede trabajar con acceso global y dejar la sede vacía.
            </p>

            <div class="mt-5 grid gap-6 lg:grid-cols-[minmax(0,1fr)_280px]">

                <div>
                    <p class="text-sm font-semibold text-slate-700">
                        Roles
                    </p>

                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        @foreach($roles as $rol)
                            <label class="flex cursor-pointer gap-3 rounded-xl border border-slate-200 bg-white p-4 hover:border-blue-300">
                                <input
                                    type="checkbox"
                                    name="roles[]"
                                    value="{{ $rol->codigo }}"
                                    class="mt-1 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                    {{ in_array($rol->codigo, old('roles', []), true) ? 'checked' : '' }}
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

                <div>
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
                                {{ (string) old('almacen_operativo_id') === (string) $almacen->id ? 'selected' : '' }}
                            >
                                {{ $almacen->nombre }} · {{ $almacen->ciudad }}
                            </option>
                        @endforeach
                    </select>

                    <input
                        type="hidden"
                        name="activo"
                        value="0"
                    >

                    <label class="mt-5 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                        <input
                            type="checkbox"
                            name="activo"
                            value="1"
                            class="rounded border-emerald-300 text-emerald-600 focus:ring-emerald-500"
                            {{ (string) old('activo', '1') === '1' ? 'checked' : '' }}
                        >

                        <span>
                            <span class="block text-sm font-semibold text-emerald-800">
                                Cuenta activa
                            </span>
                            <span class="mt-1 block text-xs text-emerald-700">
                                Podrá iniciar sesión inmediatamente.
                            </span>
                        </span>
                    </label>
                </div>

            </div>
        </x-ui.card>


        <div class="flex justify-end gap-3">
            <a
                href="{{ route('usuarios.index') }}"
                class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            >
                Cancelar
            </a>

            <button
                type="submit"
                class="inline-flex items-center justify-center rounded-xl bg-oneshop-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:opacity-90"
            >
                Crear usuario
            </button>
        </div>
    </form>

</div>

</x-layouts.oneshop>
