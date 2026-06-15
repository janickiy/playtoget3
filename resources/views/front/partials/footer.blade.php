<section class="wrapper section-for-footer">
    <div class="container">
        <div class="col-xs-12">
            <div class="row">

                @if(!empty($menu['bottom']))
                <ul class="menu-footer">
                    @foreach($menu['bottom'] ?? [] as $item)
                    <li><a href="{{ $item['link'] ?? '#' }}">{{ $item['label'] ?? '' }}</a></li>
                    @endforeach
                </ul>
                @endif

                <ul class="soc-menu">
                    <li><a target="_blank" rel="noopener" href="https://x.com/playtoget_com"><img src="{{ asset('frontend/images/x.svg') }}" alt="X"></a></li>
                    <li><a target="_blank" rel="noopener" href="https://www.facebook.com/playtoget.ru/"><img src="{{ asset('frontend/images/fb.svg') }}" alt=""></a></li>
                    <li><a target="_blank" rel="noopener" href="https://www.instagram.com/playtoget_com/"><img src="{{ asset('frontend/images/ins.svg') }}" alt=""></a></li>
                    <li><a target="_blank" rel="noopener" href="https://www.linkedin.com/groups/8510609"><img src="{{ asset('frontend/images/in.svg') }}" alt=""></a></li>
                    <li><a target="_blank" rel="noopener" href="https://www.youtube.com/channel/UC44vVK3JlHCIBBpIY16Ok2g"><img src="{{ asset('frontend/images/youtube.svg') }}" alt=""></a></li>
                </ul>
                <p class="copyright">© {{ date('Y') }} PlayToget: Спорт внутри</p>
            </div>
        </div>
    </div>
</section>
<script src="{{ asset('frontend/js/jquery.confirm.js') }}?v=2026061501"></script>
<script src="{{ asset('frontend/js/jquery.emotions.js') }}?v=2026061502"></script>
<script src="{{ asset('frontend/js/emotions.js') }}?v=2026061503"></script>
