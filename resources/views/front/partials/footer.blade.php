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
                    <li><a target="_blank" rel="noopener" href="https://twitter.com/playtoget_com"><img src="{{ asset('frontend/images/tw.png') }}" alt=""></a></li>
                    <li><a target="_blank" rel="noopener" href="https://www.facebook.com/playtoget.ru/"><img src="{{ asset('frontend/images/fb.png') }}" alt=""></a></li>
                    <li><a target="_blank" rel="noopener" href="https://www.instagram.com/playtoget_com/"><img src="{{ asset('frontend/images/ins.png') }}" alt=""></a></li>
                    <li><a target="_blank" rel="noopener" href="https://www.linkedin.com/groups/8510609"><img src="{{ asset('frontend/images/in.png') }}" alt=""></a></li>
                    <li><a target="_blank" rel="noopener" href="https://www.youtube.com/channel/UC44vVK3JlHCIBBpIY16Ok2g"><img src="{{ asset('frontend/images/youtube.png') }}" alt=""></a></li>
                </ul>
                <p class="copyright">© {{ date('Y') }} PlayToget: Спорт внутри</p>
            </div>
        </div>
    </div>
</section>
<script src="{{ asset('frontend/js/jquery.confirm.js') }}"></script>
<script src="{{ asset('frontend/js/jquery.emotions.js') }}"></script>
<script src="{{ asset('frontend/js/emotions.js') }}"></script>
