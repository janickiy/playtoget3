<p>Hello, {{ $user->displayName() }}!</p>

<p>You requested a password reset for your PlayToGet account.</p>

<p>
    Open this link to set a new password:
    <a href="{{ $resetUrl }}">{{ $resetUrl }}</a>
</p>

<p>This link is valid for 60 minutes. If you did not request a password reset, ignore this email.</p>
