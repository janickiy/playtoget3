@extends('front.layouts.app')

@section('content')
    <div class="photo-caption front-section-title">
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
            if (! $list.length) {
                return;
            }

            const spinner = '{{ asset('frontend/images/select2-spinner.gif') }}';
            const csrfToken = '{{ csrf_token() }}';
            let isLoading = false;
            let hasMore = Number($list.data('has-more')) === 1;
            let offset = Number($list.data('offset')) || 0;
            const number = Number($list.data('number')) || 5;
            const shownNewsKeys = new Set();

            $list.find('.news-block-item[data-news-key]').each(function () {
                shownNewsKeys.add(String($(this).attr('data-news-key')));
            });

            function stopNewsScroll() {
                hasMore = false;
                $list.attr('data-has-more', 0).data('has-more', 0);
                $list.find('.loading-bar').remove();
                $(document).off('scroll.news');
            }

            function appendUniqueNews(html) {
                let appended = 0;

                $($.parseHTML($.trim(html), document, true)).each(function () {
                    const $item = $(this);

                    if (! $item.hasClass('news-block-item')) {
                        return;
                    }

                    const key = String($item.attr('data-news-key') || '');

                    if (key && shownNewsKeys.has(key)) {
                        return;
                    }

                    if (key) {
                        shownNewsKeys.add(key);
                    }

                    $list.append($item);
                    $item.find('.mess_news').each(function () {
                        $(this).emotions();
                    });
                    appended++;
                });

                return appended;
            }

            function loadNews() {
                if (isLoading || !hasMore) {
                    return;
                }

                isLoading = true;
                $list.append('<div class="loading-bar"><img src="' + spinner + '" width="20" alt=""></div>');

                $.ajax({
                    type: 'POST',
                    dataType: 'json',
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
                            appendUniqueNews(data.html);
                        }

                        offset += number;
                        $list.attr('data-offset', offset).data('offset', offset);
                        hasMore = loaded > 0 && (data.has_more === true || data.has_more === 1 || data.has_more === '1');

                        if (! hasMore) {
                            stopNewsScroll();
                        }
                    },
                    complete: function () {
                        isLoading = false;
                    },
                    error: function () {
                        stopNewsScroll();
                    }
                });
            }

            $(document).off('scroll.news');
            $(document).on('scroll.news', function () {
                if ($(window).scrollTop() + $(window).height() >= $(document).height() - 80) {
                    loadNews();
                }
            });
        });
    </script>
@endpush
