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
                            <p>* required fields</p>

                            <div class="form-group">
                                {!! Form::label('current_avatar', 'Current avatar') !!}
                                <div>
                                    <img src="{{ $avatarUrl }}" alt="Community avatar" class="img-thumbnail" style="width: 140px; height: 140px; object-fit: cover;">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {!! Form::label('type', 'Type*') !!}
                                        {!! Form::select('type', $typeOptions, old('type', $row->type ?? 'team'), ['class' => 'custom-select']) !!}
                                        @if ($errors->has('type'))
                                            <p class="text-danger">{{ $errors->first('type') }}</p>
                                        @endif
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        {!! Form::label('name', 'Name*') !!}
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
                                        {!! Form::label('place', 'Place') !!}
                                        {!! Form::text('place', old('place', $row->place ?? null), ['class' => 'form-control']) !!}
                                        @if ($errors->has('place'))
                                            <p class="text-danger">{{ $errors->first('place') }}</p>
                                        @endif
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        {!! Form::label('sport_type', 'Sport type') !!}
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
                                        {!! Form::label('avatar', 'Avatar') !!}
                                        {!! Form::text('avatar', old('avatar', $row->avatar ?? null), ['class' => 'form-control']) !!}
                                        @if ($errors->has('avatar'))
                                            <p class="text-danger">{{ $errors->first('avatar') }}</p>
                                        @endif
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        {!! Form::label('cover_page', 'Cover') !!}
                                        {!! Form::text('cover_page', old('cover_page', $row->cover_page ?? null), ['class' => 'form-control']) !!}
                                        @if ($errors->has('cover_page'))
                                            <p class="text-danger">{{ $errors->first('cover_page') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                {!! Form::label('about', 'Description') !!}
                                {!! Form::textarea('about', old('about', $row->about ?? null), ['class' => 'form-control', 'rows' => 5]) !!}
                                @if ($errors->has('about'))
                                    <p class="text-danger">{{ $errors->first('about') }}</p>
                                @endif
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {!! Form::label('status', 'Status*') !!}
                                        {!! Form::select('status', $statusOptions, old('status', $row->status ?? \App\Enums\CommunityStatus::New->value), ['class' => 'custom-select']) !!}
                                        @if ($errors->has('status'))
                                            <p class="text-danger">{{ $errors->first('status') }}</p>
                                        @endif
                                    </div>

                                    <div class="form-group">
                                        {!! Form::hidden('recommended', 0) !!}
                                        <div class="custom-control custom-checkbox">
                                            {!! Form::checkbox('recommended', 1, (int) old('recommended', (int) ($row->recommended ?? 0)) === 1, ['class' => 'custom-control-input', 'id' => 'recommended']) !!}
                                            {!! Form::label('recommended', 'Recommended', ['class' => 'custom-control-label']) !!}
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
                                edit
                            </button>
                            <a class="btn btn-default float-sm-right" href="{{ route('admin.communities.index') }}">
                                back
                            </a>
                        </div>

                        {!! Form::close() !!}
                    </header>
                </div>
            </div>
        </div>
    </section>

@endsection
