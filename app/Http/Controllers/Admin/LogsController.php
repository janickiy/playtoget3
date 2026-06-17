<?php

namespace App\Http\Controllers\Admin;

use Illuminate\View\View;

class LogsController extends Controller
{
    /**
     * Shows list logs authorization users.
     */
    public function index(): View
    {
        return view('admin.logs.index', [
            'title' => 'Logs',
        ]);
    }
}
