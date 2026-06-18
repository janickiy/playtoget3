<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\MenuHelper;
use App\Helpers\SettingsHelper;
use Illuminate\Http\RedirectResponse;

class CacheController extends Controller
{
    /**
     * Clears cached settings and menu data for the admin panel.
     */
    public function clear(): RedirectResponse
    {
        SettingsHelper::cacheClear();
        MenuHelper::cacheClear();

        return back()->with('success', 'Кеш успешно сброшен.');
    }
}
