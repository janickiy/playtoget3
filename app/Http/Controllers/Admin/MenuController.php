<?php

namespace App\Http\Controllers\Admin;

use Illuminate\View\View;

class MenuController extends Controller
{
    /**
     * @return View
     */
    public function index(): View
    {
        return view('admin.menu.index')->with('title', 'Menu');
    }
}
