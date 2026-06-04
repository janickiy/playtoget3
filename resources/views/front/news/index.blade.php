@extends('front.layouts.app')

@section('content')
    <div class="photo-caption">
        <h3>
            {{ $title }}
            @if (! empty($newNewsCount))
                <sup> +{{ $newNewsCount }}</sup>
            @endif
        </h3>
    </div>

    <div
        id="comment-list"
        data-endpoint="{{ route('front.ajax.handle', ['action' => 'get_usernews_list']) }}"
        data-number="{{ $newsPageSize }}"
        data-offset="{{ $newsOffset }}"
        data-has-more="{{ $hasMore ? 1 : 0 }}"
    >
        @forelse ($news as $item)
            @include('front.news._item', ['item' => $item])
        @empty
            <p>Новостей пока нет.</p>
        @endforelse
    </div>
@endsection

@push('scripts')
    <script>
        $(function () {
            const $list = $('#comment-list');
            const spinner = '{{ asset('templates/images/select2-spinner.gif') }}';
            const csrfToken = '{{ csrf_token() }}';
            let isLoading = false;
            let hasMore = Number($list.data('has-more')) === 1;
            let offset = Number($list.data('offset')) || 0;
            const number = Number($list.data('number')) || 5;

            function loadNews() {
                if (isLoading || !hasMore) {
                    return;
                }

                isLoading = true;
                $list.append('<div class="loading-bar"><img border="0" src="' + spinner + '" width="20" alt=""></div>');

                $.ajax({
                    type: 'POST',
                    url: $list.data('endpoint'),
                    data: {
                        _token: csrfToken,
                        number: number,
                        offset: offset,
                    },
                    success: function (data) {
                        $list.find('.loading-bar').remove();
                        const loaded = Number(data.count) || 0;

                        if (loaded > 0 && data.html) {
                            $list.append(data.html);
                            $('.mess_news').each(function () {
                                $(this).emotions();
                            });
                        }

                        offset += number;
                        hasMore = loaded > 0 && (data.has_more === true || data.has_more === 1 || data.has_more === '1');
                    },
                    complete: function () {
                        isLoading = false;
                    },
                    error: function () {
                        $list.find('.loading-bar').remove();
                        hasMore = false;
                    }
                });
            }

            $(document).on('scroll.news', function () {
                if ($(window).scrollTop() + $(window).height() >= $(document).height() - 80) {
                    loadNews();
                }
            });
        });
    </script>
@endpush
