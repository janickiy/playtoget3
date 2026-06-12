@extends('app')

@section('title', $title)

@section('css')

    <!-- summernote -->
    {!! Html::style('/plugins/summernote/summernote-bs4.min.css') !!}
    <!-- CodeMirror -->
    {!! Html::style('/plugins/codemirror/codemirror.css') !!}
    {!! Html::style('/plugins/codemirror/theme/monokai.css') !!}

@endsection

@section('content')

    <!-- Main content -->
    <section class="content">

        <div class="container-fluid">
            <div class="row">
                <div class="col-12">

                    <!-- general form elements -->
                    <header class="card card-primary">

                        <!-- form start -->
                        {!! Form::open(['url' => isset($row) ? route('admin.settings.update') : route('admin.settings.store'), 'files' => true, 'method' => isset($row) ? 'put' : 'post']) !!}

                        {!! isset($row) ? Form::hidden('id', $row->id) : '' !!}

                        <div class="card-body">

                            <p>*-обязательные поля</p>

                            <div class="form-group">

                                {!! Form::label('key_cd', 'Ключ*') !!}

                                @if(isset($row))
                                    {!! Form::text('key_cd', old('key_cd', $row->key_cd ?? null), ['class' => 'form-control', 'readonly']) !!}
                                @else
                                    {!! Form::text('key_cd', old('key_cd'), ['class' => 'form-control']) !!}
                                @endif

                                @if ($errors->has('key_cd'))
                                    <p class="text-danger">{{ $errors->first('key_cd') }}</p>
                                @endif
                            </div>

                            <div class="form-group">

                                {!! Form::label('name', 'Название') !!}

                                @if(isset($row))
                                    {!! Form::text('name', old('name', $row->name ?? null), ['class' => 'form-control']) !!}
                                @else
                                    {!! Form::text('name', old('key_cd'), ['class' => 'form-control']) !!}
                                @endif

                                @if ($errors->has('name'))
                                    <p class="text-danger">{{ $errors->first('name') }}</p>
                                @endif
                            </div>

                            <div class="form-group">
                                {!! Form::label('type', 'Тип*') !!}
                                {!! Form::text('type', old('type', isset($row) ? $row->type : $type), ['class' => 'form-control', 'readonly']) !!}
                                @if ($errors->has('type'))
                                    <p class="text-danger">{{ $errors->first('type') }}</p>
                                @endif
                            </div>

                            <div class="form-group">

                                @if(isset($row) && $row->type == 'FILE' || $type == 'FILE')

                                    {!! Form::label('value', 'Файл* (jpg,png,txt,doc,docx,pdf,xls,xlsx,odt,ods,pdf)') !!}

                                    <div class="input-group">
                                        <div class="custom-file">
                                            {!! Form::file('value',  [ 'class' => 'custom-file-input']) !!}

                                            {!! Form::label('value', 'Выберите файл*', ['class' => 'custom-file-label']) !!}
                                        </div>
                                        <div class="input-group-append">
                                            <span class="input-group-text">Обзор...</span>
                                        </div>
                                    </div>

                                @elseif (isset($row) && $row->type == 'HTML' || $type == 'HTML')
                                    {!! Form::label('value', 'Значение*') !!}
                                    {!! Form::textarea('value', old('value', $row->value ?? null), ['rows' => "3", 'placeholder' => "",  'id' => 'summernote', 'style' => "display: none;"]) !!}
                                @else
                                    {!! Form::label('value', 'Значение*') !!}
                                    {!! Form::text('value', old('value', $row->value ?? null ), ['class' => 'form-control']) !!}
                                @endif

                                @if ($errors->has('value'))
                                    <p class="text-danger">{{ $errors->first('value') }}</p>
                                @endif

                            </div>

                            <div class="form-group">
                                {!! Form::label('display_value', 'Описание') !!}
                                {!! Form::textarea('display_value', old('display_value', $row->display_value ?? null), ['rows' => "3", 'placeholder' => "Описание", 'class' => 'form-control']) !!}
                                @if ($errors->has('display_value'))
                                    <p class="text-danger">{{ $errors->first('display_value') }}</p>
                                @endif
                            </div>

                            <div class="form-check">
                                {!! Form::checkbox('published', 1, isset($row) ? ($row->published): 1, ['class' => 'form-check-input']) !!}
                                {!! Form::label('published', 'Публиковать', ['class' => 'form-check-label']) !!}
                                @if ($errors->has('published'))
                                    <p class="text-danger">{{ $errors->first('published') }}</p>
                                @endif
                            </div>

                        </div>
                        <!-- /.card-body -->

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                {{ isset($row) ? 'редактировать' : 'добавить' }}
                            </button>
                            <a class="btn btn-default float-sm-right" href="{{ route('admin.settings.index') }}">
                                назад
                            </a>
                        </div>

                        {!! Form::close() !!}

                    </header>

                </div>
                <!-- /.card -->
            </div>
        </div>

    </section>
    <!-- /.content -->

@endsection

@section('js')

    <!-- Summernote -->
    {!! Html::script('/plugins/summernote/summernote-bs4.min.js') !!}

    <!-- CodeMirror -->
    {!! Html::script('/plugins/codemirror/codemirror.js') !!}
    {!! Html::script('/plugins/codemirror/mode/css/css.js') !!}
    {!! Html::script('/plugins/codemirror/mode/xml/xml.js') !!}
    {!! Html::script('/plugins/codemirror/mode/htmlmixed/htmlmixed.js') !!}
    {!! Html::script('/plugins/bs-custom-file-input/bs-custom-file-input.min.js') !!}
    {!! Html::script('/plugins/bs-custom-file-input/bs-custom-file-input.min.js') !!}

    <!-- Page specific script -->
    <script>
        $(function () {
            // Summernote
            $('#summernote').summernote()
            bsCustomFileInput.init();
        })
    </script>

@endsection
