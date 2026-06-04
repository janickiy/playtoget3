<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Repositories\CommunityRepository;
use Illuminate\Contracts\View\View;

class TeamsController extends Controller
{
    public function show(int $community, CommunityRepository $communities): View
    {
        return view('front.pages.placeholder', [
            'title' => 'Команда',
            'entity' => $communities->findTeam($community),
        ]);
    }

    public function user(int $user): View
    {
        return view('front.pages.placeholder', ['title' => 'Команды пользователя', 'entityId' => $user]);
    }

    public function members(int $community): View
    {
        return view('front.pages.placeholder', ['title' => 'Участники команды', 'entityId' => $community]);
    }

    public function photoalbums(int $community): View
    {
        return view('front.pages.placeholder', ['title' => 'Фотоальбомы команды', 'entityId' => $community]);
    }

    public function addPhoto(int $community): View
    {
        return view('front.pages.placeholder', ['title' => 'Добавление фото команды', 'entityId' => $community]);
    }

    public function photo(int $community, int $photo): View
    {
        return view('front.pages.placeholder', ['title' => 'Фото команды', 'entityId' => $community, 'childId' => $photo]);
    }

    public function editPhotoalbum(int $community, int $album): View
    {
        return view('front.pages.placeholder', ['title' => 'Редактирование фотоальбома команды', 'entityId' => $community, 'childId' => $album]);
    }

    public function videoalbums(int $community): View
    {
        return view('front.pages.placeholder', ['title' => 'Видеоальбомы команды', 'entityId' => $community]);
    }

    public function addVideo(int $community): View
    {
        return view('front.pages.placeholder', ['title' => 'Добавление видео команды', 'entityId' => $community]);
    }

    public function createVideoalbum(int $community): View
    {
        return view('front.pages.placeholder', ['title' => 'Создание видеоальбома команды', 'entityId' => $community]);
    }
}
