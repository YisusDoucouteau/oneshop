<div
    id="modalRegistrarEquipo"
    class="fixed inset-0 z-50 hidden bg-black/50"
>


    <div class="flex min-h-screen items-center justify-center p-4">


        <div class="w-full max-w-2xl rounded-2xl bg-white shadow-xl">


            {{-- HEADER --}}

            <div class="flex items-center justify-between border-b px-6 py-4">


                <h2 class="text-lg font-semibold text-slate-900">
                    Registrar equipo recibido
                </h2>


                <button
                    type="button"
                    onclick="cerrarModalEquipo()"
                    class="text-xl text-slate-500 hover:text-slate-900"
                >
                    ✕
                </button>


            </div>



            <form
                method="POST"
                action="{{ route('importaciones.unidades.store',$lote) }}"
                class="space-y-5 p-6"
            >

                @csrf



                {{-- PRODUCTO --}}

                <div>

                    <label class="text-sm font-medium text-slate-700">
                        Producto del lote
                    </label>


                    <select
                        name="detalle_lote_id"
                        class="mt-1 w-full rounded-xl border border-slate-300 p-3"
                        required
                    >


                        <option value="">
                            Seleccione producto
                        </option>


                        @foreach($lote->detalles as $detalle)


                            <option value="{{ $detalle->id }}">


                                {{ $detalle->producto?->marca?->nombre }}

                                {{ $detalle->producto?->nombre }}

                                {{ $detalle->producto?->modelo }}


                            </option>


                        @endforeach


                    </select>


                </div>




                {{-- CANTIDAD --}}

                <div>


                    <label class="text-sm font-medium text-slate-700">
                        Cantidad recibida
                    </label>


                    <input

                        type="number"

                        name="cantidad"

                        value="1"

                        min="1"

                        class="mt-1 w-full rounded-xl border border-slate-300 p-3"

                    >


                </div>





                {{-- DATOS DEL EQUIPO --}}


                <div>


                    <h3 class="mb-3 font-semibold text-slate-900">
                        Datos conocidos del equipo
                    </h3>



                    <div class="grid grid-cols-2 gap-4">


                        <input

                            name="procesador"

                            placeholder="Procesador"

                            class="rounded-xl border border-slate-300 p-3"

                        >



                        <input

                            name="generacion_procesador"

                            placeholder="Generación"

                            class="rounded-xl border border-slate-300 p-3"

                        >




                        <input

                            type="number"

                            name="ram_gb"

                            placeholder="RAM GB"

                            class="rounded-xl border border-slate-300 p-3"

                        >




                        <input

                            type="number"

                            name="almacenamiento_gb"

                            placeholder="Disco GB"

                            class="rounded-xl border border-slate-300 p-3"

                        >



                    </div>


                </div>






                <div>


                    <textarea

                        name="observacion"

                        placeholder="Observación de recepción"

                        class="w-full rounded-xl border border-slate-300 p-3"

                    ></textarea>


                </div>






                <div class="flex justify-end gap-3">


                    <button

                        type="button"

                        onclick="cerrarModalEquipo()"

                        class="rounded-xl border px-5 py-2"

                    >

                        Cancelar

                    </button>





                    <button

                        type="submit"

                        class="rounded-xl bg-slate-950 px-5 py-2 text-white"

                    >

                        Guardar equipo

                    </button>


                </div>



            </form>



        </div>


    </div>


</div>





@push('scripts')

<script>


function abrirModalEquipo(){

    document
        .getElementById('modalRegistrarEquipo')
        .classList
        .remove('hidden');

}



function cerrarModalEquipo(){

    document
        .getElementById('modalRegistrarEquipo')
        .classList
        .add('hidden');

}


</script>

@endpush