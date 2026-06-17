@foreach ($items as $item)
    @include('front.sport-blocks._card', [
        'item' => $item,
        'routePrefix' => $routePrefix,
        'viewer' => $viewer ?? null,
        'editLabel' => $editLabel ?? 'Edit',
    ])
@endforeach
