@php
    $url = route('front.events.show', ['event' => $event->id]);
@endphp

<p>Здравствуйте, {{ $invitee->displayName() }}!</p>

<p>{{ $inviter->displayName() }} приглашает вас на мероприятие «{{ $event->name }}».</p>

<p>
    Чтобы принять или отклонить приглашение, перейдите на страницу:
    <a href="{{ $url }}">{{ $url }}</a>
</p>

<p>Спасибо, что вы с PlayToGet.</p>
