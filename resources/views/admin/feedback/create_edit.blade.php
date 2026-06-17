@extends('app')

@section('title', $title)

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <header class="card card-primary">
                        {!! Form::open(['url' => route('admin.feedback.update'), 'method' => 'put']) !!}

                        {!! Form::hidden('id', $row->id) !!}

                        <div class="card-body">
                            <dl class="row">
                                <dt class="col-sm-3">Subject</dt>
                                <dd class="col-sm-9">{{ $row->subject }}</dd>

                                <dt class="col-sm-3">Name</dt>
                                <dd class="col-sm-9">{{ $row->name }}</dd>

                                <dt class="col-sm-3">Email</dt>
                                <dd class="col-sm-9">{{ $row->email }}</dd>

                                <dt class="col-sm-3">Message</dt>
                                <dd class="col-sm-9">{!! nl2br(e((string) $row->message)) !!}</dd>
                            </dl>

                            <div class="form-group">
                                {!! Form::label('status', 'Status*') !!}
                                {!! Form::select('status', $statusOptions, old('status', $row->status), ['class' => 'custom-select']) !!}
                                @if ($errors->has('status'))
                                    <p class="text-danger">{{ $errors->first('status') }}</p>
                                @endif
                            </div>

                            <div class="form-group">
                                {!! Form::label('answer', 'Answer') !!}
                                {!! Form::textarea('answer', old('answer', $row->answer), ['class' => 'form-control', 'rows' => 5]) !!}
                                @if ($errors->has('answer'))
                                    <p class="text-danger">{{ $errors->first('answer') }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                edit
                            </button>
                            <a class="btn btn-default float-sm-right" href="{{ route('admin.feedback.index') }}">
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
