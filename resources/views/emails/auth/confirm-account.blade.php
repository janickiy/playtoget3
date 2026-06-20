<p>Hello, {{ $user->displayName() }}!</p>

<p>Welcome to PlayToGet. Please confirm your account to finish registration.</p>

<p>
    Open this link to confirm your account:
    <a href="{{ $confirmationUrl }}">{{ $confirmationUrl }}</a>
</p>

<p>This link is valid for 24 hours. If you did not create an account, ignore this email.</p>
