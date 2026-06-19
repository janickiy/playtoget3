@extends('app')

@section('title', $title)

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <dl class="row">
                                <dt class="col-sm-3">{{ __('admin.fields.id') }}</dt>
                                <dd class="col-sm-9">{{ $row->id }}</dd>

                                <dt class="col-sm-3">{{ __('admin.fields.name') }}</dt>
                                <dd class="col-sm-9">{{ $row->name }}</dd>

                                <dt class="col-sm-3">{{ __('admin.fields.parent') }}</dt>
                                <dd class="col-sm-9">{{ $row->parent?->name ?: __('admin.fields.no_parent') }}</dd>
                            </dl>
                        </div>

                        <div class="card-footer">
                            <a class="btn btn-primary" href="{{ route('admin.sport-types.edit', ['id' => $row->id]) }}">
                                {{ __('admin.actions.edit') }}
                            </a>
                            <a class="btn btn-default float-sm-right" href="{{ route('admin.sport-types.index') }}">
                                {{ __('admin.actions.back') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
