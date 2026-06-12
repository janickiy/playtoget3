@extends('front.layouts.app')

@section('content')
    <div class="content-groups friends">
        <div class="job_form">
            <div class="photo-caption">
                <h3>{{ $title }}</h3>
            </div>

            @if (session('status'))
                <div class="mutations-both">
                    <p>{{ session('status') }}</p>
                    <a class="delete">x</a>
                </div>
            @endif

            @if ($errors->any())
                <div class="mutations-both">
                    <p>{{ $errors->first() }}</p>
                    <a class="delete">x</a>
                </div>
            @endif

            <form method="POST" class="form-horizontal" id="feedback-form" action="{{ route('front.feedback.store') }}" accept-charset="UTF-8">
                @csrf

                <div class="form-group">
                    <label class="control-label col-lg-3" for="subject">Тема:</label>
                    <div class="col-lg-6">
                        <input type="text" class="form-control" name="subject" id="subject" value="{{ old('subject') }}" placeholder="Введите тему сообщения">
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3" for="name">Ваше имя:</label>
                    <div class="col-lg-6">
                        <input type="text" class="form-control" name="name" id="name" value="{{ old('name') }}" placeholder="Укажите ваше имя">
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3" for="email">Адрес электронной почты:</label>
                    <div class="col-lg-6">
                        <input type="text" class="form-control" id="email" name="email" value="{{ old('email') }}" placeholder="Введите Ваш адрес электронной почты">
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3" for="message">Ваше сообщение:</label>
                    <div class="col-lg-6">
                        <textarea rows="3" class="form-control" id="message" name="message" placeholder="Введите сообщение">{{ old('message') }}</textarea>
                    </div>
                </div>

                <br>

                <div class="form-group">
                    <div class="col-xs-offset-3 col-lg-6">
                        <input type="submit" class="btn-form save-button" value="Отправить">
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
