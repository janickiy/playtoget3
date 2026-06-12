<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CatalogController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DataTableController;
use Illuminate\Support\Facades\Route;

Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('login', [AuthController::class, 'login'])->name('login.submit');
Route::get('logout', [AuthController::class, 'logout'])->name('logout');

Route::get('cp', [DashboardController::class, 'index'])->name('admin.dashboard.index');

Route::prefix('admin')->name('admin.admin.')->group(function () {
    Route::get('', [AdminController::class, 'index'])->name('index');
    Route::get('create', [AdminController::class, 'create'])->name('create');
    Route::post('store', [AdminController::class, 'store'])->name('store');
    Route::get('edit/{id}', [AdminController::class, 'edit'])->where('id', '[0-9]+')->name('edit');
    Route::put('update', [AdminController::class, 'update'])->name('update');
    Route::delete('destroy/{id}', [AdminController::class, 'destroy'])->where('id', '[0-9]+')->name('destroy');
});

Route::prefix('catalog')->name('admin.catalog.')->group(function () {
    Route::get('', [CatalogController::class, 'index'])->name('index');
    Route::get('create', [CatalogController::class, 'create'])->name('create');
    Route::post('store', [CatalogController::class, 'store'])->name('store');
    Route::get('edit/{id}', [CatalogController::class, 'edit'])->where('id', '[0-9]+')->name('edit');
    Route::put('update', [CatalogController::class, 'update'])->name('update');
    Route::delete('destroy/{id}', [CatalogController::class, 'destroy'])->where('id', '[0-9]+')->name('destroy');
});

Route::middleware(['permission:admin'])->group(function () {
    Route::group(['prefix' => 'settings'], function () {
        Route::get('', [SettingsController::class, 'index'])->name('admin.settings.index');
        Route::get('create/{type}', [SettingsController::class, 'create'])->name('admin.settings.create');
        Route::post('store', [SettingsController::class, 'store'])->name('admin.settings.store');
        Route::get('edit/{id}', [SettingsController::class, 'edit'])->name('admin.settings.edit')->where('id', '[0-9]+');
        Route::put('update', [SettingsController::class, 'update'])->name('admin.settings.update');
        Route::delete('destroy/{id}', [SettingsController::class, 'destroy'])->name('admin.settings.destroy')->where('id', '[0-9]+');
    });
});

Route::prefix('datatable')->name('admin.datatable.')->group(function () {
    Route::any('admin', [DataTableController::class, 'admin'])->middleware('permission:admin|moderator')->name('admin');
    Route::any('catalogs', [DataTableController::class, 'catalogs'])->name('catalogs');
});
