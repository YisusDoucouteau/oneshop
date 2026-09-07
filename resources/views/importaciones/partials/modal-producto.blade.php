<div
    id="modalProducto"
    class="fixed inset-0 z-50 hidden bg-black/50"
>


<div class="flex min-h-screen items-center justify-center p-4">


<div class="w-full max-w-xl rounded-2xl bg-white shadow-xl">



<div class="flex items-center justify-between border-b px-6 py-4">


<h2 class="font-semibold text-slate-900">
Crear producto rápido
</h2>


<button
type="button"
onclick="cerrarModalProducto()"
class="text-slate-500 text-xl"
>
✕
</button>


</div>





<form
id="formCrearProducto"
class="space-y-4 p-6"
>


@csrf



<div>

<label class="text-sm font-medium">
Categoría
</label>


<select
name="categoria_producto_id"
class="mt-1 w-full rounded-xl border p-3"
required
>


<option value="">
Seleccione categoría
</option>


@foreach($categorias as $categoria)

<option value="{{ $categoria->id }}">

{{ $categoria->nombre }}

</option>

@endforeach


</select>


</div>





<div>

<label class="text-sm font-medium">
Marca
</label>


<input

name="marca"

class="mt-1 w-full rounded-xl border p-3"

placeholder="Ej: Asus"

>


</div>





<div>

<label class="text-sm font-medium">
Nombre producto
</label>


<input

name="nombre"

class="mt-1 w-full rounded-xl border p-3"

placeholder="Ej: Vivobook"

required

>


</div>





<div>

<label class="text-sm font-medium">
Modelo
</label>


<input

name="modelo"

class="mt-1 w-full rounded-xl border p-3"

placeholder="Ej: X515"

>


</div>






<div class="flex justify-end gap-3">


<button

type="button"

onclick="cerrarModalProducto()"

class="rounded-xl border px-5 py-2"

>

Cancelar

</button>




<button

type="submit"

class="rounded-xl bg-slate-950 px-5 py-2 text-white"

>

Guardar producto

</button>



</div>



</form>



</div>


</div>


</div>





<script>


window.abrirModalProducto = function(){


document
.getElementById('modalProducto')
.classList
.remove('hidden');


}





window.cerrarModalProducto = function(){


document
.getElementById('modalProducto')
.classList
.add('hidden');


}





document
.addEventListener(
'DOMContentLoaded',
function(){



const formulario = 
document.getElementById(
'formCrearProducto'
);



if(!formulario)
return;



formulario.addEventListener(
'submit',
async function(e){


e.preventDefault();



let datos =
new FormData(this);



let respuesta =
await fetch(

"{{ route('importaciones.catalogo.productos.store') }}",

{

method:"POST",

headers:{


"X-CSRF-TOKEN":

document
.querySelector(
'meta[name="csrf-token"]'
)
.content,


"Accept":"application/json"


},

body:datos


}

);



let json =
await respuesta.json();



console.log(json);



if(json.ok){



let select =
document.getElementById(
'selectProducto'
);



let opcion =
new Option(

json.producto.label,

json.producto.id,

true,

true

);



select.add(opcion);



cerrarModalProducto();



formulario.reset();


}
else{


alert(
json.message ??
"Error creando producto"
);


}


});


});


</script>