<p>Hello, {{ $user->displayName() }}!</p>

<p>You requested deletion of your PlayToGet account.</p>

<p>
    To confirm account deletion, open this link:
    <a href="{{ $confirmationUrl }}">{{ $confirmationUrl }}</a>
</p>

<p>This link is valid for 24 hours. If you did not request account deletion, ignore this email.</p>
