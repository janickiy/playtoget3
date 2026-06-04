<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Repositories\SportBlockRepository;
use Illuminate\Contracts\View\View;

class PlaygroundsController extends Controller
{
    public function index(SportBlockRepository $sportBlocks, ?int $sportBlock = null): View
    {
        return view('front.pages.placeholder', [
            'title' => 'Площадки',
            'entity' => $sportBlock ? $sportBlocks->findByType($sportBlock, 'playground') : null,
            'items' => $sportBlock ? collect() : $sportBlocks->byType('playground'),
        ]);
    }
}
