<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\CommunitiesController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DataTableController;
use App\Http\Controllers\Admin\AjaxController;
use App\Http\Controllers\Admin\EventsController;
use App\Http\Controllers\Admin\UsersController;
use Illuminate\Support\Facades\Route;

Route::prefix('cp')->group(function () {
    Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.submit');
    Route::get('logout', [AuthController::class, 'logout'])->name('logout');

    Route::any('ajax', AjaxController::class)->name('admin.ajax');

    Route::middleware('auth:admin')->group(function () {
        Route::get('', [DashboardController::class, 'index'])->name('admin.dashboard.index');

        //Управление контентом
        Route::prefix('content')->group(function () {

            //Меню
            Route::any('manage-menus', [MenuController::class, 'index'])
                ->name('admin.menu.index')
                ->middleware(['permission:admin|moderator']);

            //Страницы
            Route::prefix('content')->group(function () {
                Route::get('', [ContentController::class, 'index'])->name('admin.content.index');
                Route::get('create', [ContentController::class, 'create'])->name('admin.content.create');
                Route::post('store', [ContentController::class, 'store'])->name('admin.content.store');
                Route::get('show/{id}', [ContentController::class, 'show'])->name('admin.content.show')->where('id', '[0-9]+');
                Route::get('edit/{id}', [ContentController::class, 'edit'])->name('admin.content.edit')->where('id', '[0-9]+');
                Route::put('update', [ContentController::class, 'update'])->name('admin.content.update');
                Route::delete('destroy/{id}', [ContentController::class, 'destroy'])->name('admin.content.destroy')->where('id', '[0-9]+');
            });
        });

        Route::prefix('admin')->name('admin.admin.')->group(function () {
            Route::get('', [AdminController::class, 'index'])->name('index');
            Route::get('create', [AdminController::class, 'create'])->name('create');
            Route::post('store', [AdminController::class, 'store'])->name('store');
            Route::get('edit/{id}', [AdminController::class, 'edit'])->where('id', '[0-9]+')->name('edit');
            Route::put('update', [AdminController::class, 'update'])->name('update');
            Route::delete('destroy/{id}', [AdminController::class, 'destroy'])->where('id', '[0-9]+')->name('destroy');
        });

        Route::prefix('users')->name('admin.users.')->middleware('permission:admin|moderator')->group(function () {
            Route::get('', [UsersController::class, 'index'])->name('index');
            Route::get('show/{id}', [UsersController::class, 'show'])->where('id', '[0-9]+')->name('show');
            Route::get('edit/{id}', [UsersController::class, 'edit'])->where('id', '[0-9]+')->name('edit');
            Route::put('update', [UsersController::class, 'update'])->name('update');
            Route::patch('block/{id}', [UsersController::class, 'block'])->where('id', '[0-9]+')->name('block');
            Route::patch('unblock/{id}', [UsersController::class, 'unblock'])->where('id', '[0-9]+')->name('unblock');
            Route::delete('destroy/{id}', [UsersController::class, 'destroy'])->where('id', '[0-9]+')->name('destroy');
            Route::post('bulk', [UsersController::class, 'bulk'])->name('bulk');
        });

        Route::prefix('communities')->name('admin.communities.')->middleware('permission:admin')->group(function () {
            Route::get('', [CommunitiesController::class, 'index'])->name('index');
            Route::get('show/{id}', [CommunitiesController::class, 'show'])->where('id', '[0-9]+')->name('show');
            Route::get('edit/{id}', [CommunitiesController::class, 'edit'])->where('id', '[0-9]+')->name('edit');
            Route::put('update', [CommunitiesController::class, 'update'])->name('update');
            Route::delete('destroy/{id}', [CommunitiesController::class, 'destroy'])->where('id', '[0-9]+')->name('destroy');
        });

        Route::prefix('events')->name('admin.events.')->middleware('permission:admin')->group(function () {
            Route::get('', [EventsController::class, 'index'])->name('index');
            Route::get('create', [EventsController::class, 'create'])->name('create');
            Route::post('store', [EventsController::class, 'store'])->name('store');
            Route::get('show/{id}', [EventsController::class, 'show'])->where('id', '[0-9]+')->name('show');
            Route::get('edit/{id}', [EventsController::class, 'edit'])->where('id', '[0-9]+')->name('edit');
            Route::put('update', [EventsController::class, 'update'])->name('update');
            Route::delete('destroy/{id}', [EventsController::class, 'destroy'])->where('id', '[0-9]+')->name('destroy');
        });

        Route::prefix('settings')->middleware('permission:admin')->group(function () {
            Route::get('', [SettingsController::class, 'index'])->name('admin.settings.index');
            Route::get('create/{type}', [SettingsController::class, 'create'])->name('admin.settings.create');
            Route::post('store', [SettingsController::class, 'store'])->name('admin.settings.store');
            Route::get('edit/{id}', [SettingsController::class, 'edit'])->name('admin.settings.edit')->where('id', '[0-9]+');
            Route::put('update', [SettingsController::class, 'update'])->name('admin.settings.update');
            Route::delete('destroy/{id}', [SettingsController::class, 'destroy'])->name('admin.settings.destroy')->where('id', '[0-9]+');
        });

        Route::prefix('datatable')->name('admin.datatable.')->group(function () {
            Route::any('admin', [DataTableController::class, 'admin'])->middleware('permission:admin|moderator')->name('admin');
            Route::any('users', [DataTableController::class, 'users'])->middleware('permission:admin|moderator')->name('users');
            Route::any('communities', [DataTableController::class, 'communities'])->middleware('permission:admin')->name('communities');
            Route::any('events', [DataTableController::class, 'events'])->middleware('permission:admin')->name('events');
            Route::any('content', [DataTableController::class, 'content'])->name('content');
            Route::any('settings', [DataTableController::class, 'settings'])->middleware('permission:admin')->name('settings');
        });
    });
});
