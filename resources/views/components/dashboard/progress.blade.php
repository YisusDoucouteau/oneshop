<div class="space-y-2">

    <div class="flex items-center justify-between">

        <span class="text-sm font-medium text-slate-700">
            {{ $label }}
        </span>


        <span class="text-sm text-slate-500">
            {{ $value }}
        </span>

    </div>


    <div class="h-2.5 overflow-hidden rounded-full bg-slate-100">

        <div
            class="h-full rounded-full bg-blue-600 transition-all duration-500"
            style="width: {{ $percentage }}%"
        ></div>

    </div>


    <p class="text-xs text-slate-400">
        {{ $percentage }}% del inventario
    </p>

</div>