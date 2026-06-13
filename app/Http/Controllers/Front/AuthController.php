<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\Front\Auth\LoginRequest;
use App\Repositories\UserRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Проверяет пользователя по bcrypt-паролю и авторизует на сайт.
     *
     * @param LoginRequest $request
     * @param UserRepository $users
     * @return RedirectResponse
     */
    public function login(LoginRequest $request, UserRepository $users): RedirectResponse
    {
        $user = $users->findForLogin($request->email());
        $loginError = ['username' => 'Неверный email или пароль.'];

        if (! $user || ! $user->isConfirmed()) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors($loginError);
        }

        if (! $users->passwordUsesBcrypt($user) || ! Hash::check($request->password(), $user->password)) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors($loginError);
        }

        Auth::guard('web')->login($user, $request->remember());

        return redirect()->intended(route('front.news.index'));
    }

    /**
     * Завершает пользовательскую сессию и возвращает на главную страницу
     *
     * @return RedirectResponse
     */
    public function logout(): RedirectResponse
    {
        Auth::guard('web')->logout();

        return redirect()->route('front.home');
    }
}
