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
                                <dt class="col-sm-3">Avatar</dt>
                                <dd class="col-sm-9">
                                    <img
                                        src="{{ \App\Helpers\FrontAssets::adminUserAvatar($row) }}"
                                        alt="User avatar"
                                        class="img-thumbnail"
                                        style="width: 120px; height: 120px; object-fit: cover;"
                                    >
                                </dd>

                                <dt class="col-sm-3">ID</dt>
                                <dd class="col-sm-9">{{ $row->id }}</dd>

                                <dt class="col-sm-3">Email</dt>
                                <dd class="col-sm-9">{{ $row->email }}</dd>

                                <dt class="col-sm-3">Name</dt>
                                <dd class="col-sm-9">{{ $row->displayName() }}</dd>

                                <dt class="col-sm-3">Gender</dt>
                                <dd class="col-sm-9">{{ $row->sex === 'female' ? 'Female' : ($row->sex === 'male' ? 'Male' : '') }}</dd>

                                <dt class="col-sm-3">Date of birth</dt>
                                <dd class="col-sm-9">{{ optional($row->birthday)->format('d/m/Y') }}</dd>

                                <dt class="col-sm-3">Phone</dt>
                                <dd class="col-sm-9">{{ $row->phone }}</dd>

                                <dt class="col-sm-3">Contact email</dt>
                                <dd class="col-sm-9">{{ $row->contact_email }}</dd>

                                <dt class="col-sm-3">Telegram</dt>
                                <dd class="col-sm-9">{{ $row->telegram }}</dd>

                                <dt class="col-sm-3">WhatsApp</dt>
                                <dd class="col-sm-9">{{ $row->whatsapp }}</dd>

                                <dt class="col-sm-3">Viber</dt>
                                <dd class="col-sm-9">{{ $row->viber }}</dd>

                                <dt class="col-sm-3">Website</dt>
                                <dd class="col-sm-9">{{ $row->website }}</dd>

                                <dt class="col-sm-3">City</dt>
                                <dd class="col-sm-9">{{ $row->city }}</dd>

                                <dt class="col-sm-3">Status</dt>
                                <dd class="col-sm-9">{{ $row->statusEnum()->label() }}</dd>

                                <dt class="col-sm-3">Confirmation date</dt>
                                <dd class="col-sm-9">{{ optional($row->confirmed_at)->format('d/m/Y H:i') }}</dd>

                                <dt class="col-sm-3">Created</dt>
                                <dd class="col-sm-9">{{ optional($row->created_at)->format('d/m/Y H:i') }}</dd>

                                <dt class="col-sm-3">Updated</dt>
                                <dd class="col-sm-9">{{ optional($row->updated_at)->format('d/m/Y H:i') }}</dd>

                                <dt class="col-sm-3">About me</dt>
                                <dd class="col-sm-9">{{ $row->about }}</dd>

                                <dt class="col-sm-3">About sport</dt>
                                <dd class="col-sm-9">{{ $row->about_sport }}</dd>
                            </dl>
                        </div>

                        <div class="card-footer">
                            <a class="btn btn-primary" href="{{ route('admin.users.edit', ['id' => $row->id]) }}">
                                edit
                            </a>
                            <a class="btn btn-default float-sm-right" href="{{ route('admin.users.index') }}">
                                back
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection
