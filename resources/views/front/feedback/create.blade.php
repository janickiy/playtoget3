@extends('front.layouts.app')

@section('content')
    <div class="photo-caption">
        <h3>{{ $title }}</h3>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('front.feedback.store') }}">
        @csrf
        <input type="text" name="subject" value="{{ old('subject') }}" placeholder="Тема">
        <input type="text" name="name" value="{{ old('name') }}" placeholder="Имя">
        <input type="email" name="email" value="{{ old('email') }}" placeholder="Email">
        <textarea name="message" placeholder="Сообщение">{{ old('message') }}</textarea>
        @if ($errors->any())
            <div class="alert alert-error">{{ $errors->first() }}</div>
        @endif
        <button type="submit">Отправить</button>
    </form>
@endsection
