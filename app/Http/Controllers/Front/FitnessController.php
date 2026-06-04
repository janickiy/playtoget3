<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Repositories\SportBlockRepository;
use Illuminate\Contracts\View\View;

class FitnessController extends Controller
{
    public function index(SportBlockRepository $sportBlocks, ?int $sportBlock = null): View
    {
        return view('front.pages.placeholder', [
            'title' => 'Фитнес',
            'entity' => $sportBlock ? $sportBlocks->findByType($sportBlock, 'fitness') : null,
            'items' => $sportBlock ? collect() : $sportBlocks->byType('fitness'),
        ]);
    }
}
