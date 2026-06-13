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
                                    <img src="{{ $avatarUrl }}" alt="Аватар спортивного блока" class="img-thumbnail" style="width: 140px; height: 140px; object-fit: cover;">
                                </dd>

                                <dt class="col-sm-3">ID</dt>
                                <dd class="col-sm-9">{{ $row->id }}</dd>

                                <dt class="col-sm-3">Тип</dt>
                                <dd class="col-sm-9">{{ $typeLabel }}</dd>

                                <dt class="col-sm-3">Название</dt>
                                <dd class="col-sm-9">{{ $row->name }}</dd>

                                <dt class="col-sm-3">Место</dt>
                                <dd class="col-sm-9">{{ $row->place }}</dd>

                                <dt class="col-sm-3">Адрес</dt>
                                <dd class="col-sm-9">{{ $row->address }}</dd>

                                <dt class="col-sm-3">Телефон</dt>
                                <dd class="col-sm-9">{{ $row->phone }}</dd>

                                <dt class="col-sm-3">Email</dt>
                                <dd class="col-sm-9">{{ $row->email }}</dd>

                                <dt class="col-sm-3">Сайт</dt>
                                <dd class="col-sm-9">{{ $row->website }}</dd>

                                <dt class="col-sm-3">Аватар</dt>
                                <dd class="col-sm-9">{{ $row->avatar }}</dd>

                                <dt class="col-sm-3">ID владельца</dt>
                                <dd class="col-sm-9">{{ $row->owner_id }}</dd>

                                <dt class="col-sm-3">Активен</dt>
                                <dd class="col-sm-9">{{ $row->active ? 'да' : 'нет' }}</dd>

                                <dt class="col-sm-3">Статус</dt>
                                <dd class="col-sm-9">{{ $statusLabel }}</dd>

                                <dt class="col-sm-3">Создано</dt>
                                <dd class="col-sm-9">{{ optional($row->created_at)->format('d/m/Y H:i') }}</dd>

                                <dt class="col-sm-3">Обновлено</dt>
                                <dd class="col-sm-9">{{ optional($row->updated_at)->format('d/m/Y H:i') }}</dd>

                                <dt class="col-sm-3">Описание</dt>
                                <dd class="col-sm-9">{{ $row->about }}</dd>
                            </dl>
                        </div>

                        <div class="card-footer">
                            <a class="btn btn-primary" href="{{ route('admin.sport-blocks.edit', ['id' => $row->id]) }}">
                                редактировать
                            </a>
                            <a class="btn btn-default float-sm-right" href="{{ route('admin.sport-blocks.index') }}">
                                назад
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection
