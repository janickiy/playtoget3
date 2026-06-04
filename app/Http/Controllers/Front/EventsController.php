<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Repositories\EventRepository;
use Illuminate\Contracts\View\View;

class EventsController extends Controller
{
    public function index(EventRepository $events): View
    {
        return view('front.pages.placeholder', [
            'title' => 'События',
            'items' => $events->upcoming(),
        ]);
    }

    public function show(int $event, EventRepository $events): View
    {
        return view('front.pages.placeholder', [
            'title' => 'Событие',
            'entity' => $events->find($event),
        ]);
    }

    public function members(int $event): View
    {
        return view('front.pages.placeholder', ['title' => 'Участники события', 'entityId' => $event]);
    }

    public function photoalbums(int $event): View
    {
        return view('front.pages.placeholder', ['title' => 'Фотоальбомы события', 'entityId' => $event]);
    }

    public function videoalbums(int $event): View
    {
        return view('front.pages.placeholder', ['title' => 'Видеоальбомы события', 'entityId' => $event]);
    }

    public function create(): View
    {
        return view('front.pages.placeholder', ['title' => 'Создание события']);
    }
}
