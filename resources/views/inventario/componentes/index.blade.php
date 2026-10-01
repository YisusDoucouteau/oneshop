<x-layouts.oneshop
    title="Componentes | OneShop"
    page-title="Inventario"
>
    <div
        x-data="{
            compraAbierta:
                {{
                    old('formulario') !== 'regularizacion'
                    && (
                        request()->boolean('compra')
                        || $errors->has('compra_componente')
                    || $errors->has('producto_id')
                    || $errors->has('cantidad')
                    || $errors->has('moneda_id')
                    || $errors->has('monto_total_origen')
                    || $errors->has('tipo_cambio_aplicado')
                    || $errors->has('codigo')
                    || $errors->has('nombre')
                    || $errors->has('categoria_producto_id')
                    || $errors->has('marca_id')
                    || $errors->has('modelo')
                        || $errors->has('descripcion')
                    )
                    ? 'true'
                    : 'false'
                }},

            componenteAbierto:
                {{
                    $errors->has('codigo')
                    || $errors->has('nombre')
                    || $errors->has('categoria_producto_id')
                    || $errors->has('marca_id')
                    || $errors->has('modelo')
                    || $errors->has('descripcion')
                    ? 'true'
                    : 'false'
                }},

            regularizacionAbierta:
                {{
                    old('formulario') === 'regularizacion'
                    || $errors->has('regularizacion')
                    ? 'true'
                    : 'false'
                }},

            regularizacion: {
                productoId:
                    @js((int) old('producto_id', 0)),

                almacenId:
                    @js(
                        (int) old(
                            'almacen_id',
                            $almacenSeleccionado->id
                        )
                    ),

                nombre:
                    @js(
                        old(
                            'regularizacion_nombre',
                            ''
                        )
                    ),

                disponible:
                    @js(
                        (int) old(
                            'regularizacion_disponible',
                            0
                        )
                    ),

                reservado:
                    @js(
                        (int) old(
                            'regularizacion_reservado',
                            0
                        )
                    ),

                costo:
                    @js(
                        old(
                            'costo_unitario_bob',
                            ''
                        )
                    ),

                referencia:
                    @js(
                        old(
                            'referencia',
                            'INVENTARIO-INICIAL-'
                            .now()->format('Y')
                        )
                    ),

                motivo:
                    @js(
                        old(
                            'motivo',
                            ''
                        )
                    ),
            },

            monedaCodigo:
                @js(
                    optional(
                        $monedas->firstWhere(
                            'id',
                            (int) old(
                                'moneda_id',
                                $monedas->firstWhere(
                                    'codigo',
                                    'BOB'
                                )?->id
                            )
                        )
                    )->codigo ?? 'BOB'
                ),

            cantidadCompra:
                @js((int) old('cantidad', 1)),

            montoTotalOrigen:
                @js((float) old('monto_total_origen', 0)),

            tipoCambioCompra:
                @js(
                    old(
                        'tipo_cambio_aplicado',
                        null
                    ) !== null
                        && old(
                            'tipo_cambio_aplicado'
                        ) !== ''
                        ? (float) old(
                            'tipo_cambio_aplicado'
                        )
                        : null
                ),

            nombreComponente:
                @js(old('nombre', '')),

            modeloComponente:
                @js(old('modelo', '')),

            abrirCompra() {
                this.compraAbierta = true;
            },

            cerrarCompra() {
                if (!this.componenteAbierto) {
                    this.compraAbierta = false;
                }
            },

            abrirComponente() {
                this.componenteAbierto = true;
            },

            cerrarComponente() {
                this.componenteAbierto = false;
            },

            abrirRegularizacion(datos) {
                this.regularizacion = {
                    productoId:
                        Number(datos.productoId),

                    almacenId:
                        Number(datos.almacenId),

                    nombre:
                        datos.nombre,

                    disponible:
                        Number(datos.disponible),

                    reservado:
                        Number(datos.reservado),

                    costo:
                        '',

                    referencia:
                        `INVENTARIO-INICIAL-${new Date().getFullYear()}`,

                    motivo:
                        '',
                };

                this.regularizacionAbierta = true;
            },

            cerrarRegularizacion() {
                this.regularizacionAbierta = false;
            },

            stockFisicoRegularizacion() {
                return (
                    Number(
                        this.regularizacion.disponible
                    )
                    +
                    Number(
                        this.regularizacion.reservado
                    )
                );
            },

            valorRegularizacion() {
                const costo =
                    Number(
                        this.regularizacion.costo
                    );

                const stock =
                    this.stockFisicoRegularizacion();

                if (
                    !costo
                    || costo <= 0
                    || stock <= 0
                ) {
                    return null;
                }

                return costo * stock;
            },

            cambiarMoneda(codigo) {
                this.monedaCodigo = codigo;

                if (codigo === 'BOB') {
                    this.tipoCambioCompra = null;
                }
            },

            costoUnitarioOrigen() {
                const cantidad =
                    Number(this.cantidadCompra);

                const total =
                    Number(this.montoTotalOrigen);

                if (
                    !cantidad
                    || cantidad <= 0
                    || !total
                    || total <= 0
                ) {
                    return null;
                }

                return total / cantidad;
            },

            totalBob() {
                const total =
                    Number(this.montoTotalOrigen);

                if (
                    !total
                    || total <= 0
                ) {
                    return null;
                }

                if (
                    this.monedaCodigo === 'BOB'
                ) {
                    return total;
                }

                const tc =
                    Number(this.tipoCambioCompra);

                if (
                    !tc
                    || tc <= 0
                ) {
                    return null;
                }

                return total * tc;
            },

            costoUnitarioBob() {
                const cantidad =
                    Number(this.cantidadCompra);

                const total =
                    this.totalBob();

                if (
                    !cantidad
                    || cantidad <= 0
                    || total === null
                ) {
                    return null;
                }

                return total / cantidad;
            },

            formatoMonto(valor) {
                if (
                    valor === null
                    || valor === undefined
                    || Number.isNaN(
                        Number(valor)
                    )
                ) {
                    return '—';
                }

                return new Intl.NumberFormat(
                    'es-BO',
                    {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                    }
                ).format(
                    Number(valor)
                );
            },

            codigoComponentePreview() {
                let texto =
                    `CMP-${this.nombreComponente || ''}`;

                const modelo =
                    (this.modeloComponente || '')
                        .trim();

                if (
                    modelo !== ''
                    && !texto
                        .toUpperCase()
                        .includes(
                            modelo.toUpperCase()
                        )
                ) {
                    texto += `-${modelo}`;
                }

                texto = texto
                    .normalize('NFD')
                    .replace(
                        /[\u0300-\u036f]/g,
                        ''
                    )
                    .toUpperCase()
                    .replace(
                        /[^A-Z0-9]+/g,
                        '-'
                    )
                    .replace(
                        /^-+|-+$/g,
                        ''
                    );

                if (
                    texto === 'CMP'
                    || texto === ''
                ) {
                    return 'Se generará al guardar';
                }

                return `${texto}-001`;
            },
        }"
        class="space-y-6"
    >
        {{-- Encabezado --}}
        <div class="space-y-4">
            <div class="
                inline-flex
                overflow-hidden
                rounded-xl
                border
                border-slate-200
                bg-white
                p-1
            ">
                <a
                    href="{{ route('inventario.index') }}"
                    class="
                        rounded-lg
                        px-3
                        py-2
                        text-sm
                        font-semibold
                        text-slate-600
                        transition
                        hover:bg-slate-50
                        hover:text-slate-900
                    "
                >
                    Equipos
                </a>

                <span class="
                    rounded-lg
                    bg-oneshop-light
                    px-3
                    py-2
                    text-sm
                    font-semibold
                    text-oneshop-dark
                ">
                    Componentes
                </span>
            </div>

            <div class="
                flex
                flex-col
                gap-4
                sm:flex-row
                sm:items-end
                sm:justify-between
            ">
                <div>
                    <h1 class="
                        text-2xl
                        font-bold
                        tracking-tight
                        text-slate-950
                    ">
                        Inventario de componentes
                    </h1>

                    <p class="
                        mt-1
                        max-w-2xl
                        text-sm
                        text-slate-500
                    ">
                        Controla existencias, compras, valoración y consumo de repuestos y accesorios.
                    </p>
                </div>

                @if(auth()->user()->tienePermiso('inventario.registrar'))
                    <button
                        type="button"
                        @click="abrirCompra()"
                        class="
                            inline-flex
                            items-center
                            justify-center
                            rounded-xl
                            border
                            border-oneshop-primary
                            bg-oneshop-light
                            px-4
                            py-2.5
                            text-sm
                            font-semibold
                            text-oneshop-dark
                            transition
                            hover:bg-blue-100
                        "
                    >
                        Registrar compra
                    </button>
                @endif
            </div>
        </div>

        {{-- Alertas --}}
        @if(
            session('success')
            && !request()->boolean('compra')
        )
            <div class="
                rounded-xl
                border
                border-emerald-200
                bg-emerald-50
                px-4
                py-3
                text-sm
                font-medium
                text-emerald-800
            ">
                {{ session('success') }}
            </div>
        @endif

        @if($resumen->sin_valorar > 0)
            <div class="
                flex
                gap-3
                rounded-xl
                border
                border-amber-200
                bg-amber-50
                px-4
                py-3
                text-sm
                text-amber-900
            ">
                <div>
                    <p class="font-semibold">
                        Hay {{ number_format($resumen->sin_valorar) }}
                        componente(s) con stock pendiente de valoración.
                    </p>

                    <p class="mt-1 text-amber-800">
                        Ese stock no puede consumirse ni mezclarse con nuevas compras hasta regularizar su costo.
                    </p>
                </div>
            </div>
        @endif

        {{-- Contexto de almacén --}}
        <section class="
            rounded-2xl
            border
            border-slate-200
            bg-white
            p-5
            shadow-sm
        ">
            <form
                method="GET"
                action="{{ route('inventario.componentes.index') }}"
                class="
                    grid
                    gap-3
                    lg:grid-cols-12
                "
            >
                <div class="lg:col-span-5">
                    <label
                        for="buscar"
                        class="
                            mb-1.5
                            block
                            text-xs
                            font-semibold
                            uppercase
                            tracking-wide
                            text-slate-500
                        "
                    >
                        Buscar
                    </label>

                    <input
                        id="buscar"
                        name="buscar"
                        type="search"
                        value="{{ $busqueda }}"
                        placeholder="Código, componente, marca o modelo..."
                        class="
                            w-full
                            rounded-xl
                            border-slate-300
                            text-sm
                            focus:border-oneshop-primary
                            focus:ring-oneshop-primary
                        "
                    >
                </div>

                <div class="lg:col-span-3">
                    <label
                        for="almacen"
                        class="
                            mb-1.5
                            block
                            text-xs
                            font-semibold
                            uppercase
                            tracking-wide
                            text-slate-500
                        "
                    >
                        Almacén
                    </label>

                    <select
                        id="almacen"
                        name="almacen"
                        class="
                            w-full
                            rounded-xl
                            border-slate-300
                            text-sm
                            focus:border-oneshop-primary
                            focus:ring-oneshop-primary
                        "
                    >
                        @foreach($almacenes as $almacen)
                            <option
                                value="{{ $almacen->id }}"
                                @selected(
                                    $almacenSeleccionado->id
                                    === $almacen->id
                                )
                            >
                                {{ $almacen->nombre }}
                                · {{ $almacen->ciudad }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-2">
                    <label
                        for="estado"
                        class="
                            mb-1.5
                            block
                            text-xs
                            font-semibold
                            uppercase
                            tracking-wide
                            text-slate-500
                        "
                    >
                        Estado
                    </label>

                    <select
                        id="estado"
                        name="estado"
                        class="
                            w-full
                            rounded-xl
                            border-slate-300
                            text-sm
                            focus:border-oneshop-primary
                            focus:ring-oneshop-primary
                        "
                    >
                        <option
                            value=""
                            @selected($estado === '')
                        >
                            Todos
                        </option>

                        <option
                            value="CON_STOCK"
                            @selected($estado === 'CON_STOCK')
                        >
                            Con stock
                        </option>

                        <option
                            value="SIN_VALORAR"
                            @selected($estado === 'SIN_VALORAR')
                        >
                            Sin valorar
                        </option>

                        <option
                            value="AGOTADO"
                            @selected($estado === 'AGOTADO')
                        >
                            Agotados
                        </option>
                    </select>
                </div>

                <div class="
                    flex
                    items-end
                    gap-2
                    lg:col-span-2
                ">
                    <button
                        type="submit"
                        class="
                            flex-1
                            rounded-xl
                            border
                            border-oneshop-primary
                            bg-oneshop-light
                            px-4
                            py-2.5
                            text-sm
                            font-semibold
                            text-oneshop-dark
                            transition
                            hover:bg-blue-100
                        "
                    >
                        Filtrar
                    </button>

                    <a
                        href="{{ route(
                            'inventario.componentes.index',
                            [
                                'almacen' =>
                                    $almacenSeleccionado->id,
                            ]
                        ) }}"
                        class="
                            rounded-xl
                            border
                            border-slate-300
                            px-4
                            py-2.5
                            text-sm
                            font-semibold
                            text-slate-600
                            transition
                            hover:bg-slate-50
                        "
                    >
                        ×
                    </a>
                </div>
            </form>
        </section>

        {{-- Métricas --}}
        <div class="
            grid
            gap-4
            sm:grid-cols-2
            xl:grid-cols-4
        ">
            <div class="
                rounded-2xl
                border
                border-slate-200
                bg-white
                p-5
                shadow-sm
            ">
                <p class="
                    text-xs
                    font-semibold
                    uppercase
                    tracking-wide
                    text-slate-500
                ">
                    Componentes
                </p>

                <p class="
                    mt-3
                    text-3xl
                    font-black
                    tracking-tight
                    text-slate-950
                ">
                    {{ number_format($resumen->componentes) }}
                </p>

                <p class="mt-1 text-xs text-slate-400">
                    Con existencia registrada
                </p>
            </div>

            <div class="
                rounded-2xl
                border
                border-slate-200
                bg-white
                p-5
                shadow-sm
            ">
                <p class="
                    text-xs
                    font-semibold
                    uppercase
                    tracking-wide
                    text-slate-500
                ">
                    Disponibles
                </p>

                <p class="
                    mt-3
                    text-3xl
                    font-black
                    tracking-tight
                    text-slate-950
                ">
                    {{ number_format($resumen->disponibles) }}
                </p>

                <p class="mt-1 text-xs text-slate-400">
                    {{ number_format($resumen->reservadas) }}
                    reservadas
                </p>
            </div>

            <div class="
                rounded-2xl
                border
                border-slate-200
                bg-white
                p-5
                shadow-sm
            ">
                <p class="
                    text-xs
                    font-semibold
                    uppercase
                    tracking-wide
                    text-slate-500
                ">
                    Valor del stock
                </p>

                <p class="
                    mt-3
                    text-3xl
                    font-black
                    tracking-tight
                    text-slate-950
                ">
                    Bs {{ number_format(
                        (float) $resumen->valor_stock_bob,
                        2
                    ) }}
                </p>

                <p class="mt-1 text-xs text-slate-400">
                    Disponible + reservado con costo conocido
                </p>
            </div>

            <div class="
                rounded-2xl
                border
                border-slate-200
                bg-white
                p-5
                shadow-sm
            ">
                <p class="
                    text-xs
                    font-semibold
                    uppercase
                    tracking-wide
                    text-slate-500
                ">
                    Sin valorar
                </p>

                <p class="
                    mt-3
                    text-3xl
                    font-black
                    tracking-tight
                    {{ $resumen->sin_valorar > 0
                        ? 'text-amber-700'
                        : 'text-slate-950'
                    }}
                ">
                    {{ number_format($resumen->sin_valorar) }}
                </p>

                <p class="mt-1 text-xs text-slate-400">
                    Requieren regularización
                </p>
            </div>
        </div>

        @php
            $puedeRegularizar =
                auth()
                    ->user()
                    ->tienePermiso(
                        'inventario.registrar'
                    );
        @endphp

        @if(
            $resumen->sin_valorar > 0
            && $puedeRegularizar
        )
            <div class="
                flex
                flex-col
                gap-3
                rounded-2xl
                border
                border-amber-200
                bg-amber-50
                px-5
                py-4
                sm:flex-row
                sm:items-center
                sm:justify-between
            ">
                <div>
                    <p class="
                        font-semibold
                        text-amber-900
                    ">
                        Hay stock físico pendiente de valoración
                    </p>

                    <p class="
                        mt-1
                        text-sm
                        text-amber-800
                    ">
                        Regulariza su costo inicial antes de comprar más unidades o consumirlas desde preparación.
                    </p>
                </div>

                <a
                    href="{{ route(
                        'inventario.componentes.index',
                        [
                            'almacen' =>
                                $almacenSeleccionado->id,
                            'estado' =>
                                'SIN_VALORAR',
                        ]
                    ) }}"
                    class="
                        inline-flex
                        shrink-0
                        items-center
                        justify-center
                        rounded-xl
                        border
                        border-amber-300
                        bg-white
                        px-4
                        py-2.5
                        text-sm
                        font-semibold
                        text-amber-900
                        transition
                        hover:bg-amber-100
                    "
                >
                    Ver pendientes
                </a>
            </div>
        @endif

        {{-- Existencias --}}
        <section class="
            overflow-hidden
            rounded-2xl
            border
            border-slate-200
            bg-white
            shadow-sm
        ">
            <div class="
                flex
                flex-col
                gap-2
                border-b
                border-slate-200
                px-5
                py-4
                sm:flex-row
                sm:items-center
                sm:justify-between
            ">
                <div>
                    <h2 class="
                        font-semibold
                        text-slate-950
                    ">
                        Existencias en
                        {{ $almacenSeleccionado->nombre }}
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        El valor físico incluye las unidades disponibles y reservadas.
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="
                    min-w-full
                    divide-y
                    divide-slate-200
                ">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="
                                px-5
                                py-3
                                text-left
                                text-xs
                                font-semibold
                                uppercase
                                tracking-wider
                                text-slate-500
                            ">
                                Componente
                            </th>

                            <th class="
                                px-5
                                py-3
                                text-right
                                text-xs
                                font-semibold
                                uppercase
                                tracking-wider
                                text-slate-500
                            ">
                                Disponible
                            </th>

                            <th class="
                                px-5
                                py-3
                                text-right
                                text-xs
                                font-semibold
                                uppercase
                                tracking-wider
                                text-slate-500
                            ">
                                Reservado
                            </th>

                            <th class="
                                px-5
                                py-3
                                text-right
                                text-xs
                                font-semibold
                                uppercase
                                tracking-wider
                                text-slate-500
                            ">
                                Costo promedio
                            </th>

                            <th class="
                                px-5
                                py-3
                                text-right
                                text-xs
                                font-semibold
                                uppercase
                                tracking-wider
                                text-slate-500
                            ">
                                Valor físico
                            </th>

                            <th class="
                                px-5
                                py-3
                                text-left
                                text-xs
                                font-semibold
                                uppercase
                                tracking-wider
                                text-slate-500
                            ">
                                Estado
                            </th>

                            @if($puedeRegularizar)
                                <th class="
                                    px-5
                                    py-3
                                    text-right
                                    text-xs
                                    font-semibold
                                    uppercase
                                    tracking-wider
                                    text-slate-500
                                ">
                                    Acción
                                </th>
                            @endif
                        </tr>
                    </thead>

                    <tbody class="
                        divide-y
                        divide-slate-100
                        bg-white
                    ">
                        @forelse($componentes as $componente)
                            @php
                                $existencia =
                                    $componente
                                        ->almacenes
                                        ->first();

                                $disponible =
                                    (int) (
                                        $existencia
                                            ?->pivot
                                            ?->cantidad_disponible
                                        ?? 0
                                    );

                                $reservado =
                                    (int) (
                                        $existencia
                                            ?->pivot
                                            ?->cantidad_reservada
                                        ?? 0
                                    );

                                $fisico =
                                    $disponible
                                    +
                                    $reservado;

                                $promedio =
                                    $existencia
                                        ?->pivot
                                        ?->costo_promedio_bob;

                                $valor =
                                    $promedio !== null
                                        ? $fisico
                                            * (float) $promedio
                                        : null;
                            @endphp

                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-4">
                                    <p class="
                                        font-semibold
                                        text-slate-950
                                    ">
                                        {{ $componente->nombre }}
                                    </p>

                                    <p class="
                                        mt-1
                                        text-sm
                                        text-slate-500
                                    ">
                                        {{ $componente->marca?->nombre }}

                                        @if($componente->modelo)
                                            · {{ $componente->modelo }}
                                        @endif
                                    </p>

                                    <p class="
                                        mt-1
                                        font-mono
                                        text-xs
                                        text-slate-400
                                    ">
                                        {{ $componente->codigo }}
                                    </p>
                                </td>

                                <td class="
                                    whitespace-nowrap
                                    px-5
                                    py-4
                                    text-right
                                    text-sm
                                    font-semibold
                                    text-slate-900
                                ">
                                    {{ number_format($disponible) }}
                                </td>

                                <td class="
                                    whitespace-nowrap
                                    px-5
                                    py-4
                                    text-right
                                    text-sm
                                    text-slate-600
                                ">
                                    {{ number_format($reservado) }}
                                </td>

                                <td class="
                                    whitespace-nowrap
                                    px-5
                                    py-4
                                    text-right
                                ">
                                    @if($promedio !== null)
                                        <span class="
                                            font-semibold
                                            text-slate-900
                                        ">
                                            Bs {{ number_format(
                                                (float) $promedio,
                                                2
                                            ) }}
                                        </span>
                                    @else
                                        <span class="
                                            inline-flex
                                            rounded-full
                                            bg-amber-100
                                            px-2.5
                                            py-1
                                            text-xs
                                            font-semibold
                                            text-amber-800
                                        ">
                                            Pendiente
                                        </span>
                                    @endif
                                </td>

                                <td class="
                                    whitespace-nowrap
                                    px-5
                                    py-4
                                    text-right
                                    text-sm
                                    font-semibold
                                    text-slate-900
                                ">
                                    @if($valor !== null)
                                        Bs {{ number_format(
                                            $valor,
                                            2
                                        ) }}
                                    @else
                                        <span class="text-slate-400">
                                            —
                                        </span>
                                    @endif
                                </td>

                                <td class="
                                    whitespace-nowrap
                                    px-5
                                    py-4
                                ">
                                    @if(
                                        $fisico > 0
                                        && $promedio === null
                                    )
                                        <span class="
                                            inline-flex
                                            rounded-full
                                            bg-amber-100
                                            px-2.5
                                            py-1
                                            text-xs
                                            font-semibold
                                            text-amber-800
                                        ">
                                            Sin valorar
                                        </span>
                                    @elseif($fisico === 0)
                                        <span class="
                                            inline-flex
                                            rounded-full
                                            bg-slate-100
                                            px-2.5
                                            py-1
                                            text-xs
                                            font-semibold
                                            text-slate-600
                                        ">
                                            Agotado
                                        </span>
                                    @else
                                        <span class="
                                            inline-flex
                                            rounded-full
                                            bg-emerald-100
                                            px-2.5
                                            py-1
                                            text-xs
                                            font-semibold
                                            text-emerald-700
                                        ">
                                            Valorizado
                                        </span>
                                    @endif
                                </td>

                                @if($puedeRegularizar)
                                    <td class="
                                        whitespace-nowrap
                                        px-5
                                        py-4
                                        text-right
                                    ">
                                        @if(
                                            $fisico > 0
                                            && $promedio === null
                                        )
                                            <button
                                                type="button"
                                                data-regularizar-producto="{{ $componente->id }}"
                                                @click="abrirRegularizacion({
                                                    productoId: {{ $componente->id }},
                                                    almacenId: {{ $almacenSeleccionado->id }},
                                                    nombre: @js($componente->nombre),
                                                    disponible: {{ $disponible }},
                                                    reservado: {{ $reservado }}
                                                })"
                                                class="
                                                    inline-flex
                                                    items-center
                                                    justify-center
                                                    rounded-xl
                                                    border
                                                    border-amber-300
                                                    bg-amber-50
                                                    px-3
                                                    py-2
                                                    text-xs
                                                    font-semibold
                                                    text-amber-900
                                                    transition
                                                    hover:bg-amber-100
                                                "
                                            >
                                                Regularizar valoración
                                            </button>
                                        @else
                                            <span class="text-slate-300">
                                                —
                                            </span>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="{{ $puedeRegularizar ? 7 : 6 }}"
                                    class="
                                        px-5
                                        py-14
                                        text-center
                                    "
                                >
                                    <p class="
                                        font-semibold
                                        text-slate-900
                                    ">
                                        No hay componentes para mostrar
                                    </p>

                                    <p class="
                                        mt-2
                                        text-sm
                                        text-slate-500
                                    ">
                                        Registra una compra o cambia los filtros seleccionados.
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($componentes->hasPages())
                <div class="
                    border-t
                    border-slate-200
                    px-5
                    py-4
                ">
                    {{ $componentes->links() }}
                </div>
            @endif
        </section>

        {{-- Movimientos recientes --}}
        <section class="
            overflow-hidden
            rounded-2xl
            border
            border-slate-200
            bg-white
            shadow-sm
        ">
            <div class="
                border-b
                border-slate-200
                px-5
                py-4
            ">
                <h2 class="
                    font-semibold
                    text-slate-950
                ">
                    Movimientos recientes
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Últimos movimientos cuantitativos del almacén seleccionado.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="
                    min-w-full
                    divide-y
                    divide-slate-200
                ">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="
                                px-5
                                py-3
                                text-left
                                text-xs
                                font-semibold
                                uppercase
                                tracking-wider
                                text-slate-500
                            ">
                                Fecha
                            </th>

                            <th class="
                                px-5
                                py-3
                                text-left
                                text-xs
                                font-semibold
                                uppercase
                                tracking-wider
                                text-slate-500
                            ">
                                Componente
                            </th>

                            <th class="
                                px-5
                                py-3
                                text-left
                                text-xs
                                font-semibold
                                uppercase
                                tracking-wider
                                text-slate-500
                            ">
                                Movimiento
                            </th>

                            <th class="
                                px-5
                                py-3
                                text-right
                                text-xs
                                font-semibold
                                uppercase
                                tracking-wider
                                text-slate-500
                            ">
                                Variación
                            </th>

                            <th class="
                                px-5
                                py-3
                                text-right
                                text-xs
                                font-semibold
                                uppercase
                                tracking-wider
                                text-slate-500
                            ">
                                Costo
                            </th>
                        </tr>
                    </thead>

                    <tbody class="
                        divide-y
                        divide-slate-100
                        bg-white
                    ">
                        @forelse(
                            $movimientosRecientes
                            as $movimiento
                        )
                            <tr>
                                <td class="
                                    whitespace-nowrap
                                    px-5
                                    py-4
                                    text-sm
                                    text-slate-600
                                ">
                                    {{ $movimiento
                                        ->fecha_movimiento
                                        ?->format('d/m/Y H:i')
                                    }}
                                </td>

                                <td class="px-5 py-4">
                                    <p class="
                                        font-medium
                                        text-slate-900
                                    ">
                                        {{ $movimiento
                                            ->producto
                                            ?->nombre
                                        ?? 'Componente'
                                        }}
                                    </p>

                                    <p class="
                                        mt-1
                                        text-xs
                                        text-slate-400
                                    ">
                                        {{ $movimiento
                                            ->producto
                                            ?->marca
                                            ?->nombre
                                        }}
                                    </p>
                                </td>

                                <td class="
                                    px-5
                                    py-4
                                    text-sm
                                    text-slate-600
                                ">
                                    {{ $movimiento
                                        ->tipoMovimiento
                                        ?->nombre
                                    ?? 'Movimiento'
                                    }}
                                </td>

                                <td class="
                                    whitespace-nowrap
                                    px-5
                                    py-4
                                    text-right
                                    text-sm
                                    font-semibold
                                    {{ $movimiento->cambio_disponible >= 0
                                        ? 'text-emerald-700'
                                        : 'text-rose-700'
                                    }}
                                ">
                                    {{ $movimiento->cambio_disponible >= 0
                                        ? '+'
                                        : ''
                                    }}{{ number_format(
                                        $movimiento
                                            ->cambio_disponible
                                    ) }}
                                </td>

                                <td class="
                                    whitespace-nowrap
                                    px-5
                                    py-4
                                    text-right
                                    text-sm
                                    text-slate-700
                                ">
                                    @if(
                                        $movimiento
                                            ->costo_total_bob
                                        !== null
                                    )
                                        Bs {{ number_format(
                                            (float)
                                            $movimiento
                                                ->costo_total_bob,
                                            2
                                        ) }}
                                    @else
                                        <span class="text-slate-400">
                                            —
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="5"
                                    class="
                                        px-5
                                        py-10
                                        text-center
                                        text-sm
                                        text-slate-500
                                    "
                                >
                                    Todavía no hay movimientos para este almacén.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Regularizaciones recientes --}}
        @if($regularizacionesRecientes->isNotEmpty())
            <section class="
                overflow-hidden
                rounded-2xl
                border
                border-slate-200
                bg-white
                shadow-sm
            ">
                <div class="
                    border-b
                    border-slate-200
                    px-5
                    py-4
                ">
                    <h2 class="
                        font-semibold
                        text-slate-950
                    ">
                        Regularizaciones recientes
                    </h2>

                    <p class="
                        mt-1
                        text-sm
                        text-slate-500
                    ">
                        Historial de valoraciones iniciales asignadas a stock físico legacy. Estas operaciones no modifican cantidades.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="
                        min-w-full
                        divide-y
                        divide-slate-200
                    ">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="
                                    px-5
                                    py-3
                                    text-left
                                    text-xs
                                    font-semibold
                                    uppercase
                                    tracking-wider
                                    text-slate-500
                                ">
                                    Fecha
                                </th>

                                <th class="
                                    px-5
                                    py-3
                                    text-left
                                    text-xs
                                    font-semibold
                                    uppercase
                                    tracking-wider
                                    text-slate-500
                                ">
                                    Componente
                                </th>

                                <th class="
                                    px-5
                                    py-3
                                    text-right
                                    text-xs
                                    font-semibold
                                    uppercase
                                    tracking-wider
                                    text-slate-500
                                ">
                                    Stock físico
                                </th>

                                <th class="
                                    px-5
                                    py-3
                                    text-right
                                    text-xs
                                    font-semibold
                                    uppercase
                                    tracking-wider
                                    text-slate-500
                                ">
                                    Costo inicial
                                </th>

                                <th class="
                                    px-5
                                    py-3
                                    text-right
                                    text-xs
                                    font-semibold
                                    uppercase
                                    tracking-wider
                                    text-slate-500
                                ">
                                    Valor reconocido
                                </th>

                                <th class="
                                    px-5
                                    py-3
                                    text-left
                                    text-xs
                                    font-semibold
                                    uppercase
                                    tracking-wider
                                    text-slate-500
                                ">
                                    Referencia
                                </th>
                            </tr>
                        </thead>

                        <tbody class="
                            divide-y
                            divide-slate-100
                            bg-white
                        ">
                            @foreach(
                                $regularizacionesRecientes
                                as $regularizacionItem
                            )
                                <tr class="hover:bg-slate-50">
                                    <td class="
                                        whitespace-nowrap
                                        px-5
                                        py-4
                                        text-sm
                                        text-slate-600
                                    ">
                                        {{ $regularizacionItem
                                            ->fecha_regularizacion
                                            ?->format('d/m/Y H:i')
                                        }}
                                    </td>

                                    <td class="px-5 py-4">
                                        <p class="
                                            font-semibold
                                            text-slate-900
                                        ">
                                            {{ $regularizacionItem
                                                ->producto
                                                ?->nombre
                                            }}
                                        </p>

                                        <p class="
                                            mt-1
                                            text-xs
                                            text-slate-500
                                        ">
                                            {{ $regularizacionItem
                                                ->producto
                                                ?->codigo
                                            }}

                                            @if(
                                                $regularizacionItem
                                                    ->usuario
                                            )
                                                ·
                                                {{ $regularizacionItem
                                                    ->usuario
                                                    ->name
                                                }}
                                            @endif
                                        </p>
                                    </td>

                                    <td class="
                                        whitespace-nowrap
                                        px-5
                                        py-4
                                        text-right
                                        text-sm
                                        font-semibold
                                        text-slate-900
                                    ">
                                        {{ number_format(
                                            $regularizacionItem
                                                ->stock_fisico_snapshot
                                        ) }}
                                    </td>

                                    <td class="
                                        whitespace-nowrap
                                        px-5
                                        py-4
                                        text-right
                                        text-sm
                                        font-semibold
                                        text-slate-900
                                    ">
                                        Bs {{ number_format(
                                            (float)
                                            $regularizacionItem
                                                ->costo_promedio_resultante_bob,
                                            2
                                        ) }}
                                    </td>

                                    <td class="
                                        whitespace-nowrap
                                        px-5
                                        py-4
                                        text-right
                                        text-sm
                                        font-semibold
                                        text-slate-900
                                    ">
                                        Bs {{ number_format(
                                            (float)
                                            $regularizacionItem
                                                ->valor_total_bob,
                                            2
                                        ) }}
                                    </td>

                                    <td class="
                                        px-5
                                        py-4
                                        text-sm
                                        text-slate-600
                                    ">
                                        <p class="
                                            font-medium
                                            text-slate-800
                                        ">
                                            {{ $regularizacionItem
                                                ->referencia
                                            }}
                                        </p>

                                        <p
                                            class="
                                                mt-1
                                                max-w-sm
                                                text-xs
                                                text-slate-500
                                            "
                                            title="{{ $regularizacionItem->motivo }}"
                                        >
                                            {{ \Illuminate\Support\Str::limit(
                                                $regularizacionItem
                                                    ->motivo,
                                                90
                                            ) }}
                                        </p>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        {{-- Modal: regularización de valoración --}}
        @if($puedeRegularizar)
            <div
                x-cloak
                x-show="regularizacionAbierta"
                x-transition.opacity
                @keydown.escape.window="cerrarRegularizacion()"
                @click.self="cerrarRegularizacion()"
                class="
                    fixed
                    inset-0
                    z-[55]
                    flex
                    items-center
                    justify-center
                    bg-slate-950/40
                    p-4
                "
            >
                <div
                    @click.stop
                    class="
                        max-h-[92vh]
                        w-full
                        max-w-2xl
                        overflow-y-auto
                        rounded-2xl
                        border
                        border-slate-200
                        bg-white
                        shadow-xl
                    "
                >
                    <div class="
                        flex
                        items-start
                        justify-between
                        gap-4
                        border-b
                        border-slate-200
                        px-6
                        py-5
                    ">
                        <div>
                            <p class="
                                text-xs
                                font-semibold
                                uppercase
                                tracking-wider
                                text-amber-700
                            ">
                                Valoración inicial · Stock legacy
                            </p>

                            <h2 class="
                                mt-1
                                text-xl
                                font-bold
                                text-slate-950
                            ">
                                Regularizar valoración inicial
                            </h2>

                            <p class="
                                mt-1
                                text-sm
                                text-slate-500
                            ">
                                Asigna un costo al stock físico existente sin registrar una compra ni modificar cantidades.
                            </p>
                        </div>

                        <button
                            type="button"
                            @click="cerrarRegularizacion()"
                            class="
                                rounded-xl
                                border
                                border-slate-200
                                bg-white
                                px-3
                                py-2
                                text-sm
                                font-semibold
                                text-slate-600
                                transition
                                hover:bg-slate-50
                            "
                        >
                            Cerrar
                        </button>
                    </div>

                    <form
                        method="POST"
                        action="{{ route(
                            'inventario.componentes.regularizaciones.store'
                        ) }}"
                        class="p-6"
                    >
                        @csrf

                        <input
                            type="hidden"
                            name="formulario"
                            value="regularizacion"
                        >

                        <input
                            type="hidden"
                            name="producto_id"
                            :value="regularizacion.productoId"
                        >

                        <input
                            type="hidden"
                            name="almacen_id"
                            :value="regularizacion.almacenId"
                        >

                        <input
                            type="hidden"
                            name="regularizacion_nombre"
                            :value="regularizacion.nombre"
                        >

                        <input
                            type="hidden"
                            name="regularizacion_disponible"
                            :value="regularizacion.disponible"
                        >

                        <input
                            type="hidden"
                            name="regularizacion_reservado"
                            :value="regularizacion.reservado"
                        >

                        @if(
                            old('formulario') === 'regularizacion'
                            && $errors->any()
                        )
                            <div class="
                                mb-5
                                rounded-xl
                                border
                                border-rose-200
                                bg-rose-50
                                px-4
                                py-3
                                text-sm
                                text-rose-800
                            ">
                                {{ $errors->first() }}
                            </div>
                        @endif

                        <div class="
                            mb-5
                            rounded-xl
                            border
                            border-amber-200
                            bg-amber-50
                            p-4
                        ">
                            <p class="
                                text-sm
                                font-semibold
                                text-amber-950
                            ">
                                Esta operación solo reconoce valor económico.
                            </p>

                            <p class="
                                mt-1
                                text-sm
                                text-amber-800
                            ">
                                Disponible y reservado permanecerán exactamente iguales. La valoración inicial solo puede registrarse una vez para esta existencia.
                            </p>
                        </div>

                        <div class="
                            grid
                            gap-4
                            rounded-xl
                            border
                            border-slate-200
                            bg-slate-50
                            p-4
                            sm:grid-cols-3
                        ">
                            <div class="sm:col-span-3">
                                <p class="
                                    text-xs
                                    font-semibold
                                    uppercase
                                    tracking-wide
                                    text-slate-500
                                ">
                                    Componente
                                </p>

                                <p
                                    class="
                                        mt-1
                                        font-semibold
                                        text-slate-950
                                    "
                                    x-text="
                                        regularizacion.nombre
                                        || 'Componente seleccionado'
                                    "
                                ></p>
                            </div>

                            <div>
                                <p class="
                                    text-xs
                                    font-semibold
                                    uppercase
                                    tracking-wide
                                    text-slate-500
                                ">
                                    Disponible
                                </p>

                                <p
                                    class="
                                        mt-1
                                        text-lg
                                        font-bold
                                        text-slate-950
                                    "
                                    x-text="
                                        formatoMonto(
                                            regularizacion.disponible
                                        ).replace(',00', '')
                                    "
                                ></p>
                            </div>

                            <div>
                                <p class="
                                    text-xs
                                    font-semibold
                                    uppercase
                                    tracking-wide
                                    text-slate-500
                                ">
                                    Reservado
                                </p>

                                <p
                                    class="
                                        mt-1
                                        text-lg
                                        font-bold
                                        text-slate-950
                                    "
                                    x-text="
                                        formatoMonto(
                                            regularizacion.reservado
                                        ).replace(',00', '')
                                    "
                                ></p>
                            </div>

                            <div>
                                <p class="
                                    text-xs
                                    font-semibold
                                    uppercase
                                    tracking-wide
                                    text-slate-500
                                ">
                                    Stock físico
                                </p>

                                <p
                                    class="
                                        mt-1
                                        text-lg
                                        font-bold
                                        text-slate-950
                                    "
                                    x-text="
                                        formatoMonto(
                                            stockFisicoRegularizacion()
                                        ).replace(',00', '')
                                    "
                                ></p>
                            </div>
                        </div>

                        <div class="
                            mt-5
                            grid
                            gap-5
                            sm:grid-cols-2
                        ">
                            <div>
                                <label
                                    for="regularizacion_costo_unitario_bob"
                                    class="
                                        mb-1.5
                                        block
                                        text-sm
                                        font-semibold
                                        text-slate-700
                                    "
                                >
                                    Costo unitario inicial (BOB)
                                </label>

                                <div class="relative">
                                    <span class="
                                        pointer-events-none
                                        absolute
                                        inset-y-0
                                        left-0
                                        flex
                                        items-center
                                        pl-3
                                        text-sm
                                        font-semibold
                                        text-slate-500
                                    ">
                                        Bs
                                    </span>

                                    <input
                                        id="regularizacion_costo_unitario_bob"
                                        name="costo_unitario_bob"
                                        type="number"
                                        min="0.000001"
                                        step="0.000001"
                                        x-model="regularizacion.costo"
                                        required
                                        class="
                                            w-full
                                            rounded-xl
                                            border-slate-300
                                            pl-10
                                            text-sm
                                            focus:border-oneshop-primary
                                            focus:ring-oneshop-primary
                                        "
                                    >
                                </div>

                                @error('costo_unitario_bob')
                                    @if(old('formulario') === 'regularizacion')
                                        <p class="
                                            mt-1
                                            text-xs
                                            text-rose-700
                                        ">
                                            {{ $message }}
                                        </p>
                                    @endif
                                @enderror
                            </div>

                            <div class="
                                rounded-xl
                                border
                                border-blue-200
                                bg-oneshop-soft
                                p-4
                            ">
                                <p class="
                                    text-xs
                                    font-semibold
                                    uppercase
                                    tracking-wide
                                    text-slate-500
                                ">
                                    Valor reconocido
                                </p>

                                <p class="
                                    mt-2
                                    text-2xl
                                    font-black
                                    text-oneshop-dark
                                ">
                                    Bs
                                    <span
                                        x-text="
                                            formatoMonto(
                                                valorRegularizacion()
                                            )
                                        "
                                    ></span>
                                </p>

                                <p class="
                                    mt-1
                                    text-xs
                                    text-slate-500
                                ">
                                    Stock físico × costo unitario inicial
                                </p>
                            </div>

                            <div class="sm:col-span-2">
                                <label
                                    for="regularizacion_referencia"
                                    class="
                                        mb-1.5
                                        block
                                        text-sm
                                        font-semibold
                                        text-slate-700
                                    "
                                >
                                    Referencia
                                </label>

                                <input
                                    id="regularizacion_referencia"
                                    name="referencia"
                                    type="text"
                                    maxlength="120"
                                    x-model="regularizacion.referencia"
                                    required
                                    class="
                                        w-full
                                        rounded-xl
                                        border-slate-300
                                        text-sm
                                        focus:border-oneshop-primary
                                        focus:ring-oneshop-primary
                                    "
                                >

                                @error('referencia')
                                    @if(old('formulario') === 'regularizacion')
                                        <p class="
                                            mt-1
                                            text-xs
                                            text-rose-700
                                        ">
                                            {{ $message }}
                                        </p>
                                    @endif
                                @enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label
                                    for="regularizacion_motivo"
                                    class="
                                        mb-1.5
                                        block
                                        text-sm
                                        font-semibold
                                        text-slate-700
                                    "
                                >
                                    Motivo
                                </label>

                                <textarea
                                    id="regularizacion_motivo"
                                    name="motivo"
                                    rows="3"
                                    maxlength="2000"
                                    x-model="regularizacion.motivo"
                                    placeholder="Ej. Stock existente previo a la implementación del módulo de costeo."
                                    required
                                    class="
                                        w-full
                                        rounded-xl
                                        border-slate-300
                                        text-sm
                                        focus:border-oneshop-primary
                                        focus:ring-oneshop-primary
                                    "
                                ></textarea>

                                @error('motivo')
                                    @if(old('formulario') === 'regularizacion')
                                        <p class="
                                            mt-1
                                            text-xs
                                            text-rose-700
                                        ">
                                            {{ $message }}
                                        </p>
                                    @endif
                                @enderror
                            </div>
                        </div>

                        <div class="
                            mt-6
                            flex
                            flex-col-reverse
                            gap-3
                            border-t
                            border-slate-200
                            pt-5
                            sm:flex-row
                            sm:justify-end
                        ">
                            <button
                                type="button"
                                @click="cerrarRegularizacion()"
                                class="
                                    rounded-xl
                                    border
                                    border-slate-300
                                    bg-white
                                    px-4
                                    py-2.5
                                    text-sm
                                    font-semibold
                                    text-slate-600
                                    transition
                                    hover:bg-slate-50
                                "
                            >
                                Cancelar
                            </button>

                            <button
                                type="submit"
                                class="
                                    rounded-xl
                                    border
                                    border-oneshop-primary
                                    bg-oneshop-light
                                    px-5
                                    py-2.5
                                    text-sm
                                    font-semibold
                                    text-oneshop-dark
                                    transition
                                    hover:bg-blue-100
                                "
                            >
                                Registrar valoración
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        {{-- Modal: registrar compra --}}
        <div
            x-cloak
            x-show="compraAbierta"
            x-transition.opacity
            @keydown.escape.window="
                if (!componenteAbierto) {
                    cerrarCompra()
                }
            "
            @click.self="
                if (!componenteAbierto) {
                    cerrarCompra()
                }
            "
            class="
                fixed
                inset-0
                z-50
                flex
                items-center
                justify-center
                bg-slate-950/40
                p-4
            "
        >
            <div
                @click.stop
                class="
                    max-h-[92vh]
                    w-full
                    max-w-4xl
                    overflow-y-auto
                    rounded-2xl
                    border
                    border-slate-200
                    bg-white
                    shadow-xl
                "
            >
                <div class="
                    flex
                    items-start
                    justify-between
                    gap-4
                    border-b
                    border-slate-200
                    px-6
                    py-5
                ">
                    <div>
                        <p class="
                            text-xs
                            font-semibold
                            uppercase
                            tracking-wider
                            text-oneshop-primary
                        ">
                            Paso 2 de 2 · Entrada valorizada
                        </p>

                        <h2 class="
                            mt-1
                            text-xl
                            font-bold
                            text-slate-950
                        ">
                            Registrar compra para stock
                        </h2>

                        <p class="
                            mt-1
                            text-sm
                            text-slate-500
                        ">
                            Registra una compra real y actualiza automáticamente existencia y costo promedio.
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="cerrarCompra()"
                        class="
                            rounded-xl
                            border
                            border-slate-200
                            bg-white
                            px-3
                            py-2
                            text-sm
                            font-semibold
                            text-slate-600
                            transition
                            hover:bg-slate-50
                        "
                    >
                        Cerrar
                    </button>
                </div>

                <form
                    method="POST"
                    action="{{ route(
                        'inventario.componentes.compras.store'
                    ) }}"
                    class="p-6"
                >
                    @csrf

                    @if(
                        session('success')
                        && request()->boolean('compra')
                    )
                        <div class="
                            mb-5
                            rounded-xl
                            border
                            border-emerald-200
                            bg-emerald-50
                            px-4
                            py-3
                            text-sm
                            text-emerald-800
                        ">
                            <p class="font-semibold">
                                Componente creado
                            </p>

                            <p class="mt-1">
                                {{ session('success') }}
                            </p>
                        </div>
                    @endif

                    @if(
                        $errors->has(
                            'compra_componente'
                        )
                    )
                        <div class="
                            mb-5
                            rounded-xl
                            border
                            border-rose-200
                            bg-rose-50
                            px-4
                            py-3
                            text-sm
                            text-rose-800
                        ">
                            {{ $errors->first(
                                'compra_componente'
                            ) }}
                        </div>
                    @endif

                    <div class="
                        grid
                        gap-6
                        lg:grid-cols-12
                    ">
                        <div class="
                            space-y-5
                            lg:col-span-7
                        ">
                            <section class="
                                rounded-2xl
                                border
                                border-slate-200
                                bg-slate-50/60
                                p-5
                            ">
                                <div class="
                                    mb-4
                                    flex
                                    items-start
                                    justify-between
                                    gap-4
                                ">
                                    <div>
                                        <h3 class="
                                            font-semibold
                                            text-slate-950
                                        ">
                                            Componente y destino
                                        </h3>

                                        <p class="
                                            mt-1
                                            text-sm
                                            text-slate-500
                                        ">
                                            Selecciona qué compraste y dónde ingresará físicamente.
                                        </p>
                                    </div>

                                    <button
                                        type="button"
                                        @click="abrirComponente()"
                                        class="
                                            shrink-0
                                            rounded-lg
                                            border
                                            border-oneshop-primary
                                            bg-white
                                            px-3
                                            py-2
                                            text-sm
                                            font-semibold
                                            text-oneshop-dark
                                            transition
                                            hover:bg-oneshop-light
                                        "
                                    >
                                        + Nuevo componente
                                    </button>
                                </div>

                                <div class="space-y-4">
                                    <div>
                                        <label
                                            for="producto_id"
                                            class="
                                                mb-1.5
                                                block
                                                text-sm
                                                font-semibold
                                                text-slate-700
                                            "
                                        >
                                            Componente
                                        </label>

                                        <select
                                            id="producto_id"
                                            name="producto_id"
                                            required
                                            class="
                                                w-full
                                                rounded-xl
                                                border-slate-300
                                                bg-white
                                                text-sm
                                                focus:border-oneshop-primary
                                                focus:ring-oneshop-primary
                                            "
                                        >
                                            <option value="">
                                                Seleccionar componente
                                            </option>

                                            @foreach(
                                                $productosCompra
                                                as $producto
                                            )
                                                <option
                                                    value="{{ $producto->id }}"
                                                    @selected(
                                                        (int) old(
                                                            'producto_id',
                                                            request()->integer(
                                                                'producto_id'
                                                            )
                                                        )
                                                        === $producto->id
                                                    )
                                                >
                                                    {{ $producto->nombre }}

                                                    @if(
                                                        $producto
                                                            ->marca
                                                            ?->nombre
                                                    )
                                                        · {{ $producto
                                                            ->marca
                                                            ->nombre
                                                        }}
                                                    @endif

                                                    @if($producto->modelo)
                                                        · {{ $producto->modelo }}
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>

                                        @if($productosCompra->isEmpty())
                                            <div class="
                                                mt-2
                                                rounded-lg
                                                border
                                                border-amber-200
                                                bg-amber-50
                                                px-3
                                                py-2
                                                text-xs
                                                text-amber-800
                                            ">
                                                No hay componentes en el catálogo. Usa “Nuevo componente” para registrar el primero.
                                            </div>
                                        @endif

                                        @error('producto_id')
                                            <p class="
                                                mt-1
                                                text-xs
                                                text-rose-700
                                            ">
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>

                                    <div class="
                                        grid
                                        gap-4
                                        sm:grid-cols-2
                                    ">
                                        <div>
                                            <label
                                                for="almacen_id"
                                                class="
                                                    mb-1.5
                                                    block
                                                    text-sm
                                                    font-semibold
                                                    text-slate-700
                                                "
                                            >
                                                Almacén
                                            </label>

                                            <select
                                                id="almacen_id"
                                                name="almacen_id"
                                                required
                                                class="
                                                    w-full
                                                    rounded-xl
                                                    border-slate-300
                                                    bg-white
                                                    text-sm
                                                    focus:border-oneshop-primary
                                                    focus:ring-oneshop-primary
                                                "
                                            >
                                                @foreach(
                                                    $almacenesCompra
                                                    as $almacen
                                                )
                                                    <option
                                                        value="{{ $almacen->id }}"
                                                        @selected(
                                                            (int) old(
                                                                'almacen_id',
                                                                $almacenSeleccionado->id
                                                            )
                                                            === $almacen->id
                                                        )
                                                    >
                                                        {{ $almacen->nombre }}
                                                        · {{ $almacen->ciudad }}
                                                    </option>
                                                @endforeach
                                            </select>

                                            @error('almacen_id')
                                                <p class="
                                                    mt-1
                                                    text-xs
                                                    text-rose-700
                                                ">
                                                    {{ $message }}
                                                </p>
                                            @enderror
                                        </div>

                                        <div>
                                            <label
                                                for="cantidad"
                                                class="
                                                    mb-1.5
                                                    block
                                                    text-sm
                                                    font-semibold
                                                    text-slate-700
                                                "
                                            >
                                                Cantidad
                                            </label>

                                            <input
                                                id="cantidad"
                                                name="cantidad"
                                                type="number"
                                                min="1"
                                                step="1"
                                                x-model.number="cantidadCompra"
                                                required
                                                class="
                                                    w-full
                                                    rounded-xl
                                                    border-slate-300
                                                    bg-white
                                                    text-sm
                                                    focus:border-oneshop-primary
                                                    focus:ring-oneshop-primary
                                                "
                                            >

                                            @error('cantidad')
                                                <p class="
                                                    mt-1
                                                    text-xs
                                                    text-rose-700
                                                ">
                                                    {{ $message }}
                                                </p>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section class="
                                rounded-2xl
                                border
                                border-slate-200
                                p-5
                            ">
                                <div>
                                    <h3 class="
                                        font-semibold
                                        text-slate-950
                                    ">
                                        Compra y moneda
                                    </h3>

                                    <p class="
                                        mt-1
                                        text-sm
                                        text-slate-500
                                    ">
                                        El costo total corresponde a toda la cantidad comprada.
                                    </p>
                                </div>

                                <div class="
                                    mt-4
                                    grid
                                    gap-4
                                    sm:grid-cols-2
                                ">
                                    <div>
                                        <label
                                            for="moneda_id"
                                            class="
                                                mb-1.5
                                                block
                                                text-sm
                                                font-semibold
                                                text-slate-700
                                            "
                                        >
                                            Moneda
                                        </label>

                                        <select
                                            id="moneda_id"
                                            name="moneda_id"
                                            required
                                            @change="
                                                cambiarMoneda(
                                                    $event
                                                        .target
                                                        .selectedOptions[0]
                                                        .dataset
                                                        .codigo
                                                )
                                            "
                                            class="
                                                w-full
                                                rounded-xl
                                                border-slate-300
                                                text-sm
                                                focus:border-oneshop-primary
                                                focus:ring-oneshop-primary
                                            "
                                        >
                                            @foreach($monedas as $moneda)
                                                <option
                                                    value="{{ $moneda->id }}"
                                                    data-codigo="{{ $moneda->codigo }}"
                                                    @selected(
                                                        (int) old(
                                                            'moneda_id',
                                                            $monedas
                                                                ->firstWhere(
                                                                    'codigo',
                                                                    'BOB'
                                                                )
                                                                ?->id
                                                        )
                                                        === $moneda->id
                                                    )
                                                >
                                                    {{ $moneda->codigo }}
                                                </option>
                                            @endforeach
                                        </select>

                                        @error('moneda_id')
                                            <p class="
                                                mt-1
                                                text-xs
                                                text-rose-700
                                            ">
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label
                                            for="monto_total_origen"
                                            class="
                                                mb-1.5
                                                block
                                                text-sm
                                                font-semibold
                                                text-slate-700
                                            "
                                        >
                                            Costo total
                                        </label>

                                        <input
                                            id="monto_total_origen"
                                            name="monto_total_origen"
                                            type="number"
                                            min="0.01"
                                            step="0.01"
                                            x-model.number="montoTotalOrigen"
                                            required
                                            class="
                                                w-full
                                                rounded-xl
                                                border-slate-300
                                                text-sm
                                                focus:border-oneshop-primary
                                                focus:ring-oneshop-primary
                                            "
                                        >

                                        @error('monto_total_origen')
                                            <p class="
                                                mt-1
                                                text-xs
                                                text-rose-700
                                            ">
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>

                                    <div
                                        x-show="
                                            monedaCodigo !== 'BOB'
                                        "
                                        x-cloak
                                        class="sm:col-span-2"
                                    >
                                        <label
                                            for="tipo_cambio_aplicado"
                                            class="
                                                mb-1.5
                                                block
                                                text-sm
                                                font-semibold
                                                text-slate-700
                                            "
                                        >
                                            Tipo de cambio aplicado
                                        </label>

                                        <input
                                            id="tipo_cambio_aplicado"
                                            name="tipo_cambio_aplicado"
                                            type="number"
                                            min="0.000001"
                                            step="0.000001"
                                            x-model.number="tipoCambioCompra"
                                            :required="
                                                monedaCodigo !== 'BOB'
                                            "
                                            :disabled="
                                                monedaCodigo === 'BOB'
                                            "
                                            class="
                                                w-full
                                                rounded-xl
                                                border-slate-300
                                                text-sm
                                                focus:border-oneshop-primary
                                                focus:ring-oneshop-primary
                                            "
                                        >

                                        <p class="
                                            mt-1
                                            text-xs
                                            text-slate-500
                                        ">
                                            Usa el tipo de cambio realmente aplicado en esta operación.
                                        </p>

                                        @error('tipo_cambio_aplicado')
                                            <p class="
                                                mt-1
                                                text-xs
                                                text-rose-700
                                            ">
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>
                                </div>
                            </section>

                            <section class="
                                rounded-2xl
                                border
                                border-slate-200
                                p-5
                            ">
                                <h3 class="
                                    font-semibold
                                    text-slate-950
                                ">
                                    Trazabilidad
                                </h3>

                                <div class="
                                    mt-4
                                    grid
                                    gap-4
                                    sm:grid-cols-2
                                ">
                                    <div>
                                        <label
                                            for="fecha_compra"
                                            class="
                                                mb-1.5
                                                block
                                                text-sm
                                                font-semibold
                                                text-slate-700
                                            "
                                        >
                                            Fecha de compra
                                        </label>

                                        <input
                                            id="fecha_compra"
                                            name="fecha_compra"
                                            type="datetime-local"
                                            value="{{ old(
                                                'fecha_compra',
                                                now()->format(
                                                    'Y-m-d\TH:i'
                                                )
                                            ) }}"
                                            class="
                                                w-full
                                                rounded-xl
                                                border-slate-300
                                                text-sm
                                                focus:border-oneshop-primary
                                                focus:ring-oneshop-primary
                                            "
                                        >

                                        @error('fecha_compra')
                                            <p class="
                                                mt-1
                                                text-xs
                                                text-rose-700
                                            ">
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label
                                            for="referencia"
                                            class="
                                                mb-1.5
                                                block
                                                text-sm
                                                font-semibold
                                                text-slate-700
                                            "
                                        >
                                            Referencia
                                        </label>

                                        <input
                                            id="referencia"
                                            name="referencia"
                                            type="text"
                                            maxlength="150"
                                            value="{{ old(
                                                'referencia'
                                            ) }}"
                                            placeholder="Factura, recibo, proveedor..."
                                            class="
                                                w-full
                                                rounded-xl
                                                border-slate-300
                                                text-sm
                                                focus:border-oneshop-primary
                                                focus:ring-oneshop-primary
                                            "
                                        >

                                        @error('referencia')
                                            <p class="
                                                mt-1
                                                text-xs
                                                text-rose-700
                                            ">
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>

                                    <div class="sm:col-span-2">
                                        <label
                                            for="observacion"
                                            class="
                                                mb-1.5
                                                block
                                                text-sm
                                                font-semibold
                                                text-slate-700
                                            "
                                        >
                                            Observación
                                        </label>

                                        <textarea
                                            id="observacion"
                                            name="observacion"
                                            rows="3"
                                            class="
                                                w-full
                                                rounded-xl
                                                border-slate-300
                                                text-sm
                                                focus:border-oneshop-primary
                                                focus:ring-oneshop-primary
                                            "
                                        >{{ old('observacion') }}</textarea>

                                        @error('observacion')
                                            <p class="
                                                mt-1
                                                text-xs
                                                text-rose-700
                                            ">
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>
                                </div>
                            </section>
                        </div>

                        <aside class="
                            lg:col-span-5
                        ">
                            <div class="
                                sticky
                                top-0
                                space-y-4
                            ">
                                <div class="
                                    rounded-2xl
                                    border
                                    border-blue-200
                                    bg-oneshop-soft
                                    p-5
                                ">
                                    <p class="
                                        text-xs
                                        font-semibold
                                        uppercase
                                        tracking-wider
                                        text-oneshop-primary
                                    ">
                                        Vista previa del costo
                                    </p>

                                    <div class="
                                        mt-4
                                        space-y-3
                                    ">
                                        <div class="
                                            flex
                                            items-center
                                            justify-between
                                            gap-4
                                        ">
                                            <span class="text-sm text-slate-500">
                                                Cantidad
                                            </span>

                                            <strong
                                                class="text-slate-950"
                                                x-text="
                                                    cantidadCompra || 0
                                                "
                                            ></strong>
                                        </div>

                                        <div class="
                                            flex
                                            items-center
                                            justify-between
                                            gap-4
                                        ">
                                            <span class="text-sm text-slate-500">
                                                Costo unitario origen
                                            </span>

                                            <strong class="text-slate-950">
                                                <span
                                                    x-text="monedaCodigo"
                                                ></span>

                                                <span
                                                    x-text="
                                                        formatoMonto(
                                                            costoUnitarioOrigen()
                                                        )
                                                    "
                                                ></span>
                                            </strong>
                                        </div>

                                        <div
                                            x-show="
                                                monedaCodigo !== 'BOB'
                                            "
                                            class="
                                                flex
                                                items-center
                                                justify-between
                                                gap-4
                                            "
                                        >
                                            <span class="text-sm text-slate-500">
                                                Tipo de cambio
                                            </span>

                                            <strong
                                                class="text-slate-950"
                                                x-text="
                                                    tipoCambioCompra
                                                    || '—'
                                                "
                                            ></strong>
                                        </div>

                                        <div class="
                                            border-t
                                            border-blue-200
                                            pt-3
                                        ">
                                            <div class="
                                                flex
                                                items-end
                                                justify-between
                                                gap-4
                                            ">
                                                <span class="
                                                    text-sm
                                                    font-medium
                                                    text-slate-600
                                                ">
                                                    Total en BOB
                                                </span>

                                                <strong class="
                                                    text-xl
                                                    text-slate-950
                                                ">
                                                    Bs
                                                    <span
                                                        x-text="
                                                            formatoMonto(
                                                                totalBob()
                                                            )
                                                        "
                                                    ></span>
                                                </strong>
                                            </div>

                                            <div class="
                                                mt-2
                                                flex
                                                items-center
                                                justify-between
                                                gap-4
                                            ">
                                                <span class="text-sm text-slate-500">
                                                    Costo unitario BOB
                                                </span>

                                                <strong class="text-slate-950">
                                                    Bs
                                                    <span
                                                        x-text="
                                                            formatoMonto(
                                                                costoUnitarioBob()
                                                            )
                                                        "
                                                    ></span>
                                                </strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="
                                    rounded-xl
                                    border
                                    border-slate-200
                                    bg-white
                                    p-4
                                    text-sm
                                    text-slate-600
                                ">
                                    <p class="
                                        font-semibold
                                        text-slate-800
                                    ">
                                        Qué ocurrirá al guardar
                                    </p>

                                    <p class="mt-2 leading-6">
                                        Se incrementará el disponible del almacén y se recalculará el costo promedio ponderado. La compra quedará registrada como movimiento de inventario.
                                    </p>
                                </div>
                            </div>
                        </aside>
                    </div>

                    <div class="
                        mt-6
                        flex
                        flex-col-reverse
                        gap-3
                        border-t
                        border-slate-200
                        pt-5
                        sm:flex-row
                        sm:justify-end
                    ">
                        <button
                            type="button"
                            @click="cerrarCompra()"
                            class="
                                rounded-xl
                                border
                                border-slate-300
                                px-4
                                py-2.5
                                text-sm
                                font-semibold
                                text-slate-600
                                transition
                                hover:bg-slate-50
                            "
                        >
                            Cancelar
                        </button>

                        <button
                            type="submit"
                            class="
                                rounded-xl
                                border
                                border-oneshop-primary
                                bg-oneshop-light
                                px-5
                                py-2.5
                                text-sm
                                font-semibold
                                text-oneshop-dark
                                transition
                                hover:bg-blue-100
                            "
                        >
                            Registrar compra
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal: registrar componente --}}
        <div
            x-cloak
            x-show="componenteAbierto"
            x-transition.opacity
            @keydown.escape.window="
                if (componenteAbierto) {
                    cerrarComponente()
                }
            "
            @click.self="cerrarComponente()"
            class="
                fixed
                inset-0
                z-[60]
                flex
                items-center
                justify-center
                bg-slate-950/30
                p-4
            "
        >
            <div
                @click.stop
                class="
                    max-h-[90vh]
                    w-full
                    max-w-2xl
                    overflow-y-auto
                    rounded-2xl
                    border
                    border-slate-200
                    bg-white
                    shadow-xl
                "
            >
                <div class="
                    flex
                    items-start
                    justify-between
                    gap-4
                    border-b
                    border-slate-200
                    px-6
                    py-5
                ">
                    <div>
                        <p class="
                            text-xs
                            font-semibold
                            uppercase
                            tracking-wider
                            text-oneshop-primary
                        ">
                            Paso 1 de 2 · Catálogo
                        </p>

                        <h2 class="
                            mt-1
                            text-xl
                            font-bold
                            text-slate-950
                        ">
                            Nuevo componente
                        </h2>

                        <p class="
                            mt-1
                            text-sm
                            text-slate-500
                        ">
                            Primero crea el artículo. El stock se registrará después mediante la compra.
                        </p>
                    </div>

                    <button
                        type="button"
                        @click.stop="cerrarComponente()"
                        class="
                            rounded-xl
                            border
                            border-slate-200
                            px-3
                            py-2
                            text-sm
                            font-semibold
                            text-slate-600
                            hover:bg-slate-50
                        "
                    >
                        Volver
                    </button>
                </div>

                <form
                    method="POST"
                    action="{{ route(
                        'inventario.componentes.catalogo.store'
                    ) }}"
                    class="p-6"
                >
                    @csrf

                    <input
                        type="hidden"
                        name="almacen_contexto"
                        value="{{ $almacenSeleccionado->id }}"
                    >

                    <div class="
                        mb-5
                        rounded-xl
                        border
                        border-blue-200
                        bg-oneshop-soft
                        px-4
                        py-3
                        text-sm
                        text-slate-700
                    ">
                        <strong class="text-slate-900">
                            Este paso no modifica existencias.
                        </strong>

                        Solo crea el artículo del catálogo. Al guardarlo volverás automáticamente a la compra con este componente seleccionado.
                    </div>

                    <div class="
                        grid
                        gap-5
                        md:grid-cols-2
                    ">
                        <div class="
                            md:col-span-2
                            rounded-xl
                            border
                            border-blue-200
                            bg-oneshop-soft
                            px-4
                            py-3
                        ">
                            <div class="
                                flex
                                flex-col
                                gap-1
                                sm:flex-row
                                sm:items-center
                                sm:justify-between
                            ">
                                <div>
                                    <p class="
                                        text-sm
                                        font-semibold
                                        text-slate-800
                                    ">
                                        Código automático
                                    </p>

                                    <p class="
                                        mt-0.5
                                        text-xs
                                        text-slate-500
                                    ">
                                        No necesitas inventarlo; OneShop genera un correlativo único.
                                    </p>
                                </div>

                                <code
                                    class="
                                        mt-2
                                        rounded-lg
                                        border
                                        border-blue-200
                                        bg-white
                                        px-3
                                        py-2
                                        text-xs
                                        font-semibold
                                        text-oneshop-dark
                                        sm:mt-0
                                    "
                                    x-text="codigoComponentePreview()"
                                ></code>
                            </div>
                        </div>

                        <div>
                            <label
                                for="nombre"
                                class="
                                    mb-1.5
                                    block
                                    text-sm
                                    font-semibold
                                    text-slate-700
                                "
                            >
                                Nombre
                            </label>

                            <input
                                id="nombre"
                                name="nombre"
                                type="text"
                                maxlength="180"
                                value="{{ old('nombre') }}"
                                x-model="nombreComponente"
                                placeholder="Ej. Cargador Dell 65W - punta aguja"
                                required
                                class="
                                    w-full
                                    rounded-xl
                                    border-slate-300
                                    text-sm
                                    focus:border-oneshop-primary
                                    focus:ring-oneshop-primary
                                "
                            >

                            @error('nombre')
                                <p class="
                                    mt-1
                                    text-xs
                                    text-rose-700
                                ">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label
                                for="categoria_producto_id"
                                class="
                                    mb-1.5
                                    block
                                    text-sm
                                    font-semibold
                                    text-slate-700
                                "
                            >
                                Categoría
                            </label>

                            <select
                                id="categoria_producto_id"
                                name="categoria_producto_id"
                                required
                                class="
                                    w-full
                                    rounded-xl
                                    border-slate-300
                                    text-sm
                                    focus:border-oneshop-primary
                                    focus:ring-oneshop-primary
                                "
                            >
                                <option value="">
                                    Seleccionar categoría
                                </option>

                                @foreach($categorias as $categoria)
                                    <option
                                        value="{{ $categoria->id }}"
                                        @selected(
                                            (int) old(
                                                'categoria_producto_id',
                                                $categoriaComponenteId
                                            )
                                            === $categoria->id
                                        )
                                    >
                                        {{ $categoria->nombre }}
                                    </option>
                                @endforeach
                            </select>

                            @error('categoria_producto_id')
                                <p class="
                                    mt-1
                                    text-xs
                                    text-rose-700
                                ">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label
                                for="marca_id"
                                class="
                                    mb-1.5
                                    block
                                    text-sm
                                    font-semibold
                                    text-slate-700
                                "
                            >
                                Marca
                            </label>

                            <select
                                id="marca_id"
                                name="marca_id"
                                class="
                                    w-full
                                    rounded-xl
                                    border-slate-300
                                    text-sm
                                    focus:border-oneshop-primary
                                    focus:ring-oneshop-primary
                                "
                            >
                                <option value="">
                                    Sin marca / genérico
                                </option>

                                @foreach($marcas as $marca)
                                    <option
                                        value="{{ $marca->id }}"
                                        @selected(
                                            (int) old(
                                                'marca_id'
                                            )
                                            === $marca->id
                                        )
                                    >
                                        {{ $marca->nombre }}
                                    </option>
                                @endforeach
                            </select>

                            @error('marca_id')
                                <p class="
                                    mt-1
                                    text-xs
                                    text-rose-700
                                ">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label
                                for="modelo"
                                class="
                                    mb-1.5
                                    block
                                    text-sm
                                    font-semibold
                                    text-slate-700
                                "
                            >
                                Modelo
                            </label>

                            <input
                                id="modelo"
                                name="modelo"
                                type="text"
                                maxlength="150"
                                value="{{ old('modelo') }}"
                                x-model="modeloComponente"
                                placeholder="Opcional, ej. 65W"
                                class="
                                    w-full
                                    rounded-xl
                                    border-slate-300
                                    text-sm
                                    focus:border-oneshop-primary
                                    focus:ring-oneshop-primary
                                "
                            >

                            @error('modelo')
                                <p class="
                                    mt-1
                                    text-xs
                                    text-rose-700
                                ">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label
                                for="descripcion"
                                class="
                                    mb-1.5
                                    block
                                    text-sm
                                    font-semibold
                                    text-slate-700
                                "
                            >
                                Descripción
                            </label>

                            <textarea
                                id="descripcion"
                                name="descripcion"
                                rows="3"
                                placeholder="Características útiles para identificar el componente."
                                class="
                                    w-full
                                    rounded-xl
                                    border-slate-300
                                    text-sm
                                    focus:border-oneshop-primary
                                    focus:ring-oneshop-primary
                                "
                            >{{ old('descripcion') }}</textarea>

                            @error('descripcion')
                                <p class="
                                    mt-1
                                    text-xs
                                    text-rose-700
                                ">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </div>

                    <div class="
                        mt-6
                        flex
                        flex-col-reverse
                        gap-3
                        border-t
                        border-slate-200
                        pt-5
                        sm:flex-row
                        sm:justify-end
                    ">
                        <button
                            type="button"
                            @click.stop="cerrarComponente()"
                            class="
                                rounded-xl
                                border
                                border-slate-300
                                px-4
                                py-2.5
                                text-sm
                                font-semibold
                                text-slate-600
                                transition
                                hover:bg-slate-50
                            "
                        >
                            Volver a compra
                        </button>

                        <button
                            type="submit"
                            class="
                                rounded-xl
                                border
                                border-oneshop-primary
                                bg-oneshop-light
                                px-5
                                py-2.5
                                text-sm
                                font-semibold
                                text-oneshop-dark
                                transition
                                hover:bg-blue-100
                            "
                        >
                            Guardar y continuar compra
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.oneshop>
