<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Http\Controllers\TestControlelr;
use App\Http\Controllers\VetLandingController;
Route::get('/import-vets', [TestControlelr::class, 'importPdfData']);


// using Query Builder
Route::get('/users-db', function () {
      //  dd('hi');
    $users = DB::table('users')->get();
    return response()->json($users);
});

// using Eloquent Model
Route::get('/users', function () {

    $users = User::all();
    return response()->json($users);
});

Route::get('/', function () {
 
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::get('/admin/users', [App\Http\Controllers\HomeController::class, 'users'])->name('admin.users');
Route::get('/admin/sp/{id}', [App\Http\Controllers\HomeController::class, 'sp_profile'])->name('admin.sp_profile');
Route::get('/admin/bookings', [App\Http\Controllers\HomeController::class, 'bookings'])->name('admin.bookings');


Route::get('/admin/supports', [App\Http\Controllers\HomeController::class, 'supports'])->name('admin.supports');


;



Route::get('/backend/vet/{slug}', [VetLandingController::class, 'show'])
     ->name('vet.landing');