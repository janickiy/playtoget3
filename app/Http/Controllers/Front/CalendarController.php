<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Repositories\EventRepository;
use Illuminate\Contracts\View\View;

class CalendarController extends Controller
{
    public function index(EventRepository $events): View
    {
        return view('front.pages.placeholder', [
            'title' => 'Календарь',
            'items' => $events->upcoming(),
        ]);
    }
}
