<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(
        DashboardService $dashboard
    ): View {

        $datos = $dashboard->obtenerResumen();

        return view(
            'dashboard',
            $datos
        );
    }
}