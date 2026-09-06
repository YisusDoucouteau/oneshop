<?php

namespace App\View\Components\Inventario;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Trazabilidad extends Component
{
    public function __construct(
        public $eventos
    ) {}

    public function render(): View|Closure|string
    {
        return view('components.inventario.trazabilidad');
    }
}