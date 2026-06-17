@php
    $label = $community->type === 'group' ? 'group' : 'team';
    $url = $community->type === 'group'
        ? route('front.groups.show', ['community' => $community->id])
        : route('front.teams.show', ['community' => $community->id]);
@endphp

<p>Hello, {{ $invitee->displayName() }}!</p>

<p>{{ $inviter->displayName() }} invites you to join {{ $label }} «{{ $community->name }}».</p>

<p>
    To accept or decline the invitation, go to the page:
    <a href="{{ $url }}">{{ $url }}</a>
</p>

<p>Thank you for being with PlayToGet.</p>
