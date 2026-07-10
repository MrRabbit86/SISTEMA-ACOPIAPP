<?php

namespace App\Http\Controllers\Web;

use App\Enums\Rol;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class DashboardController extends Controller
{
    public function index(): RedirectResponse
    {
        return match (auth()->user()->rol) {
            Rol::CIUDADANO => redirect()->route('mis-ofertas'),
            Rol::EMPRESA => redirect()->route('ofertas.mapa'),
            default => redirect()->route('admin.usuarios'),
        };
    }
}