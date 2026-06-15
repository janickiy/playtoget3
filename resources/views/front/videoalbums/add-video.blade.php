@extends('front.layouts.app')

@section('content')
    <h2 class="video-form-title">{{ $title }}</h2>

    @if ($errors->any())
        <div class="mutations-both">
            <p>{{ $errors->first() }}</p>
            <a class="delete">x</a>
        </div>
    @endif

    <form autocomplete="off" class="form-horizontal" method="POST" action="{{ route('front.videoalbums.store-video') }}" accept-charset="UTF-8">
        @csrf
        <div class="job_form">
            <div class="form-group">
                <label class="col-lg-3 control-label" for="video">Ссылка на видео:</label>
                <div class="col-lg-7">
                    <input class="form-control" type="text" value="{{ old('video') }}" name="video" id="video">
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-3 control-label" for="description">Описание:</label>
                <div class="col-lg-7">
                    <textarea class="form-control form-dark" id="description" rows="4" name="description">{{ old('description') }}</textarea>
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-3 control-label" for="videoalbum_id">Выберите альбом:</label>
                <div class="col-lg-7">
                    <div class="styled-select styled-select-4">
                        <select name="videoalbum_id" id="videoalbum_id">
                            @foreach ($albums as $album)
                                <option value="{{ $album->id }}" @selected((int) old('videoalbum_id') === (int) $album->id)>{{ $album->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="control center_text">
            <input class="btn-form save-button margin0Auto" type="submit" value="Сохранить">
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        selectAction();
    </script>
@endpush
