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

                                <dt class="col-sm-3">Тема</dt>
                                <dd class="col-sm-9">{{ $row->subject }}</dd>

                                <dt class="col-sm-3">Имя</dt>
                                <dd class="col-sm-9">{{ $row->name }}</dd>

                                <dt class="col-sm-3">Email</dt>
                                <dd class="col-sm-9">{{ $row->email }}</dd>

                                <dt class="col-sm-3">Статус</dt>
                                <dd class="col-sm-9">{{ $row->statusLabel() }}</dd>

                                <dt class="col-sm-3">Дата</dt>
                                <dd class="col-sm-9">{{ optional($row->time)->format('d/m/Y H:i') }}</dd>

                                <dt class="col-sm-3">Сообщение</dt>
                                <dd class="col-sm-9">{!! nl2br(e((string) $row->message)) !!}</dd>

                                <dt class="col-sm-3">Ответ</dt>
                                <dd class="col-sm-9">{!! nl2br(e((string) $row->answer)) !!}</dd>
                            </dl>
                        </div>

                        <div class="card-footer">
                            <a class="btn btn-primary" href="{{ route('admin.feedback.edit', ['id' => $row->id]) }}">
                                редактировать
                            </a>
                            <a class="btn btn-default float-sm-right" href="{{ route('admin.feedback.index') }}">
                                назад
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
