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
                border-oneshop-primary
                bg-oneshop-light
                px-4
                py-2.5
                text-sm
                font-semibold
                text-oneshop-dark
                transition
                hover:bg-blue-100
                focus:outline-none
                focus:ring-4
                focus:ring-blue-100
                disabled:cursor-not-allowed
                disabled:opacity-60
            ',
        ])
    }}
>
    {{ $slot }}
</button>