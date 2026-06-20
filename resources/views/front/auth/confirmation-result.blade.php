@extends('errors.layout')

@section('title', $title)

@section('styles')
    <style>
        .confirmation-card {
            width: min(560px, calc(100% - 32px));
            margin: 46px auto 90px;
            padding: 40px 46px 44px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 18px 42px rgba(0, 0, 0, .28);
            color: #2d3045;
            text-align: center;
        }

        .confirmation-card__mark {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 78px;
            height: 78px;
            margin: 0 auto 24px;
            border-radius: 50%;
            background: #46b1ab;
            color: #fff;
            font-size: 46px;
            font-weight: 800;
            line-height: 1;
        }

        .confirmation-card--error .confirmation-card__mark {
            background: #d46570;
        }

        .confirmation-card h1 {
            margin: 0 0 18px;
            font-size: 34px;
            line-height: 1.2;
            font-weight: 800;
            text-transform: uppercase;
            color: #2d3045;
        }

        .confirmation-card p {
            margin: 0 auto 30px;
            max-width: 430px;
            color: #6f7280;
            font-size: 17px;
            line-height: 1.55;
        }

        .confirmation-card__button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 210px;
            height: 54px;
            padding: 0 28px;
            border-radius: 28px;
            background: #425a9f;
            color: #fff;
            font-size: 17px;
            font-weight: 700;
            text-transform: uppercase;
            text-decoration: none;
        }

        .confirmation-card__button:hover,
        .confirmation-card__button:focus {
            background: #10a3ce;
            color: #fff;
            text-decoration: none;
        }
    </style>
@endsection

@section('content')
    <div class="confirmation-card{{ $success ? '' : ' confirmation-card--error' }}">
        <div class="confirmation-card__mark">{!! $success ? '&#10003;' : '!' !!}</div>
        <h1>{{ $heading }}</h1>
        <p>{{ $message }}</p>
        <a href="{{ $buttonUrl }}" class="confirmation-card__button">{{ $buttonText }}</a>
    </div>
@endsection
