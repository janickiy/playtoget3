@extends('errors.layout')

@section('title', 'Registration')

@section('styles')
    <style>
        .auth-card {
            width: min(520px, calc(100% - 32px));
            margin: 20px auto 80px;
            padding: 34px 42px 38px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 18px 42px rgba(0, 0, 0, .28);
            color: #2d3045;
        }

        .auth-card h1 {
            margin: 0 0 26px;
            text-align: center;
            font-size: 34px;
            line-height: 1.2;
            font-weight: 800;
            text-transform: uppercase;
            color: #2d3045;
        }

        .auth-card input,
        .auth-card select {
            display: block;
            width: 100%;
            height: 46px;
            margin: 0 0 12px;
            padding: 0 18px;
            border: 1px solid #d7d9df;
            border-radius: 5px;
            background: #fff;
            color: #2d3045;
            font-size: 16px;
        }

        .auth-card .auth-button {
            display: block;
            min-width: 210px;
            height: 54px;
            margin: 22px auto 12px;
            border: 0;
            border-radius: 28px;
            background: #425a9f;
            color: #fff;
            font-size: 20px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .auth-card .auth-button:hover {
            background: #10a3ce;
        }

        .auth-card .auth-link {
            display: block;
            text-align: center;
            color: #2d78bd;
            font-size: 16px;
        }

        .auth-card .auth-alert {
            margin-bottom: 18px;
            padding: 12px 16px;
            border-radius: 5px;
            background: #f7d9dd;
            color: #a53a47;
            font-size: 15px;
        }

        .auth-card .auth-success {
            background: #dff2e8;
            color: #287647;
        }

        .auth-card .required-note {
            margin: -14px 0 20px;
            text-align: center;
            color: #9fa2aa;
            font-size: 15px;
        }
    </style>
@endsection

@section('content')
    <div class="auth-card">
        <h1>Registration</h1>
        <p class="required-note">* - required</p>

        @if (session('status'))
            <div class="auth-alert auth-success">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="auth-alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('front.registration.store') }}" autocomplete="off">
            @csrf
            <input type="email" name="email" value="{{ old('email') }}" placeholder="Email *" required>
            <input type="password" name="password" placeholder="Password *" required>
            <input type="password" name="password_confirmation" placeholder="Confirm password *" required>
            <input type="text" name="firstname" value="{{ old('firstname') }}" placeholder="First name *" required>
            <input type="text" name="lastname" value="{{ old('lastname') }}" placeholder="Last name *" required>
            <input type="text" name="nickname" value="{{ old('nickname') }}" placeholder="Nickname">
            <select name="sex" required>
                <option value="">Gender *</option>
                <option value="male" @selected(old('sex') === 'male')>Male</option>
                <option value="female" @selected(old('sex') === 'female')>Female</option>
            </select>
            <input type="date" name="birthday" value="{{ old('birthday') }}" placeholder="Birthday">

            <button type="submit" class="auth-button">Sign up</button>
            <a href="{{ route('front.home') }}" class="auth-link">Sign in to the site</a>
        </form>
    </div>
@endsection
