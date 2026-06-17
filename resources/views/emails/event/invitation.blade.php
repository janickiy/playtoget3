@php
    $url = route('front.events.show', ['event' => $event->id]);
@endphp

<p>Hello, {{ $invitee->displayName() }}!</p>

<p>{{ $inviter->displayName() }} invites you to the event «{{ $event->name }}».</p>

<p>
    To accept or decline the invitation, go to the page:
    <a href="{{ $url }}">{{ $url }}</a>
</p>

<p>Thank you for being with PlayToGet.</p>
