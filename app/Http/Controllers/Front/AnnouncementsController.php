<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Repositories\AnnouncementRepository;
use Illuminate\Contracts\View\View;

class AnnouncementsController extends Controller
{
    /**
     * Показывает список опубликованных объявлений.
     */
    public function index(AnnouncementRepository $announcements): View
    {
        return view('front.announcements.index', [
            'announcements' => $announcements->visibleList(),
            'hideTopProfile' => true,
            'title' => 'Объявления',
        ]);
    }

    /**
     * Показывает опубликованное объявление по slug.
     */
    public function show(string $slug, AnnouncementRepository $announcements): View
    {
        $announcement = $announcements->visibleBySlug($slug);

        return view('front.content.show', [
            'hideTopProfile' => true,
            'page' => $announcement,
            'title' => $announcement?->title ?? 'Объявление',
        ]);
    }
}
