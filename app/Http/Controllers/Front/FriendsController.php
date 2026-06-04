<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class FriendsController extends Controller
{
    public function index(): View
    {
        return view('front.pages.placeholder', ['title' => 'Друзья']);
    }

    public function user(int $user): View
    {
        return view('front.pages.placeholder', ['title' => 'Друзья пользователя', 'entityId' => $user]);
    }
}
