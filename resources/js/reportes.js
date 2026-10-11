import DataTable from 'datatables.net-dt';
import 'datatables.net-dt/css';
import 'datatables.net-responsive-dt';
import 'datatables.net-responsive-dt/css';
import '../css/reportes-datatables.css';

const idioma = {
    search: 'Buscar',
    searchPlaceholder: 'Escribe para filtrar…',
    lengthMenu: 'Mostrar _MENU_',
    info: '_START_–_END_ de _TOTAL_',
    infoEmpty: 'Sin registros',
    infoFiltered: '(filtrado de _MAX_)',
    zeroRecords: 'No se encontraron registros con ese criterio.',
    emptyTable: 'No hay información disponible.',
    loadingRecords: 'Cargando…',
    processing: 'Procesando…',
    paginate: {
        first: 'Primera',
        last: 'Última',
        next: 'Siguiente',
        previous: 'Anterior',
    },
};

const inicializarTabla = (tabla) => {
    if (tabla.dataset.datatableReady === '1') {
        return;
    }

    const indiceOrden = Number.parseInt(tabla.dataset.orderColumn ?? '0', 10);
    const direccion = tabla.dataset.orderDirection === 'asc' ? 'asc' : 'desc';

    new DataTable(tabla, {
        responsive: true,
        autoWidth: false,
        pageLength: Number.parseInt(tabla.dataset.pageLength ?? '25', 10),
        lengthMenu: [10, 25, 50, 100],
        order: [[indiceOrden, direccion]],
        stateSave: false,
        language: idioma,
    });

    tabla.dataset.datatableReady = '1';
};

const iniciar = () => {
    document
        .querySelectorAll('table[data-oneshop-datatable]')
        .forEach(inicializarTabla);
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', iniciar);
} else {
    iniciar();
}
