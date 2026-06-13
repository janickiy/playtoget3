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

                                <dt class="col-sm-3">Email</dt>
                                <dd class="col-sm-9">{{ $row->email }}</dd>

                                <dt class="col-sm-3">Имя</dt>
                                <dd class="col-sm-9">{{ $row->displayName() }}</dd>

                                <dt class="col-sm-3">Пол</dt>
                                <dd class="col-sm-9">{{ $row->sex === 'female' ? 'Женский' : ($row->sex === 'male' ? 'Мужской' : '') }}</dd>

                                <dt class="col-sm-3">Дата рождения</dt>
                                <dd class="col-sm-9">{{ optional($row->birthday)->format('d/m/Y') }}</dd>

                                <dt class="col-sm-3">Телефон</dt>
                                <dd class="col-sm-9">{{ $row->phone }}</dd>

                                <dt class="col-sm-3">Контактный email</dt>
                                <dd class="col-sm-9">{{ $row->contact_email }}</dd>

                                <dt class="col-sm-3">Telegram</dt>
                                <dd class="col-sm-9">{{ $row->telegram }}</dd>

                                <dt class="col-sm-3">WhatsApp</dt>
                                <dd class="col-sm-9">{{ $row->whatsapp }}</dd>

                                <dt class="col-sm-3">Viber</dt>
                                <dd class="col-sm-9">{{ $row->viber }}</dd>

                                <dt class="col-sm-3">Сайт</dt>
                                <dd class="col-sm-9">{{ $row->website }}</dd>

                                <dt class="col-sm-3">Город</dt>
                                <dd class="col-sm-9">{{ $row->city }}</dd>

                                <dt class="col-sm-3">Статус</dt>
                                <dd class="col-sm-9">{{ $row->statusEnum()->label() }}</dd>

                                <dt class="col-sm-3">Дата подтверждения</dt>
                                <dd class="col-sm-9">{{ optional($row->confirmed_at)->format('d/m/Y H:i') }}</dd>

                                <dt class="col-sm-3">Создан</dt>
                                <dd class="col-sm-9">{{ optional($row->created_at)->format('d/m/Y H:i') }}</dd>

                                <dt class="col-sm-3">Обновлен</dt>
                                <dd class="col-sm-9">{{ optional($row->updated_at)->format('d/m/Y H:i') }}</dd>

                                <dt class="col-sm-3">О себе</dt>
                                <dd class="col-sm-9">{{ $row->about }}</dd>

                                <dt class="col-sm-3">О спорте</dt>
                                <dd class="col-sm-9">{{ $row->about_sport }}</dd>
                            </dl>
                        </div>

                        <div class="card-footer">
                            <a class="btn btn-primary" href="{{ route('admin.users.edit', ['id' => $row->id]) }}">
                                редактировать
                            </a>
                            <a class="btn btn-default float-sm-right" href="{{ route('admin.users.index') }}">
                                назад
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection
