@extends('front.layouts.app')

@section('content')
    <h2>{{ $title }}</h2>

    @if ($errors->any())
        <div class="mutations-both">
            <p>{{ $errors->first() }}</p>
            <a class="delete">x</a>
        </div>
    @endif

    <form autocomplete="off" class="form-horizontal" method="POST" action="{{ $action }}" accept-charset="UTF-8">
        @csrf
        <div class="education_form">
            <div class="form-group">
                <label class="col-lg-3 control-label" for="name">Название</label>
                <div class="col-lg-6">
                    <input class="form-control" type="text" value="{{ $name }}" name="name" id="name">
                </div>
            </div>
        </div>
        <div class="control center_text">
            <input class="btn-form save-button margin0Auto" type="submit" value="{{ $button }}">
        </div>
    </form>
@endsection
