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
    public function login(LoginRequest $request, UserRepository $users): RedirectResponse
    {
        $user = $users->findForLogin($request->email());

        if (!$user) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['username' => 'Пользователь не найден или доступ к аккаунту ограничен.']);
        }

        if ($users->passwordMatchesLegacy($user, $request->password())) {
            $users->replacePassword($user, Hash::make($request->password()));

            $user->refresh();
        } elseif (!$users->passwordUsesBcrypt($user) || !Hash::check($request->password(), $user->password)) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['username' => 'Неверный email или пароль.']);
        }

        Auth::guard('web')->login($user, $request->remember());

        return redirect()->intended(route('front.news.index'));
    }

    public function logout(): RedirectResponse
    {
        Auth::guard('web')->logout();

        return redirect()->route('front.home');
    }
}
