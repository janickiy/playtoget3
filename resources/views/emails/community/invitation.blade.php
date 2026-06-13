@php
    $label = $community->type === 'group' ? 'группу' : 'команду';
    $url = $community->type === 'group'
        ? route('front.groups.show', ['community' => $community->id])
        : route('front.teams.show', ['community' => $community->id]);
@endphp

<p>Здравствуйте, {{ $invitee->displayName() }}!</p>

<p>{{ $inviter->displayName() }} приглашает вас вступить в {{ $label }} «{{ $community->name }}».</p>

<p>
    Чтобы принять или отклонить приглашение, перейдите на страницу:
    <a href="{{ $url }}">{{ $url }}</a>
</p>

<p>Спасибо, что вы с PlayToGet.</p>
