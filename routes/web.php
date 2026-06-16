<?php

use App\Http\Controllers\Front\AjaxController;
use App\Http\Controllers\Front\AnnouncementsController;
use App\Http\Controllers\Front\AuthController as FrontAuthController;
use App\Http\Controllers\Front\CalendarController;
use App\Http\Controllers\Front\ContentController;
use App\Http\Controllers\Front\EventsController;
use App\Http\Controllers\Front\FeedbackController;
use App\Http\Controllers\Front\FitnessController;
use App\Http\Controllers\Front\FriendsController;
use App\Http\Controllers\Front\GroupsController;
use App\Http\Controllers\Front\HomeController;
use App\Http\Controllers\Front\NewsController;
use App\Http\Controllers\Front\PhotoalbumsController;
use App\Http\Controllers\Front\PlaygroundsController;
use App\Http\Controllers\Front\ProfileController;
use App\Http\Controllers\Front\ShopsController;
use App\Http\Controllers\Front\TeamsController;
use App\Http\Controllers\Front\VideoalbumsController;
use Illuminate\Support\Facades\Route;

Route::controller(HomeController::class)->group(function () {
    Route::get('/', 'index')->name('front.home');
});

Route::controller(FrontAuthController::class)->name('front.')->group(function () {
    Route::post('/', 'login')->name('login');
    Route::get('auth/{provider}/redirect', 'redirectToProvider')
        ->where('provider', 'google|facebook|x|linkedin')
        ->name('social.redirect');
    Route::get('auth/{provider}/callback', 'handleProviderCallback')
        ->where('provider', 'google|facebook|x|linkedin')
        ->name('social.callback');
    Route::post('front/logout', 'logout')->name('logout');
});

Route::prefix('news')->name('front.news.')->controller(NewsController::class)->group(function () {
    Route::get('', 'index')->name('index');
});

Route::prefix('profile')->name('front.profile.')->controller(ProfileController::class)->group(function () {
    Route::get('edit', 'edit')->name('edit');
    Route::post('edit', 'update')->name('update');
    Route::get('{user}/messages/user/{recipient}', 'messages')
        ->where(['user' => '[0-9]+', 'recipient' => '[0-9]+'])
        ->name('messages.show');
    Route::get('{user}/messages', 'dialogues')->where('user', '[0-9]+')->name('messages.index');
    Route::get('{user}', 'show')->where('user', '[0-9]+')->name('show');
});

Route::prefix('playgrounds')->name('front.playgrounds.')->controller(PlaygroundsController::class)->group(function () {
    Route::get('create', 'create')->name('create');
    Route::post('create', 'store')->name('store');
    Route::get('{sportBlock}/edit', 'edit')->where('sportBlock', '[0-9]+')->name('edit');
    Route::post('{sportBlock}/edit', 'update')->where('sportBlock', '[0-9]+')->name('update');
    Route::get('{sportBlock?}', 'index')->where('sportBlock', '[0-9]+')->name('index');
});

Route::prefix('shops')->name('front.shops.')->controller(ShopsController::class)->group(function () {
    Route::get('create', 'create')->name('create');
    Route::post('create', 'store')->name('store');
    Route::get('{sportBlock}/edit', 'edit')->where('sportBlock', '[0-9]+')->name('edit');
    Route::post('{sportBlock}/edit', 'update')->where('sportBlock', '[0-9]+')->name('update');
    Route::get('{sportBlock?}', 'index')->where('sportBlock', '[0-9]+')->name('index');
});

Route::prefix('fitness')->name('front.fitness.')->controller(FitnessController::class)->group(function () {
    Route::get('create', 'create')->name('create');
    Route::post('create', 'store')->name('store');
    Route::get('{sportBlock}/edit', 'edit')->where('sportBlock', '[0-9]+')->name('edit');
    Route::post('{sportBlock}/edit', 'update')->where('sportBlock', '[0-9]+')->name('update');
    Route::get('{sportBlock?}', 'index')->where('sportBlock', '[0-9]+')->name('index');
});

Route::prefix('calendar')->name('front.calendar.')->controller(CalendarController::class)->group(function () {
    Route::get('', 'index')->name('index');
});

Route::prefix('events')->name('front.events.')->controller(EventsController::class)->group(function () {
    Route::get('create', 'create')->name('create');
    Route::post('create', 'store')->name('store');
    Route::get('{event}/edit', 'edit')->where('event', '[0-9]+')->name('edit');
    Route::post('{event}/edit', 'update')->where('event', '[0-9]+')->name('update');
    Route::get('{event}/members', 'members')->where('event', '[0-9]+')->name('members');

    Route::prefix('{event}/photoalbums')->where(['event' => '[0-9]+'])->group(function () {
        Route::get('add-photo', 'addPhoto')->name('photoalbums.add-photo');
        Route::get('create', 'createPhotoAlbum')->name('photoalbums.create');
        Route::post('create', 'storePhotoAlbum')->name('photoalbums.store');
        Route::get('photo/{photo}', 'photoWithoutAlbum')->where('photo', '[0-9]+')->name('photoalbums.photo.legacy');
        Route::get('{album}/photo/{photo}', 'photo')
            ->where(['album' => '[0-9]+', 'photo' => '[0-9]+'])
            ->name('photoalbums.photo');
        Route::get('{album}', 'showPhotoalbum')->where('album', '[0-9]+')->name('photoalbums.show');
        Route::get('', 'photoAlbums')->name('photoalbums');
    });
    Route::get('{event}/photoalbum/{album}/edit', 'editPhotoalbum')
        ->where(['event' => '[0-9]+', 'album' => '[0-9]+'])
        ->name('photoalbum.edit');
    Route::post('{event}/photoalbum/{album}/edit', 'updatePhotoalbum')
        ->where(['event' => '[0-9]+', 'album' => '[0-9]+'])
        ->name('photoalbum.update');
    Route::delete('{event}/photoalbum/{album}', 'destroyPhotoalbum')
        ->where(['event' => '[0-9]+', 'album' => '[0-9]+'])
        ->name('photoalbum.destroy');

    Route::prefix('{event}/videoalbums')->where(['event' => '[0-9]+'])->group(function () {
        Route::get('add-video', 'addVideo')->name('videoalbums.add-video');
        Route::post('add-video', 'storeVideo')->name('videoalbums.store-video');
        Route::get('create', 'createVideoAlbum')->name('videoalbums.create');
        Route::post('create', 'storeVideoAlbum')->name('videoalbums.store');
        Route::get('{album}', 'showVideoAlbum')->where('album', '[0-9]+')->name('videoalbums.show');
        Route::get('', 'videoAlbums')->name('videoalbums');
    });
    Route::get('{event}/videoalbum/{album}/edit', 'editVideoalbum')
        ->where(['event' => '[0-9]+', 'album' => '[0-9]+'])
        ->name('videoalbum.edit');
    Route::post('{event}/videoalbum/{album}/edit', 'updateVideoalbum')
        ->where(['event' => '[0-9]+', 'album' => '[0-9]+'])
        ->name('videoalbum.update');
    Route::delete('{event}/videoalbum/{album}', 'destroyVideoalbum')
        ->where(['event' => '[0-9]+', 'album' => '[0-9]+'])
        ->name('videoalbum.destroy');

    Route::get('{event}', 'show')->where('event', '[0-9]+')->name('show');
    Route::get('', 'index')->name('index');
});

Route::prefix('announcements')->name('front.announcements.')->controller(AnnouncementsController::class)->group(function () {
    Route::get('', 'index')->name('index');
    Route::get('{slug}', 'show')->where('slug', '[A-Za-z0-9-]+')->name('show');
});

Route::prefix('friends')->name('front.friends.')->controller(FriendsController::class)->group(function () {
    Route::get('user/{user}', 'user')->where('user', '[0-9]+')->name('user');
    Route::get('', 'index')->name('index');
});

Route::prefix('groups')->name('front.groups.')->controller(GroupsController::class)->group(function () {
    Route::get('', 'index')->name('index');
    Route::get('create', 'create')->name('create');
    Route::post('create', 'store')->name('store');
    Route::get('user/{user}', 'user')->where('user', '[0-9]+')->name('user');

    Route::prefix('photoalbums')->group(function () {
        Route::get('', 'photoAlbums')->name('photoalbums.default');
        Route::get('{album}/edit', 'editPhotoalbum')->where('album', '[0-9]+')->name('photoalbum.edit');
        Route::post('{album}/edit', 'updatePhotoalbum')->where('album', '[0-9]+')->name('photoalbum.update');
        Route::delete('{album}', 'destroyPhotoalbum')->where('album', '[0-9]+')->name('photoalbum.destroy');
    });

    Route::prefix('videoalbums')->group(function () {
        Route::get('', 'videoAlbums')->name('videoalbums.default');
        Route::get('{album}/edit', 'editVideoalbum')->where('album', '[0-9]+')->name('videoalbum.edit');
        Route::post('{album}/edit', 'updateVideoalbum')->where('album', '[0-9]+')->name('videoalbum.update');
        Route::delete('{album}', 'destroyVideoalbum')->where('album', '[0-9]+')->name('videoalbum.destroy');
    });

    Route::get('{community}/edit', 'edit')->where('community', '[0-9]+')->name('edit');
    Route::post('{community}/edit', 'update')->where('community', '[0-9]+')->name('update');
    Route::get('{community}/members', 'members')->where('community', '[0-9]+')->name('members');

    Route::prefix('{community}/photoalbums')->where(['community' => '[0-9]+'])->group(function () {
        Route::get('add-photo', 'addPhoto')->name('photoalbums.add-photo');
        Route::get('create', 'createPhotoAlbum')->name('photoalbums.create');
        Route::post('create', 'storePhotoAlbum')->name('photoalbums.store');
        Route::get('photo/{photo}', 'photoWithoutAlbum')->where('photo', '[0-9]+')->name('photoalbums.photo.legacy');
        Route::get('{album}/photo/{photo}', 'photo')
            ->where(['album' => '[0-9]+', 'photo' => '[0-9]+'])
            ->name('photoalbums.photo');
        Route::get('{album}', 'showPhotoalbum')->where('album', '[0-9]+')->name('photoalbums.show');
        Route::get('', 'photoAlbums')->name('photoalbums');
    });

    Route::get('{community}/photoalbum/{album}/edit', 'editPhotoalbumForGroup')
        ->where(['community' => '[0-9]+', 'album' => '[0-9]+'])
        ->name('photoalbum.edit.with-community');
    Route::post('{community}/photoalbum/{album}/edit', 'updatePhotoalbumForGroup')
        ->where(['community' => '[0-9]+', 'album' => '[0-9]+'])
        ->name('photoalbum.update.with-community');
    Route::delete('{community}/photoalbum/{album}', 'destroyPhotoalbumForGroup')
        ->where(['community' => '[0-9]+', 'album' => '[0-9]+'])
        ->name('photoalbum.destroy.with-community');

    Route::prefix('{community}/videoalbums')->where(['community' => '[0-9]+'])->group(function () {
        Route::get('add-video', 'addVideo')->name('videoalbums.add-video');
        Route::post('add-video', 'storeVideo')->name('videoalbums.store-video');
        Route::get('create', 'createVideoAlbum')->name('videoalbums.create');
        Route::post('create', 'storeVideoAlbum')->name('videoalbums.store');
        Route::get('{album}', 'showVideoAlbum')->where('album', '[0-9]+')->name('videoalbums.show');
        Route::get('', 'videoAlbums')->name('videoalbums');
    });
    Route::delete('{community}/videoalbum/{album}', 'destroyVideoalbumForGroup')
        ->where(['community' => '[0-9]+', 'album' => '[0-9]+'])
        ->name('videoalbum.destroy.with-community');

    Route::prefix('{community}/events')->where(['community' => '[0-9]+'])->group(function () {
        Route::get('create', 'createEvent')->name('events.create');
        Route::post('create', 'storeEvent')->name('events.store');
        Route::get('', 'events')->name('events');
    });
    Route::get('{community}', 'show')->where('community', '[0-9]+')->name('show');
});

Route::prefix('photoalbums')->name('front.photoalbums.')->controller(PhotoalbumsController::class)->group(function () {
    Route::get('add-photo', 'addPhoto')->name('add-photo');
    Route::get('create', 'create')->name('create');
    Route::post('create', 'store')->name('store');
    Route::get('edit/{album}', 'edit')->where('album', '[0-9]+')->name('edit');
    Route::post('edit/{album}', 'update')->where('album', '[0-9]+')->name('update');
    Route::get('user/{user}', 'user')->where('user', '[0-9]+')->name('user');
    Route::delete('{album}', 'destroy')->where('album', '[0-9]+')->name('destroy');
    Route::get('{album}', 'show')->where('album', '[0-9]+')->name('show');
    Route::get('', 'index')->name('index');
});

Route::prefix('videoalbums')->name('front.videoalbums.')->controller(VideoalbumsController::class)->group(function () {
    Route::get('add-video', 'addVideo')->name('add-video');
    Route::post('add-video', 'storeVideo')->name('store-video');
    Route::get('create', 'create')->name('create');
    Route::post('create', 'store')->name('store');
    Route::get('edit/{album}', 'edit')->where('album', '[0-9]+')->name('edit');
    Route::post('edit/{album}', 'update')->where('album', '[0-9]+')->name('update');
    Route::get('user/{user}', 'user')->where('user', '[0-9]+')->name('user');
    Route::delete('{album}', 'destroy')->where('album', '[0-9]+')->name('destroy');
    Route::get('{album}', 'show')->where('album', '[0-9]+')->name('show');
    Route::get('', 'index')->name('index');
});

Route::prefix('teams')->name('front.teams.')->controller(TeamsController::class)->group(function () {
    Route::get('', 'index')->name('index');
    Route::get('create', 'create')->name('create');
    Route::post('create', 'store')->name('store');
    Route::get('user/{user}', 'user')->where('user', '[0-9]+')->name('user');

    Route::prefix('photoalbums')->group(function () {
        Route::get('', 'photoAlbums')->name('photoalbums.default');
        Route::get('{album}/edit', 'editPhotoalbum')->where('album', '[0-9]+')->name('photoalbum.edit');
        Route::post('{album}/edit', 'updatePhotoalbum')->where('album', '[0-9]+')->name('photoalbum.update');
        Route::delete('{album}', 'destroyPhotoalbum')->where('album', '[0-9]+')->name('photoalbum.destroy');
    });

    Route::prefix('videoalbums')->group(function () {
        Route::get('', 'videoAlbums')->name('videoalbums.default');
        Route::get('{album}/edit', 'editVideoalbum')->where('album', '[0-9]+')->name('videoalbum.edit');
        Route::post('{album}/edit', 'updateVideoalbum')->where('album', '[0-9]+')->name('videoalbum.update');
        Route::delete('{album}', 'destroyVideoalbum')->where('album', '[0-9]+')->name('videoalbum.destroy');
    });

    Route::get('{community}/edit', 'edit')->where('community', '[0-9]+')->name('edit');
    Route::post('{community}/edit', 'update')->where('community', '[0-9]+')->name('update');
    Route::get('{community}/members', 'members')->where('community', '[0-9]+')->name('members');

    Route::prefix('{community}/photoalbums')->where(['community' => '[0-9]+'])->group(function () {
        Route::get('add-photo', 'addPhoto')->name('photoalbums.add-photo');
        Route::get('create', 'createPhotoAlbum')->name('photoalbums.create');
        Route::post('create', 'storePhotoAlbum')->name('photoalbums.store');
        Route::get('photo/{photo}', 'photoWithoutAlbum')->where('photo', '[0-9]+')->name('photoalbums.photo.legacy');
        Route::get('{album}/photo/{photo}', 'photo')
            ->where(['album' => '[0-9]+', 'photo' => '[0-9]+'])
            ->name('photoalbums.photo');
        Route::get('{album}', 'showPhotoalbum')->where('album', '[0-9]+')->name('photoalbums.show');
        Route::get('', 'photoAlbums')->name('photoalbums');
    });

    Route::get('{community}/photoalbum/{album}/edit', 'editPhotoalbumForTeam')
        ->where(['community' => '[0-9]+', 'album' => '[0-9]+'])
        ->name('photoalbum.edit.with-community');
    Route::post('{community}/photoalbum/{album}/edit', 'updatePhotoalbumForTeam')
        ->where(['community' => '[0-9]+', 'album' => '[0-9]+'])
        ->name('photoalbum.update.with-community');

    Route::prefix('{community}/videoalbums')->where(['community' => '[0-9]+'])->group(function () {
        Route::get('add-video', 'addVideo')->name('videoalbums.add-video');
        Route::post('add-video', 'storeVideo')->name('videoalbums.store-video');
        Route::get('create', 'createVideoAlbum')->name('videoalbums.create');
        Route::post('create', 'storeVideoAlbum')->name('videoalbums.store');
        Route::get('{album}', 'showVideoAlbum')->where('album', '[0-9]+')->name('videoalbums.show');
        Route::get('', 'videoAlbums')->name('videoalbums');
    });

    Route::prefix('{community}/events')->where(['community' => '[0-9]+'])->group(function () {
        Route::get('create', 'createEvent')->name('events.create');
        Route::post('create', 'storeEvent')->name('events.store');
        Route::get('', 'events')->name('events');
    });
    Route::get('{community}', 'show')->where('community', '[0-9]+')->name('show');
});

Route::prefix('page')->name('front.content.')->controller(ContentController::class)->group(function () {
    Route::get('{slug}', 'show')->where('slug', '[A-Za-z0-9-]+')->name('show');
});

Route::prefix('feedback')->name('front.feedback.')->controller(FeedbackController::class)->group(function () {
    Route::get('', 'create')->name('create');
    Route::post('', 'store')->name('store');
});

Route::prefix('ajax')->name('front.ajax.')->controller(AjaxController::class)->group(function () {
    Route::match(['GET', 'POST'], '{action}', 'handle')->name('handle');
});
