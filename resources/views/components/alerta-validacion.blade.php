<div 
    id="alertaValidacion"
    class="fixed hidden top-6 right-6 z-[999999]">

    <div 
        class="w-80 rounded-2xl bg-white p-5 shadow-2xl border border-red-200">

        <div class="flex items-center gap-3">

            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100">

                <i data-lucide="triangle-alert"
                   class="h-6 w-6 text-red-600"></i>

            </div>


            <div>

                <h3 class="font-semibold text-slate-900">
                    Campo requerido
                </h3>


                <p id="mensajeAlertaValidacion"
                   class="text-sm text-slate-600 mt-1">
                </p>

            </div>

        </div>


        <div class="mt-4 flex justify-end">

            <button
            onclick="cerrarAlertaValidacion()"
            class="rounded-xl bg-slate-900 px-5 py-2 text-sm text-white">

                Entendido

            </button>

        </div>


    </div>

</div>