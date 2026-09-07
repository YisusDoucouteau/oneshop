<section class="mb-6 rounded-2xl border border-slate-200 bg-white shadow-sm">


<div class="border-b border-slate-200 px-6 py-5">

<h2 class="font-semibold text-slate-950">
Productos del lote
</h2>

<p class="text-sm text-slate-500">
Productos esperados dentro de esta importación.
</p>

</div>



<div class="p-6">


<table class="w-full text-sm">


<thead>

<tr class="border-b text-left text-slate-500">

<th class="py-3">
Producto
</th>

<th>
Cantidad esperada
</th>

<th>
Cantidad recibida
</th>

</tr>

</thead>




<tbody id="tablaDetalles">


@forelse($lote->detalles as $detalle)


<tr
class="border-b"
data-detalle-id="{{ $detalle->id }}"
>


<td class="py-3 font-medium">

{{ $detalle->producto?->marca?->nombre }}

{{ $detalle->producto?->nombre }}

{{ $detalle->producto?->modelo }}

</td>



<td class="cantidad-esperada">

{{ $detalle->cantidad_esperada }}

</td>



<td>

{{ $detalle->cantidad_recibida }}

</td>



</tr>



@empty


<tr id="filaVacia">

<td colspan="3"
class="py-6 text-center text-slate-500">

Todavía no existen productos registrados en este lote.

</td>

</tr>


@endforelse


</tbody>


</table>





<hr class="my-6">



<h3 class="mb-4 font-semibold">

Agregar producto al lote

</h3>




<form id="formAgregarProducto">


@csrf



<div class="grid gap-4 md:grid-cols-3">



<select

name="producto_id"

id="selectProducto"

class="rounded-xl border p-3"

required

>


<option value="">
Seleccione producto
</option>



<option value="crear">
+ Crear producto nuevo
</option>



@foreach($productos as $producto)


<option value="{{ $producto->id }}">


{{ $producto->marca?->nombre }}

{{ $producto->nombre }}

{{ $producto->modelo }}


</option>


@endforeach



</select>






<input

type="number"

name="cantidad_esperada"

value="1"

min="1"

class="rounded-xl border p-3"

required

>






<button

type="submit"

class="rounded-xl bg-slate-950 px-5 text-white"

>

Agregar

</button>




</div>


</form>



</div>



</section>






<script>


// Abrir modal crear producto

document
.getElementById('selectProducto')
.addEventListener(
'change',
function(){


if(this.value === 'crear'){


window.abrirModalProducto();


this.value = "";


}


});






// Agregar producto al lote

document
.getElementById('formAgregarProducto')
.addEventListener(
'submit',
async function(e){


e.preventDefault();



let formulario=this;



let datos =
new FormData(formulario);




let respuesta =
await fetch(

"{{ route('importaciones.detalles.store',$lote) }}",

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


let detalle=json.detalle;




let filaExistente =
document.querySelector(

`[data-detalle-id="${detalle.id}"]`

);





if(filaExistente){


filaExistente
.querySelector(
'.cantidad-esperada'
)
.innerText =
detalle.cantidad_esperada;



}

else{


let fila = `

<tr

class="border-b"

data-detalle-id="${detalle.id}"

>


<td class="py-3 font-medium">

${detalle.producto}

</td>


<td class="cantidad-esperada">

${detalle.cantidad_esperada}

</td>


<td>

${detalle.cantidad_recibida}

</td>


</tr>

`;




let vacia =
document.getElementById(
'filaVacia'
);



if(vacia){

vacia.remove();

}



document
.getElementById(
'tablaDetalles'
)
.insertAdjacentHTML(
'beforeend',
fila
);



}




formulario.reset();



}

else{


alert(
json.message ??
"Error registrando producto"
);


}



});



</script>