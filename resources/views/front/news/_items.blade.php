@foreach ($news as $item)
    @include('front.news._item', ['item' => $item])
@endforeach
