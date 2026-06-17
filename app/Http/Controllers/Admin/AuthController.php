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
     * Restricts access к страницам authorization для уже авторизованных administratorов.
     */
    public function __construct()
    {
        $this->middleware('guest:admin', ['except' => ['logout']]);
    }

    /**
     * Shows form login в админку.
     *
     * @return View
     */
    public function showLoginForm(): View
    {
        return view('login')->with('title', 'Authorization');
    }

    /**
     * Checks учетные data administrator и выполняет вход в админку.
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
        return redirect()->route('login')->with('error', "Invalid login or password!");
    }

    /**
     * Redirects administrator на dashboard после успешной authorization.
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
     * Ends admin session и возвращает на page login.
     *
     * @return RedirectResponse
     */
    public function logout(): RedirectResponse
    {
        Auth::guard('admin')->logout();

        return redirect()->route('login');
    }
}
