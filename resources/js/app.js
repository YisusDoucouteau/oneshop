import './bootstrap';

import Alpine from 'alpinejs';

import { createIcons } from 'lucide';


window.Alpine = Alpine;


const csrfToken =
    document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content');


if (csrfToken && window.axios) {

    window.axios.defaults.headers.common[
        'X-CSRF-TOKEN'
    ] = csrfToken;

}


Alpine.start();


createIcons();