<?php

use App\Http\Controllers\Front\AjaxController;
use App\Http\Controllers\Front\AuthController as FrontAuthController;
use App\Http\Controllers\Front\CalendarController;
use App\Http\Controllers\Front\ContentController;
use App\Http\Controllers\Front\EventsController;
use App\Http\Controllers\Front\FeedbackController;
use App\Http\Controllers\Front\FitnessController;
use App\Http\Controllers\Front\FriendsController;
use App\Http\Controllers\Front\HomeController;
use App\Http\Controllers\Front\NewsController;
use App\Http\Controllers\Front\PhotoalbumsController;
use App\Http\Controllers\Front\PlaygroundsController;
use App\Http\Controllers\Front\ProfileController;
use App\Http\Controllers\Front\ShopsController;
use App\Http\Controllers\Front\TeamsController;
use App\Http\Controllers\Front\VideoalbumsController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('front.home');
Route::post('/', [FrontAuthController::class, 'login'])->name('front.login');
Route::post('front/logout', [FrontAuthController::class, 'logout'])->name('front.logout');

Route::get('news', [NewsController::class, 'index'])->name('front.news.index');
Route::get('profile/edit', [ProfileController::class, 'edit'])->name('front.profile.edit');
Route::post('profile/edit', [ProfileController::class, 'update'])->name('front.profile.update');
Route::get('profile/{user}/messages/user/{recipient}', [ProfileController::class, 'messages'])
    ->where(['user' => '[0-9]+', 'recipient' => '[0-9]+'])
    ->name('front.profile.messages.show');
Route::get('profile/{user}/messages', [ProfileController::class, 'dialogues'])
    ->where('user', '[0-9]+')
    ->name('front.profile.messages.index');
Route::get('profile/{user}', [ProfileController::class, 'show'])->where('user', '[0-9]+')->name('front.profile.show');

Route::get('playgrounds/{sportBlock?}', [PlaygroundsController::class, 'index'])
    ->where('sportBlock', '[0-9]+')
    ->name('front.playgrounds.index');
Route::get('shops/{sportBlock?}', [ShopsController::class, 'index'])
    ->where('sportBlock', '[0-9]+')
    ->name('front.shops.index');
Route::get('fitness/{sportBlock?}', [FitnessController::class, 'index'])
    ->where('sportBlock', '[0-9]+')
    ->name('front.fitness.index');

Route::get('calendar', [CalendarController::class, 'index'])->name('front.calendar.index');

Route::prefix('events')->name('front.events.')->group(function () {
    Route::get('create', [EventsController::class, 'create'])->name('create');
    Route::get('{event}/members', [EventsController::class, 'members'])->where('event', '[0-9]+')->name('members');
    Route::get('{event}/photoalbums', [EventsController::class, 'photoalbums'])->where('event', '[0-9]+')->name('photoalbums');
    Route::get('{event}/videoalbums', [EventsController::class, 'videoalbums'])->where('event', '[0-9]+')->name('videoalbums');
    Route::get('{event}', [EventsController::class, 'show'])->where('event', '[0-9]+')->name('show');
    Route::get('', [EventsController::class, 'index'])->name('index');
});

Route::get('friends/user/{user}', [FriendsController::class, 'user'])->where('user', '[0-9]+')->name('front.friends.user');
Route::get('friends', [FriendsController::class, 'index'])->name('front.friends.index');

Route::prefix('photoalbums')->name('front.photoalbums.')->group(function () {
    Route::get('add-photo', [PhotoalbumsController::class, 'addPhoto'])->name('add-photo');
    Route::get('create', [PhotoalbumsController::class, 'create'])->name('create');
    Route::post('create', [PhotoalbumsController::class, 'store'])->name('store');
    Route::get('edit/{album}', [PhotoalbumsController::class, 'edit'])->where('album', '[0-9]+')->name('edit');
    Route::post('edit/{album}', [PhotoalbumsController::class, 'update'])->where('album', '[0-9]+')->name('update');
    Route::get('user/{user}', [PhotoalbumsController::class, 'user'])->where('user', '[0-9]+')->name('user');
    Route::delete('{album}', [PhotoalbumsController::class, 'destroy'])->where('album', '[0-9]+')->name('destroy');
    Route::get('{album}', [PhotoalbumsController::class, 'show'])->where('album', '[0-9]+')->name('show');
    Route::get('', [PhotoalbumsController::class, 'index'])->name('index');
});

Route::prefix('videoalbums')->name('front.videoalbums.')->group(function () {
    Route::get('add-video', [VideoalbumsController::class, 'addVideo'])->name('add-video');
    Route::post('add-video', [VideoalbumsController::class, 'storeVideo'])->name('store-video');
    Route::get('create', [VideoalbumsController::class, 'create'])->name('create');
    Route::post('create', [VideoalbumsController::class, 'store'])->name('store');
    Route::get('edit/{album}', [VideoalbumsController::class, 'edit'])->where('album', '[0-9]+')->name('edit');
    Route::post('edit/{album}', [VideoalbumsController::class, 'update'])->where('album', '[0-9]+')->name('update');
    Route::get('user/{user}', [VideoalbumsController::class, 'user'])->where('user', '[0-9]+')->name('user');
    Route::delete('{album}', [VideoalbumsController::class, 'destroy'])->where('album', '[0-9]+')->name('destroy');
    Route::get('{album}', [VideoalbumsController::class, 'show'])->where('album', '[0-9]+')->name('show');
    Route::get('', [VideoalbumsController::class, 'index'])->name('index');
});

Route::prefix('teams')->name('front.teams.')->group(function () {
    Route::get('', [TeamsController::class, 'index'])->name('index');
    Route::get('create', [TeamsController::class, 'create'])->name('create');
    Route::post('create', [TeamsController::class, 'store'])->name('store');
    Route::get('user/{user}', [TeamsController::class, 'user'])->where('user', '[0-9]+')->name('user');

    Route::prefix('photoalbums')->group(function () {
        Route::get('', [TeamsController::class, 'photoalbums'])->name('photoalbums.default');
        Route::get('{album}/edit', [TeamsController::class, 'editPhotoalbum'])->where('album', '[0-9]+')->name('photoalbum.edit');
        Route::post('{album}/edit', [TeamsController::class, 'updatePhotoalbum'])->where('album', '[0-9]+')->name('photoalbum.update');
    });



    Route::prefix('videoalbums')->group(function () {
        Route::get('', [TeamsController::class, 'videoalbums'])->name('videoalbums.default');
        Route::get('{album}/edit', [TeamsController::class, 'editVideoalbum'])->where('album', '[0-9]+')->name('videoalbum.edit');
        Route::post('{album}/edit', [TeamsController::class, 'updateVideoalbum'])->where('album', '[0-9]+')->name('videoalbum.update');

    });

    Route::get('{community}/edit', [TeamsController::class, 'edit'])->where('community', '[0-9]+')->name('edit');
    Route::post('{community}/edit', [TeamsController::class, 'update'])->where('community', '[0-9]+')->name('update');
    Route::get('{community}/members', [TeamsController::class, 'members'])->where('community', '[0-9]+')->name('members');
    Route::get('{community}/photoalbums/add-photo', [TeamsController::class, 'addPhoto'])->where('community', '[0-9]+')->name('photoalbums.add-photo');
    Route::get('{community}/photoalbums/create', [TeamsController::class, 'createPhotoalbum'])->where('community', '[0-9]+')->name('photoalbums.create');
    Route::post('{community}/photoalbums/create', [TeamsController::class, 'storePhotoalbum'])->where('community', '[0-9]+')->name('photoalbums.store');
    Route::get('{community}/photoalbums/photo/{photo}', [TeamsController::class, 'photoWithoutAlbum'])->where(['community' => '[0-9]+', 'photo' => '[0-9]+'])->name('photoalbums.photo.legacy');
    Route::get('{community}/photoalbums/{album}/photo/{photo}', [TeamsController::class, 'photo'])->where(['community' => '[0-9]+', 'album' => '[0-9]+', 'photo' => '[0-9]+'])->name('photoalbums.photo');
    Route::get('{community}/photoalbums/{album}', [TeamsController::class, 'showPhotoalbum'])->where(['community' => '[0-9]+', 'album' => '[0-9]+'])->name('photoalbums.show');
    Route::get('{community}/photoalbums', [TeamsController::class, 'photoalbums'])->where('community', '[0-9]+')->name('photoalbums');
    Route::get('{community}/photoalbum/{album}/edit', [TeamsController::class, 'editPhotoalbumForTeam'])->where(['community' => '[0-9]+', 'album' => '[0-9]+'])->name('photoalbum.edit.with-community');
    Route::post('{community}/photoalbum/{album}/edit', [TeamsController::class, 'updatePhotoalbumForTeam'])->where(['community' => '[0-9]+', 'album' => '[0-9]+'])->name('photoalbum.update.with-community');
    Route::get('{community}/videoalbums/add-video', [TeamsController::class, 'addVideo'])->where('community', '[0-9]+')->name('videoalbums.add-video');
    Route::post('{community}/videoalbums/add-video', [TeamsController::class, 'storeVideo'])->where('community', '[0-9]+')->name('videoalbums.store-video');
    Route::get('{community}/videoalbums/create', [TeamsController::class, 'createVideoalbum'])->where('community', '[0-9]+')->name('videoalbums.create');
    Route::post('{community}/videoalbums/create', [TeamsController::class, 'storeVideoalbum'])->where('community', '[0-9]+')->name('videoalbums.store');
    Route::get('{community}/videoalbums/{album}', [TeamsController::class, 'showVideoalbum'])->where(['community' => '[0-9]+', 'album' => '[0-9]+'])->name('videoalbums.show');
    Route::get('{community}/videoalbums', [TeamsController::class, 'videoalbums'])->where('community', '[0-9]+')->name('videoalbums');
    Route::get('{community}/events', [TeamsController::class, 'events'])->where('community', '[0-9]+')->name('events');
    Route::get('{community}', [TeamsController::class, 'show'])->where('community', '[0-9]+')->name('show');
});

Route::get('page/{content}', [ContentController::class, 'show'])->where('content', '[0-9]+')->name('front.content.show');
Route::get('feedback', [FeedbackController::class, 'create'])->name('front.feedback.create');
Route::post('feedback', [FeedbackController::class, 'store'])->name('front.feedback.store');
Route::match(['GET', 'POST'], 'ajax/{action}', [AjaxController::class, 'handle'])->name('front.ajax.handle');
