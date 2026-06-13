@extends('app')

@section('title', $title)

@section('css')
    {!! Html::style('/plugins/summernote/summernote-bs4.min.css') !!}
    {!! Html::style('/plugins/codemirror/codemirror.css') !!}
    {!! Html::style('/plugins/codemirror/theme/monokai.css') !!}

    <style>
        .announcement-text-field textarea,
        .announcement-text-field .note-editable {
            height: 120px;
            min-height: 120px;
            overflow-y: auto;
            resize: vertical;
        }
    </style>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <header class="card card-primary">
                        {!! Form::open(['url' => isset($row) ? route('admin.announcements.update') : route('admin.announcements.store'), 'method' => isset($row) ? 'put' : 'post']) !!}

                        {!! isset($row) ? Form::hidden('id', $row->id) : '' !!}
                        {!! Form::hidden('published', 0) !!}

                        <div class="card-body">
                            <p>*-обязательные поля</p>

                            <div class="form-group">
                                {!! Form::label('title', 'Название*') !!}
                                {!! Form::text('title', old('title', $row->title ?? null), ['id' => 'title', 'class' => 'form-control']) !!}
                                @if ($errors->has('title'))
                                    <p class="text-danger">{{ $errors->first('title') }}</p>
                                @endif
                            </div>

                            <div class="form-group">
                                {!! Form::label('slug', 'ЧПУ') !!}
                                {!! Form::text('slug', old('slug', $row->slug ?? null), ['id' => 'slug', 'class' => 'form-control']) !!}
                                @if ($errors->has('slug'))
                                    <p class="text-danger">{{ $errors->first('slug') }}</p>
                                @endif
                            </div>

                            <div class="form-group announcement-text-field">
                                {!! Form::label('text', 'Содержание*') !!}
                                {!! Form::textarea('text', old('text', $row->text ?? null), ['rows' => '5', 'placeholder' => 'Описание', 'id' => 'summernote', 'style' => 'display: none;']) !!}
                                @if ($errors->has('text'))
                                    <p class="text-danger">{{ $errors->first('text') }}</p>
                                @endif
                            </div>

                            <div class="form-check">
                                {!! Form::checkbox('published', 1, (bool) old('published', isset($row) ? $row->published : 1), ['id' => 'published', 'class' => 'form-check-input']) !!}
                                {!! Form::label('published', 'Публиковать', ['class' => 'form-check-label']) !!}
                                @if ($errors->has('published'))
                                    <p class="text-danger">{{ $errors->first('published') }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                {{ isset($row) ? 'редактировать' : 'добавить' }}
                            </button>
                            <a class="btn btn-default float-sm-right" href="{{ route('admin.announcements.index') }}">
                                назад
                            </a>
                        </div>

                        {!! Form::close() !!}
                    </header>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('js')
    {!! Html::script('/plugins/summernote/summernote-bs4.min.js') !!}
    {!! Html::script('/plugins/codemirror/codemirror.js') !!}
    {!! Html::script('/plugins/codemirror/mode/css/css.js') !!}
    {!! Html::script('/plugins/codemirror/mode/xml/xml.js') !!}
    {!! Html::script('/plugins/codemirror/mode/htmlmixed/htmlmixed.js') !!}
    {!! Html::script('/plugins/bs-custom-file-input/bs-custom-file-input.min.js') !!}

    <script>
        $(document).ready(function () {
            $('#summernote').summernote({
                height: 120
            });
            bsCustomFileInput.init();

            @if (! isset($row))
            let slugTimer = null;
            let slugRequest = null;

            $("#title").on("change keyup input", function () {
                let title = this.value;

                clearTimeout(slugTimer);
                slugTimer = setTimeout(function () {
                    if (title.length < 2) {
                        $("#slug").val('');
                        return;
                    }

                    if (slugRequest) {
                        slugRequest.abort();
                    }

                    slugRequest = $.ajax({
                        url: '{!! route('admin.ajax') !!}',
                        method: "POST",
                        data: {
                            action: "get_announcement_slug",
                            title: title
                        },
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        dataType: "json"
                    });

                    slugRequest.done(function (data) {
                        if (data.slug != null && data.slug !== '') {
                            $("#slug").val(data.slug);
                        }
                    });
                }, 250);
            });
            @endif
        });
    </script>
@endsection
