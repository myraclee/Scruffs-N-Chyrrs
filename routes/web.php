<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('customer.home');
})->name('home');

Route::get('/products', function () {
    return view('customer.products');
})->name('products');

Route::get('/contacts', function () {
    return view('customer.contacts');
})->name('contacts');

Route::get('/aboutus', function () {
    return view('customer.aboutus');
})->name('aboutus');

// Authentication Routes
Route::get('/signup', [AuthController::class, 'showSignup'])->name('signup');
Route::post('/signup', [AuthController::class, 'register'])->name('signup.store');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
