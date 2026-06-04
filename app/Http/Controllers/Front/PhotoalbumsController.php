<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class PhotoalbumsController extends Controller
{
    public function index(): View
    {
        return view('front.pages.placeholder', ['title' => 'Фотоальбомы']);
    }

    public function addPhoto(): View
    {
        return view('front.pages.placeholder', ['title' => 'Добавление фото']);
    }

    public function create(): View
    {
        return view('front.pages.placeholder', ['title' => 'Создание фотоальбома']);
    }

    public function user(int $user): View
    {
        return view('front.pages.placeholder', ['title' => 'Фотоальбомы пользователя', 'entityId' => $user]);
    }
}
