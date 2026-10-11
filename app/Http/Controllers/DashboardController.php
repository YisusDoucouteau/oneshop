<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(
        Request $request,
        DashboardService $dashboard
    ): View {
        $datos = $dashboard->obtenerResumen(
            $request->user()
        );

        return view(
            'dashboard',
            $datos
        );
    }
}
