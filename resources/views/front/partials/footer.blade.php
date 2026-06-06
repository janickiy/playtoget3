<section class="wrapper section-for-footer">
    <div class="container">
        <div class="col-xs-12">
            <div class="row">
                <ul class="menu-footer">
                    <li><a href="{{ route('front.content.show', ['content' => 1]) }}">О сервисе</a></li>
                    <li><a href="{{ route('front.content.show', ['content' => 2]) }}">Возможности</a></li>
                    <li><a href="{{ route('front.content.show', ['content' => 3]) }}">Реклама на сайте</a></li>
                    <li><a href="{{ route('front.content.show', ['content' => 4]) }}">Пользовательское соглашение</a></li>
                    <li><a href="{{ route('front.content.show', ['content' => 5]) }}">Правила пользования</a></li>
                    <li><a href="{{ route('front.feedback.create') }}">Обратная связь</a></li>
                </ul>
                <ul class="soc-menu">
                    <li><a target="_blank" rel="noopener" href="https://twitter.com/playtoget_com"><img src="{{ asset('frontend/images/tw.png') }}" alt=""></a></li>
                    <li><a target="_blank" rel="noopener" href="https://vk.com/playtoget"><img src="{{ asset('frontend/images/vk.png') }}" alt=""></a></li>
                    <li><a target="_blank" rel="noopener" href="https://www.facebook.com/playtoget.ru/"><img src="{{ asset('frontend/images/fb.png') }}" alt=""></a></li>
                    <li><a target="_blank" rel="noopener" href="https://www.instagram.com/playtoget_com/"><img src="{{ asset('frontend/images/ins.png') }}" alt=""></a></li>
                    <li><a target="_blank" rel="noopener" href="http://www.ok.ru/group/52832209666136"><img src="{{ asset('frontend/images/ok.png') }}" alt=""></a></li>
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
