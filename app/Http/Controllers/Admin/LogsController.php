<?php

namespace App\Http\Controllers\Admin;

use Illuminate\View\View;

class LogsController extends Controller
{
    /**
     * Показывает список логов авторизации пользователей.
     */
    public function index(): View
    {
        return view('admin.logs.index', [
            'title' => 'Логи',
        ]);
    }
}
