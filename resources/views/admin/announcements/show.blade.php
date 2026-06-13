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

                                <dt class="col-sm-3">Название</dt>
                                <dd class="col-sm-9">{{ $row->title }}</dd>

                                <dt class="col-sm-3">ЧПУ</dt>
                                <dd class="col-sm-9">{{ $row->slug }}</dd>

                                <dt class="col-sm-3">Опубликовано</dt>
                                <dd class="col-sm-9">{{ $row->published ? 'да' : 'нет' }}</dd>

                                <dt class="col-sm-3">Создано</dt>
                                <dd class="col-sm-9">{{ optional($row->created_at)->format('d/m/Y H:i') }}</dd>

                                <dt class="col-sm-3">Обновлено</dt>
                                <dd class="col-sm-9">{{ optional($row->updated_at)->format('d/m/Y H:i') }}</dd>

                                <dt class="col-sm-3">Содержание</dt>
                                <dd class="col-sm-9">{!! $row->text !!}</dd>
                            </dl>
                        </div>

                        <div class="card-footer">
                            <a class="btn btn-primary" href="{{ route('admin.announcements.edit', ['id' => $row->id]) }}">
                                редактировать
                            </a>
                            <a class="btn btn-info" href="{{ route('front.announcements.show', ['slug' => $row->slug]) }}" target="_blank">
                                открыть на сайте
                            </a>
                            <a class="btn btn-default float-sm-right" href="{{ route('admin.announcements.index') }}">
                                назад
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
