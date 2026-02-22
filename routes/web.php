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

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');

Route::middleware('auth')->group(function () {
    Route::get('/account', [AuthController::class, 'account'])->name('account');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/account/edit', [AuthController::class, 'showEditProfile'])->name('edit-profile');
    Route::post('/account/update', [AuthController::class, 'updateProfile'])->name('update-profile');
    Route::get('/account/change-password', [AuthController::class, 'showChangePassword'])->name('change-password');
    Route::post('/account/update-password', [AuthController::class, 'updatePassword'])->name('update-password');
});

// OWNER ROUTES
Route::middleware(['auth', 'owner'])->group(function () {
    Route::get('/owner/pages/dashboard', function () {
        return view('owner.pages.dashboard');
    })->name('owner.dashboard');

    Route::get('/owner/pages/inventory', function () {
        return view('owner.pages.inventory');
    })->name('owner.inventory');

    Route::get('/owner/pages/orders', function () {
        return view('owner.pages.orders');
    })->name('owner.orders');

    Route::get('/owner/pages/content_management', function () {
        return view('owner.pages.content_management');
    })->name('owner.content');
});
