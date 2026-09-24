<?php

return [
    'boleta' => [
        'lugar' =>
            env(
                'ONESHOP_BOLETA_LUGAR',
                'ORURO'
            ),

        'telefonos' => [
            'Oruro: 78601531 - 69590686',
            'Sucre: 74500139',
            'Potosí: 71834240',
            'Envíos a nivel nacional: 62424764',
        ],

        'correo' =>
            env(
                'ONESHOP_BOLETA_CORREO',
                'Oneshop.oruro@gmail.com'
            ),

        'red_social' =>
            env(
                'ONESHOP_BOLETA_RED',
                'Oneshop'
            ),
    ],
];
