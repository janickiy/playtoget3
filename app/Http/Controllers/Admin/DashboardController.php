<?php

namespace App\Http\Controllers\Admin;

use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Shows start page admin panel.
     */
    public function index(): View
    {
        return view('admin.dashboard.index')->with('title', 'dashboard');
    }
}
