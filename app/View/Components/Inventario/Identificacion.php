<?php

namespace App\View\Components\Inventario;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Identificacion extends Component
{
    public function __construct(
        public $equipo
    ) {}

    public function render(): View|Closure|string
    {
        return view('components.inventario.identificacion');
    }
}