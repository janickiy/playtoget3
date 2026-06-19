@extends('app')

@section('title', $title)

@section('content')

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <header class="card card-primary">
                        {!! Form::open(['url' => route('admin.users.update'), 'method' => 'put']) !!}
                        {!! Form::hidden('id', $row->id) !!}

                        <div class="card-body">
                            <p>* required fields</p>

                            <div class="form-group">
                                <label>Current avatar</label>
                                <div>
                                    <img
                                        src="{{ \App\Helpers\FrontAssets::adminUserAvatar($row) }}"
                                        alt="User avatar"
                                        class="img-thumbnail"
                                        style="width: 120px; height: 120px; object-fit: cover;"
                                    >
                                </div>
                            </div>

                            <div class="form-group">
                                {!! Form::label('email', 'Email*') !!}
                                {!! Form::text('email', old('email', $row->email), ['class' => 'form-control', 'placeholder' => 'Email']) !!}
                                @if ($errors->has('email'))
                                    <p class="text-danger">{{ $errors->first('email') }}</p>
                                @endif
                            </div>

                            <div class="form-group">
                                {!! Form::label('password', 'New password') !!}
                                {!! Form::password('password', ['class' => 'form-control']) !!}
                                @if ($errors->has('password'))
                                    <p class="text-danger">{{ $errors->first('password') }}</p>
                                @endif
                            </div>

                            <div class="form-group">
                                {!! Form::label('password_again', 'Repeat password') !!}
                                {!! Form::password('password_again', ['class' => 'form-control']) !!}
                                @if ($errors->has('password_again'))
                                    <p class="text-danger">{{ $errors->first('password_again') }}</p>
                                @endif
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        {!! Form::label('firstname', 'Name') !!}
                                        {!! Form::text('firstname', old('firstname', $row->firstname), ['class' => 'form-control']) !!}
                                        @if ($errors->has('firstname'))
                                            <p class="text-danger">{{ $errors->first('firstname') }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        {!! Form::label('lastname', 'Last name') !!}
                                        {!! Form::text('lastname', old('lastname', $row->lastname), ['class' => 'form-control']) !!}
                                        @if ($errors->has('lastname'))
                                            <p class="text-danger">{{ $errors->first('lastname') }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        {!! Form::label('nickname', 'Nickname') !!}
                                        {!! Form::text('nickname', old('nickname', $row->nickname), ['class' => 'form-control']) !!}
                                        @if ($errors->has('nickname'))
                                            <p class="text-danger">{{ $errors->first('nickname') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        {!! Form::label('sex', 'Gender') !!}
                                        {!! Form::select('sex', ['male' => 'Male', 'female' => 'Female'], old('sex', $row->sex), ['class' => 'custom-select', 'placeholder' => 'not specified']) !!}
                                        @if ($errors->has('sex'))
                                            <p class="text-danger">{{ $errors->first('sex') }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        {!! Form::label('birthday', 'Date of birth') !!}
                                        {!! Form::date('birthday', old('birthday', $row->birthday ? $row->birthday->format('Y-m-d') : null), ['class' => 'form-control']) !!}
                                        @if ($errors->has('birthday'))
                                            <p class="text-danger">{{ $errors->first('birthday') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        {!! Form::label('phone', 'Phone') !!}
                                        {!! Form::text('phone', old('phone', $row->phone), ['class' => 'form-control']) !!}
                                        @if ($errors->has('phone'))
                                            <p class="text-danger">{{ $errors->first('phone') }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        {!! Form::label('contact_email', 'Contact email') !!}
                                        {!! Form::text('contact_email', old('contact_email', $row->contact_email), ['class' => 'form-control']) !!}
                                        @if ($errors->has('contact_email'))
                                            <p class="text-danger">{{ $errors->first('contact_email') }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        {!! Form::label('website', 'Website') !!}
                                        {!! Form::text('website', old('website', $row->website), ['class' => 'form-control']) !!}
                                        @if ($errors->has('website'))
                                            <p class="text-danger">{{ $errors->first('website') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        {!! Form::label('telegram', 'Telegram') !!}
                                        {!! Form::text('telegram', old('telegram', $row->telegram), ['class' => 'form-control']) !!}
                                        @if ($errors->has('telegram'))
                                            <p class="text-danger">{{ $errors->first('telegram') }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        {!! Form::label('whatsapp', 'WhatsApp') !!}
                                        {!! Form::text('whatsapp', old('whatsapp', $row->whatsapp), ['class' => 'form-control']) !!}
                                        @if ($errors->has('whatsapp'))
                                            <p class="text-danger">{{ $errors->first('whatsapp') }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        {!! Form::label('viber', 'Viber') !!}
                                        {!! Form::text('viber', old('viber', $row->viber), ['class' => 'form-control']) !!}
                                        @if ($errors->has('viber'))
                                            <p class="text-danger">{{ $errors->first('viber') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        {!! Form::label('country', 'Country') !!}
                                        {!! Form::text('country', old('country', $row->country), ['class' => 'form-control']) !!}
                                        @if ($errors->has('country'))
                                            <p class="text-danger">{{ $errors->first('country') }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        {!! Form::label('region', 'Region') !!}
                                        {!! Form::text('region', old('region', $row->region), ['class' => 'form-control']) !!}
                                        @if ($errors->has('region'))
                                            <p class="text-danger">{{ $errors->first('region') }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        {!! Form::label('city', 'City') !!}
                                        {!! Form::text('city', old('city', $row->city), ['class' => 'form-control']) !!}
                                        @if ($errors->has('city'))
                                            <p class="text-danger">{{ $errors->first('city') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                {!! Form::label('about', 'About me') !!}
                                {!! Form::textarea('about', old('about', $row->about), ['class' => 'form-control', 'rows' => 4]) !!}
                                @if ($errors->has('about'))
                                    <p class="text-danger">{{ $errors->first('about') }}</p>
                                @endif
                            </div>

                            <div class="form-group">
                                {!! Form::label('about_sport', 'About sport') !!}
                                {!! Form::textarea('about_sport', old('about_sport', $row->about_sport), ['class' => 'form-control', 'rows' => 4]) !!}
                                @if ($errors->has('about_sport'))
                                    <p class="text-danger">{{ $errors->first('about_sport') }}</p>
                                @endif
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {!! Form::label('status', 'Status*') !!}
                                        {!! Form::select('status', $statusOptions, old('status', $row->status), ['class' => 'custom-select']) !!}
                                        @if ($errors->has('status'))
                                            <p class="text-danger">{{ $errors->first('status') }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {!! Form::label('confirmed_at', 'Confirmation date') !!}
                                        {!! Form::datetimeLocal('confirmed_at', old('confirmed_at', $row->confirmed_at ? $row->confirmed_at->format('Y-m-d\TH:i') : null), ['class' => 'form-control']) !!}
                                        @if ($errors->has('confirmed_at'))
                                            <p class="text-danger">{{ $errors->first('confirmed_at') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">edit</button>
                            <a class="btn btn-default float-sm-right" href="{{ route('admin.users.index') }}">
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
