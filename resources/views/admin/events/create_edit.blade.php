@extends('app')

@section('title', $title)

@section('content')

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <header class="card card-primary">
                        {!! Form::open(['url' => route('admin.events.update'), 'method' => 'put']) !!}

                        {!! Form::hidden('id', $row->id) !!}

                        <div class="card-body">
                            <p>* required fields</p>

                            <div class="form-group">
                                {!! Form::label('current_cover', 'Current cover') !!}
                                <div>
                                    <img src="{{ $coverUrl }}" alt="Event cover" class="img-thumbnail" style="width: 180px; height: 120px; object-fit: cover;">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {!! Form::label('name', 'Name*') !!}
                                        {!! Form::text('name', old('name', $row->name ?? null), ['class' => 'form-control']) !!}
                                        @if ($errors->has('name'))
                                            <p class="text-danger">{{ $errors->first('name') }}</p>
                                        @endif
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        {!! Form::label('status', 'Status*') !!}
                                        {!! Form::select('status', $statusOptions, old('status', $row->status ?? \App\Enums\EventStatus::New->value), ['class' => 'custom-select']) !!}
                                        @if ($errors->has('status'))
                                            <p class="text-danger">{{ $errors->first('status') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {!! Form::label('date_from', 'Start') !!}
                                        {!! Form::input('datetime-local', 'date_from', old('date_from', $row->date_from ? $row->date_from->format('Y-m-d\TH:i') : null), ['class' => 'form-control']) !!}
                                        @if ($errors->has('date_from'))
                                            <p class="text-danger">{{ $errors->first('date_from') }}</p>
                                        @endif
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        {!! Form::label('date_to', 'End') !!}
                                        {!! Form::input('datetime-local', 'date_to', old('date_to', $row->date_to ? $row->date_to->format('Y-m-d\TH:i') : null), ['class' => 'form-control']) !!}
                                        @if ($errors->has('date_to'))
                                            <p class="text-danger">{{ $errors->first('date_to') }}</p>
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

                            <div class="form-group">
                                {!! Form::label('cover_page', 'Cover') !!}
                                {!! Form::text('cover_page', old('cover_page', $row->cover_page ?? null), ['class' => 'form-control']) !!}
                                @if ($errors->has('cover_page'))
                                    <p class="text-danger">{{ $errors->first('cover_page') }}</p>
                                @endif
                            </div>

                            <div class="form-group">
                                {!! Form::label('address', 'Address') !!}
                                {!! Form::textarea('address', old('address', $row->address ?? null), ['class' => 'form-control', 'rows' => 3]) !!}
                                @if ($errors->has('address'))
                                    <p class="text-danger">{{ $errors->first('address') }}</p>
                                @endif
                            </div>

                            <div class="form-group">
                                {!! Form::label('description', 'Description') !!}
                                {!! Form::textarea('description', old('description', $row->description ?? null), ['class' => 'form-control', 'rows' => 5]) !!}
                                @if ($errors->has('description'))
                                    <p class="text-danger">{{ $errors->first('description') }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                edit
                            </button>
                            <a class="btn btn-default float-sm-right" href="{{ route('admin.events.index') }}">
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
