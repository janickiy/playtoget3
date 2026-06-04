<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CatalogController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\DataTableController;
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

Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('login', [AuthController::class, 'login'])->name('login.submit');
Route::get('logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/', [HomeController::class, 'index'])->name('front.home');
Route::post('/', [FrontAuthController::class, 'login'])->name('front.login');
Route::post('front/logout', [FrontAuthController::class, 'logout'])->name('front.logout');

Route::get('news', [NewsController::class, 'index'])->name('front.news.index');
Route::get('profile/edit', [ProfileController::class, 'edit'])->name('front.profile.edit');
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
Route::get('events/create', [EventsController::class, 'create'])->name('front.events.create');
Route::get('events/{event}/members', [EventsController::class, 'members'])->where('event', '[0-9]+')->name('front.events.members');
Route::get('events/{event}/photoalbums', [EventsController::class, 'photoalbums'])->where('event', '[0-9]+')->name('front.events.photoalbums');
Route::get('events/{event}/videoalbums', [EventsController::class, 'videoalbums'])->where('event', '[0-9]+')->name('front.events.videoalbums');
Route::get('events/{event}', [EventsController::class, 'show'])->where('event', '[0-9]+')->name('front.events.show');
Route::get('events', [EventsController::class, 'index'])->name('front.events.index');

Route::get('friends/user/{user}', [FriendsController::class, 'user'])->where('user', '[0-9]+')->name('front.friends.user');
Route::get('friends', [FriendsController::class, 'index'])->name('front.friends.index');

Route::get('photoalbums/add-photo', [PhotoalbumsController::class, 'addPhoto'])->name('front.photoalbums.add-photo');
Route::get('photoalbums/create', [PhotoalbumsController::class, 'create'])->name('front.photoalbums.create');
Route::get('photoalbums/user/{user}', [PhotoalbumsController::class, 'user'])->where('user', '[0-9]+')->name('front.photoalbums.user');
Route::get('photoalbums', [PhotoalbumsController::class, 'index'])->name('front.photoalbums.index');

Route::get('videoalbums/user/{user}', [VideoalbumsController::class, 'user'])->where('user', '[0-9]+')->name('front.videoalbums.user');
Route::get('videoalbums', [VideoalbumsController::class, 'index'])->name('front.videoalbums.index');

Route::get('teams/user/{user}', [TeamsController::class, 'user'])->where('user', '[0-9]+')->name('front.teams.user');
Route::get('teams/{community}/members', [TeamsController::class, 'members'])->where('community', '[0-9]+')->name('front.teams.members');
Route::get('teams/{community}/photoalbums/add-photo', [TeamsController::class, 'addPhoto'])->where('community', '[0-9]+')->name('front.teams.photoalbums.add-photo');
Route::get('teams/{community}/photoalbums/photo/{photo}', [TeamsController::class, 'photo'])->where(['community' => '[0-9]+', 'photo' => '[0-9]+'])->name('front.teams.photoalbums.photo');
Route::get('teams/{community}/photoalbums', [TeamsController::class, 'photoalbums'])->where('community', '[0-9]+')->name('front.teams.photoalbums');
Route::get('teams/{community}/photoalbum/{album}/edit', [TeamsController::class, 'editPhotoalbum'])->where(['community' => '[0-9]+', 'album' => '[0-9]+'])->name('front.teams.photoalbum.edit');
Route::get('teams/{community}/videoalbums/add-video', [TeamsController::class, 'addVideo'])->where('community', '[0-9]+')->name('front.teams.videoalbums.add-video');
Route::get('teams/{community}/videoalbums/create', [TeamsController::class, 'createVideoalbum'])->where('community', '[0-9]+')->name('front.teams.videoalbums.create');
Route::get('teams/{community}/videoalbums', [TeamsController::class, 'videoalbums'])->where('community', '[0-9]+')->name('front.teams.videoalbums');
Route::get('teams/{community}', [TeamsController::class, 'show'])->where('community', '[0-9]+')->name('front.teams.show');

Route::get('page/{content}', [ContentController::class, 'show'])->where('content', '[0-9]+')->name('front.content.show');
Route::get('feedback', [FeedbackController::class, 'create'])->name('front.feedback.create');
Route::post('feedback', [FeedbackController::class, 'store'])->name('front.feedback.store');
Route::match(['GET', 'POST'], 'ajax/{action}', [AjaxController::class, 'handle'])->name('front.ajax.handle');

Route::get('cp', [DashboardController::class, 'index'])->name('admin.dashboard.index');

Route::group(['prefix' => 'admin'], function () {
    Route::get('', [AdminController::class, 'index'])->name('admin.admin.index');
    Route::get('create', [AdminController::class, 'create'])->name('admin.admin.create');
    Route::post('store', [AdminController::class, 'store'])->name('admin.admin.store');
    Route::get('edit/{id}', [AdminController::class, 'edit'])->name('admin.admin.edit')->where('id', '[0-9]+');
    Route::put('update', [AdminController::class, 'update'])->name('admin.admin.update');
    Route::delete('destroy/{id}', [AdminController::class, 'destroy'])->name('admin.admin.destroy')->where('id', '[0-9]+');
});

Route::group(['prefix' => 'catalog'], function () {
    Route::get('', [CatalogController::class, 'index'])->name('admin.catalog.index');
    Route::get('create', [CatalogController::class, 'create'])->name('admin.catalog.create');
    Route::post('store', [CatalogController::class, 'store'])->name('admin.catalog.store');
    Route::get('edit/{id}', [CatalogController::class, 'edit'])->name('admin.catalog.edit')->where('id', '[0-9]+');
    Route::put('update', [CatalogController::class, 'update'])->name('admin.catalog.update');
    Route::delete('destroy/{id}', [CatalogController::class, 'destroy'])->name('admin.catalog.destroy')->where('id', '[0-9]+');
});

Route::group(['prefix' => 'datatable'], function () {
    Route::any('admin', [DataTableController::class, 'admin'])->name('admin.datatable.admin')->middleware('permission:admin|moderator');
    Route::any('catalogs', [DataTableController::class, 'catalogs'])->name('admin.datatable.catalogs');
});
