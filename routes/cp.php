<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\PagesController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DataTableController;
use Illuminate\Support\Facades\Route;

Route::prefix('cp')->group(function () {
    Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.submit');
    Route::get('logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware('auth:admin')->group(function () {
        Route::get('', [DashboardController::class, 'index'])->name('admin.dashboard.index');

        //Управление контентом
        Route::prefix('content')->group(function () {

            //Меню
            Route::any('manage-menus', [MenuController::class, 'index'])
                ->name('admin.menu.index')
                ->middleware(['permission:admin|moderator']);

            //Страницы
            Route::prefix('pages')->group(function () {
                Route::get('', [PagesController::class, 'index'])->name('admin.pages.index');
                Route::get('create', [PagesController::class, 'create'])->name('admin.pages.create');
                Route::post('store', [PagesController::class, 'store'])->name('admin.pages.store');
                Route::get('edit/{id}', [PagesController::class, 'edit'])->name('admin.pages.edit')->where('id', '[0-9]+');
                Route::put('update', [PagesController::class, 'update'])->name('admin.pages.update');
                Route::delete('destroy/{id}', [PagesController::class, 'destroy'])->name('admin.pages.destroy')->where('id', '[0-9]+');
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
            Route::any('users', [DataTableController::class, ''])
            Route::any('settings', [DataTableController::class, 'settings'])->middleware('permission:admin')->name('settings');
        });
    });
});
