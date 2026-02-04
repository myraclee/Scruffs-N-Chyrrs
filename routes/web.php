<?php

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

Route::get('/signup', function () {
    return view('customer.signup');
})->name('signup');

Route::get('/login', function () {
    return view('customer.login_customer');
})->name('login');
