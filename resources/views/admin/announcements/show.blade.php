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
                                <dt class="col-sm-3">ID</dt>
                                <dd class="col-sm-9">{{ $row->id }}</dd>

                                <dt class="col-sm-3">Name</dt>
                                <dd class="col-sm-9">{{ $row->title }}</dd>

                                <dt class="col-sm-3">Slug</dt>
                                <dd class="col-sm-9">{{ $row->slug }}</dd>

                                <dt class="col-sm-3">Published</dt>
                                <dd class="col-sm-9">{{ $row->published ? 'yes' : 'no' }}</dd>

                                <dt class="col-sm-3">Created</dt>
                                <dd class="col-sm-9">{{ optional($row->created_at)->format('d/m/Y H:i') }}</dd>

                                <dt class="col-sm-3">Updated</dt>
                                <dd class="col-sm-9">{{ optional($row->updated_at)->format('d/m/Y H:i') }}</dd>

                                <dt class="col-sm-3">Content</dt>
                                <dd class="col-sm-9">{!! $row->text !!}</dd>
                            </dl>
                        </div>

                        <div class="card-footer">
                            <a class="btn btn-primary" href="{{ route('admin.announcements.edit', ['id' => $row->id]) }}">
                                edit
                            </a>
                            <a class="btn btn-info" href="{{ route('front.announcements.show', ['slug' => $row->slug]) }}" target="_blank">
                                open on site
                            </a>
                            <a class="btn btn-default float-sm-right" href="{{ route('admin.announcements.index') }}">
                                back
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
