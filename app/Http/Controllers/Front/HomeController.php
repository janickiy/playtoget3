<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Показывает главную страницу или отправляет авторизованного пользователя в новости.
     *
     * @param Request $request
     * @return View|RedirectResponse
     */
    public function index(Request $request): View|RedirectResponse
    {
        if (Auth::guard('web')->check()) {
            return redirect()->route('front.news.index');
        }

        return view('front.auth.login', [
            'title' => 'Спортивный интернет-проект',
            'email' => $request->old('username'),
        ]);
    }
}
