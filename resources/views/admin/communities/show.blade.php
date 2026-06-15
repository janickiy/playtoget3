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
                                <dt class="col-sm-3">Аватарка</dt>
                                <dd class="col-sm-9">
                                    <img src="{{ $avatarUrl }}" alt="Аватар комьюнити" class="img-thumbnail" style="width: 140px; height: 140px; object-fit: cover;">
                                </dd>

                                <dt class="col-sm-3">ID</dt>
                                <dd class="col-sm-9">{{ $row->id }}</dd>

                                <dt class="col-sm-3">Тип</dt>
                                <dd class="col-sm-9">{{ $typeLabel }}</dd>

                                <dt class="col-sm-3">Название</dt>
                                <dd class="col-sm-9">{{ $row->name }}</dd>

                                <dt class="col-sm-3">Место</dt>
                                <dd class="col-sm-9">{{ $row->place }}</dd>

                                <dt class="col-sm-3">Вид спорта</dt>
                                <dd class="col-sm-9">{{ $row->sport_type }}</dd>

                                <dt class="col-sm-3">Аватар</dt>
                                <dd class="col-sm-9">{{ $row->avatar }}</dd>

                                <dt class="col-sm-3">Обложка</dt>
                                <dd class="col-sm-9">{{ $row->cover_page }}</dd>

                                <dt class="col-sm-3">Статус</dt>
                                <dd class="col-sm-9">{{ $statusLabel }}</dd>

                                <dt class="col-sm-3">Рекомендовано</dt>
                                <dd class="col-sm-9">{{ (int) $row->recommended === 1 ? 'да' : 'нет' }}</dd>

                                <dt class="col-sm-3">Создано</dt>
                                <dd class="col-sm-9">{{ optional($row->created_at)->format('d/m/Y H:i') }}</dd>

                                <dt class="col-sm-3">Обновлено</dt>
                                <dd class="col-sm-9">{{ optional($row->updated_at)->format('d/m/Y H:i') }}</dd>

                                <dt class="col-sm-3">Описание</dt>
                                <dd class="col-sm-9">{{ $row->about }}</dd>
                            </dl>
                        </div>

                        <div class="card-footer">
                            <a class="btn btn-primary" href="{{ route('admin.communities.edit', ['id' => $row->id]) }}">
                                редактировать
                            </a>
                            <a class="btn btn-default float-sm-right" href="{{ route('admin.communities.index') }}">
                                назад
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection
