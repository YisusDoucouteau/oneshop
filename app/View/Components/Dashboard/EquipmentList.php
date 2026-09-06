<?php

namespace App\View\Components\Dashboard;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class EquipmentList extends Component
{
    public function __construct(
        public $equipos
    ) {}

    public function render(): View|Closure|string
    {
        return view('components.dashboard.equipment-list');
    }
}