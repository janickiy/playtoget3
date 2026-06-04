<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Repositories\UserRepository;
use Illuminate\Contracts\View\View;

class ProfileController extends Controller
{
    public function show(int $user, UserRepository $users): View
    {
        return view('front.pages.placeholder', [
            'title' => 'Профиль',
            'entity' => $users->find($user),
        ]);
    }

    public function edit(): View
    {
        return view('front.pages.placeholder', ['title' => 'Редактирование профиля']);
    }
}
