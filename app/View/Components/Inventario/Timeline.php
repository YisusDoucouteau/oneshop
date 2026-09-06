<?php

namespace App\View\Components\Inventario;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Timeline extends Component
{
    public $eventos;

    public function __construct($eventos = [])
    {
        $this->eventos = $eventos;
    }

    public function render(): View|Closure|string
    {
        return view('components.inventario.timeline');
    }
}