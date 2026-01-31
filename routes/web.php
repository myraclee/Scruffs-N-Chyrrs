<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('customer.home');
});

Route::get('/products', function () {
    return view('customer.products');
});

Route::get('/contacts', function () {
    return view('customer.contacts');
});

Route::get('/aboutus', function () {
    return view('customer.aboutus');
});

