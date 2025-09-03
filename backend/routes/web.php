<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::get('/admin/users', [App\Http\Controllers\HomeController::class, 'users'])->name('admin.users');
Route::get('/admin/sp/{id}', [App\Http\Controllers\HomeController::class, 'sp_profile'])->name('admin.sp_profile');
Route::get('/admin/bookings', [App\Http\Controllers\HomeController::class, 'bookings'])->name('admin.bookings');


Route::get('/admin/supports', [App\Http\Controllers\HomeController::class, 'supports'])->name('admin.supports');
