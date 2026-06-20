<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\Front\Auth\LoginRequest;
use App\Http\Requests\Front\Auth\PasswordResetEmailRequest;
use App\Http\Requests\Front\Auth\PasswordResetRequest;
use App\Http\Requests\Front\Auth\RegisterRequest;
use App\Repositories\SocialAccountRepository;
use App\Repositories\UserRepository;
use App\Service\AccountRegistrationService;
use App\Service\PasswordResetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
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
     * Shows the registration form.
     */
    public function showRegistrationForm(): View
    {
        return view('front.auth.registration', [
            'title' => 'Registration',
        ]);
    }

    /**
     * Creates a new account and sends an email confirmation link.
     */
    public function register(RegisterRequest $request, AccountRegistrationService $registration): RedirectResponse
    {
        $registration->register($request->validated());

        return redirect()
            ->route('front.registration.form')
            ->with('status', 'We sent a confirmation link to your email. Please check your mailbox.');
    }

    /**
     * Confirms the account by the email token.
     */
    public function confirmRegistration(string $token, AccountRegistrationService $registration): RedirectResponse
    {
        $user = $registration->confirm($token);

        abort_if(! $user, 404);

        return redirect()
            ->route('front.home')
            ->with('auth_status', 'Your account has been confirmed. You can sign in now.');
    }

    /**
     * Sends a password reset link without revealing whether the email exists.
     */
    public function sendPasswordResetLink(
        PasswordResetEmailRequest $request,
        PasswordResetService $passwords,
    ): RedirectResponse {
        $passwords->sendResetLink($request->email());

        return back()
            ->with('password_reset_mode', true)
            ->with('password_reset_status', 'If this email exists, we sent a password reset link.');
    }

    /**
     * Shows the password reset form from an email link.
     */
    public function showRestoreForm(Request $request): View
    {
        return view('front.auth.restore', [
            'title' => 'Password reset',
            'email' => (string) $request->query('email', ''),
            'token' => (string) $request->query('token', ''),
        ]);
    }

    /**
     * Updates the password using a valid reset token.
     */
    public function restorePassword(
        PasswordResetRequest $request,
        PasswordResetService $passwords,
    ): RedirectResponse {
        if (! $passwords->reset($request->email(), $request->token(), $request->password())) {
            return back()
                ->withInput($request->only(['email', 'token']))
                ->withErrors(['password' => 'The password reset link is invalid or expired.']);
        }

        return redirect()
            ->route('front.home')
            ->with('auth_status', 'Password changed successfully. You can sign in now.');
    }

    /**
     * Checks user by bcrypt-password and authorized website.
     *
     * @param LoginRequest $request
     * @param UserRepository $users
     * @return RedirectResponse
     */
    public function login(LoginRequest $request, UserRepository $users): RedirectResponse
    {
        $user = $users->findForLogin($request->email());
        $loginError = ['username' => 'Invalid email or password.'];

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
     * Redirects the user to the selected OAuth provider authorization page.
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
                ->withErrors(['username' => 'Authorization through the selected service is temporarily unavailable.']);
        }

        try {
            return Socialite::driver($driver)->redirect();
        } catch (DriverMissingConfigurationException) {
            return redirect()
                ->route('front.home')
                ->withErrors(['username' => 'Authorization through the selected service is temporarily unavailable.']);
        }
    }

    /**
     * Processes the OAuth-provider callback and authorizes the local user.
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
                ->withErrors(['username' => 'Authorization through the selected service is temporarily unavailable.']);
        }

        try {
            $socialUser = Socialite::driver($driver)->user();
            $user = $accounts->findOrCreateUser($provider, $socialUser);

            if (! $user->isConfirmed()) {
                return redirect()
                    ->route('front.home')
                    ->withErrors(['username' => 'Invalid email or password.']);
            }

            Auth::guard('web')->login($user, true);

            return redirect()->intended(route('front.news.index'));
        } catch (InvalidStateException|DriverMissingConfigurationException|RuntimeException $exception) {
            return redirect()
                ->route('front.home')
                ->withErrors(['username' => $exception->getMessage() ?: 'Authorization failed.']);
        } catch (Throwable) {
            return redirect()
                ->route('front.home')
                ->withErrors(['username' => 'Authorization failed.']);
        }
    }

    /**
     * Ends user session and returns to home page
     *
     * @return RedirectResponse
     */
    public function logout(): RedirectResponse
    {
        Auth::guard('web')->logout();

        return redirect()->route('front.home');
    }

    /**
     * Maps the provider public name from the Socialite driver.
     */
    private function driverFor(string $provider): ?string
    {
        return self::SOCIAL_PROVIDERS[$provider] ?? null;
    }

    /**
     * Checks that the required OAuth settings are filled in for the provider.
     */
    private function providerIsConfigured(string $driver): bool
    {
        $config = Config::get('services.' . $driver, []);

        return filled($config['client_id'] ?? null)
            && filled($config['client_secret'] ?? null)
            && filled($config['redirect'] ?? null);
    }
}
