@extends('app')

@section('title', $title)

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card card-primary">
                        {!! Form::open(['url' => isset($row) ? route('admin.sport-levels.update') : route('admin.sport-levels.store'), 'method' => isset($row) ? 'put' : 'post']) !!}

                        {!! isset($row) ? Form::hidden('id', $row->id) : '' !!}

                        <div class="card-body">
                            <p>{{ __('admin.common.required_fields') }}</p>

                            <div class="form-group">
                                {!! Form::label('name', __('admin.fields.name') . '*') !!}
                                {!! Form::text('name', old('name', $row->name ?? null), ['class' => 'form-control']) !!}
                                @if ($errors->has('name'))
                                    <p class="text-danger">{{ $errors->first('name') }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                {{ isset($row) ? __('admin.buttons.update') : __('admin.buttons.create') }}
                            </button>
                            <a class="btn btn-default float-sm-right" href="{{ route('admin.sport-levels.index') }}">
                                {{ __('admin.actions.back') }}
                            </a>
                        </div>

                        {!! Form::close() !!}
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
