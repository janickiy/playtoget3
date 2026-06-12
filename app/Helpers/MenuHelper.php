<?php

namespace App\Helpers;

use Harimayco\Menu\Models\Menus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class MenuHelper
{
    /**
     * @return array
     */
    public static function getMenuList(): array
    {
        if (Cache::has('menu')) {
            $menu = Cache::get('menu');

            return is_array($menu) ? $menu : [];
        }

        $menuTable = config('menu.table_prefix') . config('menu.table_name_menus');
        $itemsTable = config('menu.table_prefix') . config('menu.table_name_items');

        if (! Schema::hasTable($menuTable) || ! Schema::hasTable($itemsTable)) {
            return [];
        }

        $menu = [];
        $menu['bottom'] = Menus::where('name', 'bottom')->with('items')->first()?->items?->toArray();

        Cache::put('menu', $menu);

        return $menu;
    }
}
