<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

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

// PASSWORD RESETS

Route::get('/password/reset', function () {
    return view('customer.password_page.reset_password');
})->name('reset-password');

Route::get('/password/code', function () {
    return view('customer.password_page.enter_code');
})->name('enter-code');

Route::get('/password/new', function () {
    return view('customer.password_page.new_password');
})->name('new-password');

// AUTHENTICATION ROUTES
Route::get('/signup', [AuthController::class, 'showSignup'])->name('signup');
Route::post('/signup', [AuthController::class, 'register'])->name('signup.store');

// account route
Route::get('/account', [AuthController::class, 'account'])
    ->middleware('auth')
    ->name('account');

//Log Out Route 
Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// Pop Up
// Signup
Route::get('/signup', [AuthController::class, 'showSignup'])->name('signup');
Route::post('/signup', [AuthController::class, 'register'])->name('signup.store');

// Login
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');

// Account & Logout
Route::get('/account', [AuthController::class, 'account'])->middleware('auth')->name('account');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// OWNER
Route::get('/owner/pages/dashboard', function () {
    return view('owner.pages.dashboard');
})->name('owner.dashboard');

Route::get('/owner/pages/inventory', function () {
    return view('owner.pages.inventory');
})->name('owner.inventory');

Route::get('/owner/pages/orders', function () {
    return view('owner.pages.orders');
})->name('owner.orders');

Route::get('/owner/pages/content', function () {
    return view('owner.pages.content');
})->name('owner.content');