@php
    $isOnline = (bool) ($isOnline ?? false);
    $userId = $userId ?? null;
@endphp

<span
    class="avatar-online-status{{ $isOnline ? ' online' : ' offline' }}"
    @if ($userId) data-num="{{ $userId }}" @endif
    title="{{ $isOnline ? 'Online' : 'Offline' }}"
></span>
