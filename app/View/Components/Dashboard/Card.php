<?php

namespace App\View\Components\Dashboard;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Card extends Component
{
    public function __construct(
        public string $title,
        public string|int $value,
        public string $description = '',
        public string $icon = 'package',
    ) {
    }


    public function render(): View|Closure|string
    {
        return view('components.dashboard.card');
    }
}