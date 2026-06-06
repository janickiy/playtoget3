@foreach ($comments as $comment)
    @include('front.profile._comment', ['comment' => $comment, 'viewer' => $viewer])
@endforeach
