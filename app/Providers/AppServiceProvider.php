<?php

namespace App\Providers;


use App\Helpers\FrontAssets;
use App\Helpers\PermissionsHelper;
use App\Models\Comment;
use App\Models\Community;
use App\Models\Event;
use App\Models\Message;
use App\Models\Occupation;
use App\Models\Photo;
use App\Models\SportBlock;
use App\Models\User;
use App\Models\Video;
use App\Helpers\SettingsHelper;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $loader = \Illuminate\Foundation\AliasLoader::getInstance();
        $loader->alias('PermissionsHelper', PermissionsHelper::class);
        $loader->alias('SettingsHelper', SettingsHelper::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::morphMap([
            'comment' => Comment::class,
            'event' => Event::class,
            'fitness' => SportBlock::class,
            'group' => Community::class,
            'message' => Message::class,
            'occupation' => Occupation::class,
            'photo' => Photo::class,
            'playground' => SportBlock::class,
            'shop' => SportBlock::class,
            'team' => Community::class,
            'user' => User::class,
            'user_attach' => User::class,
            'video' => Video::class,
        ]);

        View::composer('front.*', function ($view) {
            $user = Auth::guard('web')->user();

            $events = Schema::hasTable('events')
                ? Event::query()->where('banned', false)->orderByDesc('date_from')->limit(3)->get()
                : collect();

            $sportBlocks = Schema::hasTable('sport_blocks')
                ? SportBlock::query()->where('banned', false)->orderByDesc('id')->get()
                : collect();

            $view->with('frontLayout', [
                'user' => $user,
                'displayName' => $user?->displayName() ?? 'PlayToGet',
                'firstname' => $user?->firstname ?: 'PlayToGet',
                'lastname' => $user?->lastname ?: '',
                'about' => $user?->about ?: '',
                'avatar' => FrontAssets::userAvatar($user),
                'cover' => FrontAssets::userCover($user),
                'events' => $events,
                'eventCount' => $events->count(),
                'playgrounds' => $sportBlocks->where('type', 'playground')->take(3)->values(),
                'playgroundsCount' => $sportBlocks->where('type', 'playground')->count(),
                'shops' => $sportBlocks->where('type', 'shop')->take(3)->values(),
                'shopsCount' => $sportBlocks->where('type', 'shop')->count(),
                'fitness' => $sportBlocks->where('type', 'fitness')->take(3)->values(),
                'fitnessCount' => $sportBlocks->where('type', 'fitness')->count(),
            ]);
        });
    }
}
