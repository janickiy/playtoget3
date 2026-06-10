<?php

namespace App\Http\Controllers\Admin;

use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Показывает стартовую страницу админской панели.
     */
    public function index(): View
    {
        return view('admin.dashboard.index')->with('title', 'dashboard');
    }
}
