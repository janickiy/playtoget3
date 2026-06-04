<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Repositories\SportBlockRepository;
use Illuminate\Contracts\View\View;

class ShopsController extends Controller
{
    public function index(SportBlockRepository $sportBlocks, ?int $sportBlock = null): View
    {
        return view('front.pages.placeholder', [
            'title' => 'Магазины',
            'entity' => $sportBlock ? $sportBlocks->findByType($sportBlock, 'shop') : null,
            'items' => $sportBlock ? collect() : $sportBlocks->byType('shop'),
        ]);
    }
}
