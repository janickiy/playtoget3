<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\Front\Auth\LoginRequest;
use App\Repositories\SocialAccountRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Exceptions\DriverMissingConfigurationException;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use RuntimeException;
use Throwable;

class AuthController extends Controller
{
    private const SOCIAL_PROVIDERS = [
        'google' => 'google',
        'facebook' => 'facebook',
        'x' => 'x',
        'linkedin' => 'linkedin-openid',
    ];

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
     * Перенаправляет пользователя на страницу авторизации выбранного OAuth-провайдера.
     *
     * @param string $provider
     * @return RedirectResponse
     */
    public function redirectToProvider(string $provider): RedirectResponse
    {
        $driver = $this->driverFor($provider);

        if (! $driver || ! $this->providerIsConfigured($driver)) {
            return redirect()
                ->route('front.home')
                ->withErrors(['username' => 'Авторизация через выбранный сервис временно недоступна.']);
        }

        try {
            return Socialite::driver($driver)->redirect();
        } catch (DriverMissingConfigurationException) {
            return redirect()
                ->route('front.home')
                ->withErrors(['username' => 'Авторизация через выбранный сервис временно недоступна.']);
        }
    }

    /**
     * Обрабатывает callback OAuth-провайдера и авторизует локального пользователя.
     *
     * @param string $provider
     * @param SocialAccountRepository $accounts
     * @return RedirectResponse
     */
    public function handleProviderCallback(
        string $provider,
        SocialAccountRepository $accounts,
    ): RedirectResponse {
        $driver = $this->driverFor($provider);

        if (! $driver || ! $this->providerIsConfigured($driver)) {
            return redirect()
                ->route('front.home')
                ->withErrors(['username' => 'Авторизация через выбранный сервис временно недоступна.']);
        }

        try {
            $socialUser = Socialite::driver($driver)->user();
            $user = $accounts->findOrCreateUser($provider, $socialUser);

            if (! $user->isConfirmed()) {
                return redirect()
                    ->route('front.home')
                    ->withErrors(['username' => 'Неверный email или пароль.']);
            }

            Auth::guard('web')->login($user, true);

            return redirect()->intended(route('front.news.index'));
        } catch (InvalidStateException|DriverMissingConfigurationException|RuntimeException $exception) {
            return redirect()
                ->route('front.home')
                ->withErrors(['username' => $exception->getMessage() ?: 'Не удалось выполнить авторизацию.']);
        } catch (Throwable) {
            return redirect()
                ->route('front.home')
                ->withErrors(['username' => 'Не удалось выполнить авторизацию.']);
        }
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

    /**
     * Сопоставляет публичное имя провайдера с драйвером Socialite.
     */
    private function driverFor(string $provider): ?string
    {
        return self::SOCIAL_PROVIDERS[$provider] ?? null;
    }

    /**
     * Проверяет, что для провайдера заполнены обязательные OAuth-настройки.
     */
    private function providerIsConfigured(string $driver): bool
    {
        $config = Config::get('services.' . $driver, []);

        return filled($config['client_id'] ?? null)
            && filled($config['client_secret'] ?? null)
            && filled($config['redirect'] ?? null);
    }
}
