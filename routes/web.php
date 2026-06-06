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
    Route::get('user/{user}', [PhotoalbumsController::class, 'user'])->where('user', '[0-9]+')->name('user');
    Route::get('', [PhotoalbumsController::class, 'index'])->name('index');
});

Route::prefix('videoalbums')->name('front.videoalbums.')->group(function () {
    Route::get('user/{user}', [VideoalbumsController::class, 'user'])->where('user', '[0-9]+')->name('user');
    Route::get('', [VideoalbumsController::class, 'index'])->name('index');
});

Route::prefix('teams')->name('front.teams.')->group(function () {
    Route::get('user/{user}', [TeamsController::class, 'user'])->where('user', '[0-9]+')->name('user');
    Route::get('{community}/members', [TeamsController::class, 'members'])->where('community', '[0-9]+')->name('members');
    Route::get('{community}/photoalbums/add-photo', [TeamsController::class, 'addPhoto'])->where('community', '[0-9]+')->name('photoalbums.add-photo');
    Route::get('{community}/photoalbums/photo/{photo}', [TeamsController::class, 'photo'])->where(['community' => '[0-9]+', 'photo' => '[0-9]+'])->name('photoalbums.photo');
    Route::get('{community}/photoalbums', [TeamsController::class, 'photoalbums'])->where('community', '[0-9]+')->name('photoalbums');
    Route::get('{community}/photoalbum/{album}/edit', [TeamsController::class, 'editPhotoalbum'])->where(['community' => '[0-9]+', 'album' => '[0-9]+'])->name('photoalbum.edit');
    Route::get('{community}/videoalbums/add-video', [TeamsController::class, 'addVideo'])->where('community', '[0-9]+')->name('videoalbums.add-video');
    Route::get('{community}/videoalbums/create', [TeamsController::class, 'createVideoalbum'])->where('community', '[0-9]+')->name('videoalbums.create');
    Route::get('{community}/videoalbums', [TeamsController::class, 'videoalbums'])->where('community', '[0-9]+')->name('videoalbums');
    Route::get('{community}', [TeamsController::class, 'show'])->where('community', '[0-9]+')->name('show');
});

Route::get('page/{content}', [ContentController::class, 'show'])->where('content', '[0-9]+')->name('front.content.show');
Route::get('feedback', [FeedbackController::class, 'create'])->name('front.feedback.create');
Route::post('feedback', [FeedbackController::class, 'store'])->name('front.feedback.store');
Route::match(['GET', 'POST'], 'ajax/{action}', [AjaxController::class, 'handle'])->name('front.ajax.handle');
