@extends('app')

@section('title', $title)

@section('content')

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <header class="card card-primary">
                        {!! Form::open(['url' => route('admin.communities.update'), 'method' => 'put']) !!}

                        {!! Form::hidden('id', $row->id) !!}

                        <div class="card-body">
                            <p>*-обязательные поля</p>

                            <div class="form-group">
                                {!! Form::label('current_avatar', 'Текущая аватарка') !!}
                                <div>
                                    <img src="{{ $avatarUrl }}" alt="Аватар комьюнити" class="img-thumbnail" style="width: 140px; height: 140px; object-fit: cover;">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {!! Form::label('type', 'Тип*') !!}
                                        {!! Form::select('type', $typeOptions, old('type', $row->type ?? 'team'), ['class' => 'custom-select']) !!}
                                        @if ($errors->has('type'))
                                            <p class="text-danger">{{ $errors->first('type') }}</p>
                                        @endif
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        {!! Form::label('name', 'Название*') !!}
                                        {!! Form::text('name', old('name', $row->name ?? null), ['class' => 'form-control']) !!}
                                        @if ($errors->has('name'))
                                            <p class="text-danger">{{ $errors->first('name') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {!! Form::label('place', 'Место') !!}
                                        {!! Form::text('place', old('place', $row->place ?? null), ['class' => 'form-control']) !!}
                                        @if ($errors->has('place'))
                                            <p class="text-danger">{{ $errors->first('place') }}</p>
                                        @endif
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        {!! Form::label('sport_type', 'Вид спорта') !!}
                                        {!! Form::text('sport_type', old('sport_type', $row->sport_type ?? null), ['class' => 'form-control']) !!}
                                        @if ($errors->has('sport_type'))
                                            <p class="text-danger">{{ $errors->first('sport_type') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {!! Form::label('avatar', 'Аватар') !!}
                                        {!! Form::text('avatar', old('avatar', $row->avatar ?? null), ['class' => 'form-control']) !!}
                                        @if ($errors->has('avatar'))
                                            <p class="text-danger">{{ $errors->first('avatar') }}</p>
                                        @endif
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        {!! Form::label('cover_page', 'Обложка') !!}
                                        {!! Form::text('cover_page', old('cover_page', $row->cover_page ?? null), ['class' => 'form-control']) !!}
                                        @if ($errors->has('cover_page'))
                                            <p class="text-danger">{{ $errors->first('cover_page') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                {!! Form::label('about', 'Описание') !!}
                                {!! Form::textarea('about', old('about', $row->about ?? null), ['class' => 'form-control', 'rows' => 5]) !!}
                                @if ($errors->has('about'))
                                    <p class="text-danger">{{ $errors->first('about') }}</p>
                                @endif
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {!! Form::label('status', 'Статус*') !!}
                                        {!! Form::select('status', $statusOptions, old('status', $row->status ?? \App\Enums\CommunityStatus::New->value), ['class' => 'custom-select']) !!}
                                        @if ($errors->has('status'))
                                            <p class="text-danger">{{ $errors->first('status') }}</p>
                                        @endif
                                    </div>

                                    <div class="form-group">
                                        {!! Form::hidden('recommended', 0) !!}
                                        <div class="custom-control custom-checkbox">
                                            {!! Form::checkbox('recommended', 1, (int) old('recommended', (int) ($row->recommended ?? 0)) === 1, ['class' => 'custom-control-input', 'id' => 'recommended']) !!}
                                            {!! Form::label('recommended', 'Рекомендовано', ['class' => 'custom-control-label']) !!}
                                        </div>
                                        @if ($errors->has('recommended'))
                                            <p class="text-danger">{{ $errors->first('recommended') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                редактировать
                            </button>
                            <a class="btn btn-default float-sm-right" href="{{ route('admin.communities.index') }}">
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
