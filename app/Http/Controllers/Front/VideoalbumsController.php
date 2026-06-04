<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class VideoalbumsController extends Controller
{
    public function index(): View
    {
        return view('front.pages.placeholder', ['title' => 'Видеоальбомы']);
    }

    public function user(int $user): View
    {
        return view('front.pages.placeholder', ['title' => 'Видеоальбомы пользователя', 'entityId' => $user]);
    }
}
