<?php

namespace App\View\Components\Dashboard;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Progress extends Component
{

    public function __construct(
        public string $label,
        public string $value,
        public int $percentage,
    ) {
    }


    public function render(): View|Closure|string
    {
        return view('components.dashboard.progress');
    }
}