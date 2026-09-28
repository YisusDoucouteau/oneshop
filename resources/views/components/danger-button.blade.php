<button
    {{
        $attributes->merge([
            'type' => 'submit',

            'class' => '
                inline-flex
                items-center
                justify-center
                gap-2
                rounded-xl
                border
                border-red-300
                bg-red-50
                px-4
                py-2.5
                text-sm
                font-semibold
                text-red-800
                transition
                hover:bg-red-100
                focus:outline-none
                focus:ring-4
                focus:ring-red-100
                disabled:cursor-not-allowed
                disabled:opacity-60
            ',
        ])
    }}
>
    {{ $slot }}
</button>