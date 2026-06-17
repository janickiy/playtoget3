@extends('app')

@section('title', $title)

@section('content')

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <header class="card card-primary">
                        {!! Form::open(['url' => route('admin.sport-blocks.update'), 'method' => 'put']) !!}

                        {!! Form::hidden('id', $row->id) !!}
                        {!! Form::hidden('avatar', $row->avatar ?? '') !!}
                        {!! Form::hidden('owner_id', $row->owner_id ?? '') !!}

                        <div class="card-body">
                            <p>* required fields</p>

                            <div class="form-group">
                                {!! Form::label('current_avatar', 'Current avatar') !!}
                                <div>
                                    <img src="{{ $avatarUrl }}" alt="Sport block avatar" class="img-thumbnail" style="width: 140px; height: 140px; object-fit: cover;">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {!! Form::label('type', 'Type*') !!}
                                        {!! Form::select('type', $typeOptions, old('type', $row->type ?? 'playground'), ['class' => 'custom-select']) !!}
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
                                        {!! Form::label('address', 'Address') !!}
                                        {!! Form::text('address', old('address', $row->address ?? null), ['class' => 'form-control']) !!}
                                        @if ($errors->has('address'))
                                            <p class="text-danger">{{ $errors->first('address') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {!! Form::label('phone', 'Phone') !!}
                                        {!! Form::text('phone', old('phone', $row->phone ?? null), ['class' => 'form-control']) !!}
                                        @if ($errors->has('phone'))
                                            <p class="text-danger">{{ $errors->first('phone') }}</p>
                                        @endif
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        {!! Form::label('email', 'Email') !!}
                                        {!! Form::text('email', old('email', $row->email ?? null), ['class' => 'form-control']) !!}
                                        @if ($errors->has('email'))
                                            <p class="text-danger">{{ $errors->first('email') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        {!! Form::label('website', 'Website') !!}
                                        {!! Form::text('website', old('website', $row->website ?? null), ['class' => 'form-control']) !!}
                                        @if ($errors->has('website'))
                                            <p class="text-danger">{{ $errors->first('website') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {!! Form::label('status', 'Status*') !!}
                                        {!! Form::select('status', $statusOptions, old('status', $row->status ?? \App\Enums\SportBlockStatus::New->value), ['class' => 'custom-select']) !!}
                                        @if ($errors->has('status'))
                                            <p class="text-danger">{{ $errors->first('status') }}</p>
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

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                edit
                            </button>
                            <a class="btn btn-default float-sm-right" href="{{ route('admin.sport-blocks.index') }}">
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
