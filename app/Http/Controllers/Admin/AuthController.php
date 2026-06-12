<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\View;

class AuthController extends Controller
{
    /**
     * Ограничивает доступ к страницам авторизации для уже авторизованных администраторов.
     */
    public function __construct()
    {
        $this->middleware('guest:admin', ['except' => ['logout']]);
    }

    /**
     * Показывает форму входа в админку.
     *
     * @return View
     */
    public function showLoginForm(): View
    {
        return view('login')->with('title', 'Авторизация');
    }

    /**
     * Проверяет учетные данные администратора и выполняет вход в админку.
     *
     * @param Request $request
     * @return RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request): RedirectResponse
    {
        // Validate the form data
        $this->validate($request, [
            'login'   => 'required',
            'password' => 'required|min:6'
        ]);

        // Attempt to log the user in
        if (Auth::guard('admin')->attempt(['login' => $request->login, 'password' => $request->password], $request->remember)) {
            return redirect()->route('admin.dashboard.index');
        }
        // if unsuccessful, then redirect back to the login with the form data
        return redirect()->route('login')->with('error', "Неверный логин или пароль!");
    }

    /**
     * Перенаправляет администратора на дашборд после успешной авторизации.
     *
     * @param $request
     * @param $user
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    protected function authenticated($request, $user)
    {
        return redirect()->route('admin.dashboard.index');
    }

    /**
     * Завершает админскую сессию и возвращает на страницу входа.
     *
     * @return RedirectResponse
     */
    public function logout(): RedirectResponse
    {
        Auth::guard('admin')->logout();

        return redirect()->route('login');
    }
}
