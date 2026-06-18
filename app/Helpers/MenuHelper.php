<?php

namespace App\Helpers;

use App\Enums\CacheKey;
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
        $menu = Cache::remember(CacheKey::Menu->value, CacheKey::Menu->ttl(), function (): array {
            $menuTable = config('menu.table_prefix') . config('menu.table_name_menus');
            $itemsTable = config('menu.table_prefix') . config('menu.table_name_items');

            if (! Schema::hasTable($menuTable) || ! Schema::hasTable($itemsTable)) {
                return [];
            }

            $menu = [];
            $menu['bottom'] = Menus::where('name', 'bottom')->with('items')->first()?->items?->toArray();

            return $menu;
        });

        return is_array($menu) ? $menu : [];
    }

    public static function cacheClear(): bool
    {
        return Cache::forget(CacheKey::Menu->value);
    }
}
