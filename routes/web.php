<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\MaterialController;
use App\Http\Controllers\Api\HomeImageController;
use App\Http\Controllers\Api\ProductSampleController;
use App\Http\Controllers\Api\OrderTemplateController;
use App\Http\Controllers\Api\FaqController;
use App\Http\Controllers\Api\FAQCategoryController;
use App\Http\Controllers\Api\RushFeeController;
use App\Http\Controllers\Api\CustomerOrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('customer.pages.home');
})->name('home');

Route::get('/products', function () {
    return view('customer.pages.products');
})->name('products');

Route::get('/products/{product}', function (App\Models\Product $product) {
    return view('customer.pages.product-detail', compact('product'));
})->name('product.detail');

Route::get('/faqs', function () {
    return view('customer.pages.faqs');
})->name('faqs');

Route::get('/rush', function () {
    return view('customer.pages.rush_fees');
})->name('rush');

Route::get('/aboutus', function () {
    return view('customer.pages.aboutus');
})->name('aboutus');

// PASSWORD RESETS
Route::get('/password/reset', [AuthController::class, 'showResetPassword'])->name('reset-password');
Route::post('/password/reset', [AuthController::class, 'sendResetCode'])->name('reset-password.send');

Route::get('/password/code', [AuthController::class, 'showEnterCode'])->name('enter-code');
Route::post('/password/code', [AuthController::class, 'verifyResetCode'])->name('enter-code.verify');

Route::get('/password/new', [AuthController::class, 'showNewPassword'])->name('new-password');
Route::post('/password/new', [AuthController::class, 'resetPassword'])->name('new-password.reset');

// AUTHENTICATION ROUTES
Route::get('/signup', [AuthController::class, 'showSignup'])->name('signup');
Route::post('/signup', [AuthController::class, 'register'])->name('signup.store');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::get('/owner/login', [AuthController::class, 'showOwnerLogin'])->name('owner.login');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');

Route::middleware('auth')->group(function () {
    Route::get('/account', [AuthController::class, 'account'])->name('account');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/account/edit', [AuthController::class, 'showEditProfile'])->name('edit-profile');
    Route::post('/account/update', [AuthController::class, 'updateProfile'])->name('update-profile');
    Route::get('/account/change-password', [AuthController::class, 'showChangePassword'])->name('change-password');
    Route::post('/account/update-password', [AuthController::class, 'updatePassword'])->name('update-password');
});

//View Order
Route::middleware('auth')->group(function () {
    Route::get('/account/orders', function () {
        return view('customer.view_orders'); // your Blade for customers
    })->name('customer.orders');
});

// API ROUTES - Products and Materials
Route::prefix('api/products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::post('/', [ProductController::class, 'store']);
    Route::get('{productId}', [ProductController::class, 'show']);
    Route::put('{productId}', [ProductController::class, 'update']);
    Route::delete('{productId}', [ProductController::class, 'destroy']);
});

Route::prefix('api/materials')->group(function () {
    Route::get('/', [MaterialController::class, 'index']);
    Route::post('/', [MaterialController::class, 'store']);
    Route::get('{material}', [MaterialController::class, 'show']);
    Route::put('{material}', [MaterialController::class, 'update']);
    Route::delete('{material}', [MaterialController::class, 'destroy']);
});

Route::prefix('api/home-images')->group(function () {
    Route::get('/', [HomeImageController::class, 'index']);
    Route::post('/', [HomeImageController::class, 'store']);
    Route::delete('{homeImage}', [HomeImageController::class, 'destroy']);
});

Route::prefix('api/product-samples')->group(function () {
    Route::get('/', [ProductSampleController::class, 'index']);
    Route::post('/', [ProductSampleController::class, 'store']);
    Route::get('{productSample}', [ProductSampleController::class, 'show']);
    Route::put('{productSample}', [ProductSampleController::class, 'update']);
    Route::delete('{productSample}', [ProductSampleController::class, 'destroy']);
    Route::delete('images/{productSampleImage}', [ProductSampleController::class, 'destroyImage']);
});

Route::prefix('api/order-templates')->group(function () {
    Route::get('/', [OrderTemplateController::class, 'index']);
    Route::post('/', [OrderTemplateController::class, 'store']);
    Route::get('{orderTemplate}', [OrderTemplateController::class, 'show']);
    Route::put('{orderTemplate}', [OrderTemplateController::class, 'update']);
    Route::delete('{orderTemplate}', [OrderTemplateController::class, 'destroy']);
});

// CUSTOMER ORDERS ROUTES - Public read for templates, auth required for POST
Route::prefix('api/customer-orders')->group(function () {
    Route::get('product/{productId}/template', [CustomerOrderController::class, 'getProductOrderTemplate']);
    Route::post('/', [CustomerOrderController::class, 'store'])->middleware('auth');
});

// FAQ ROUTES - Public read, auth required for write operations
Route::prefix('api/faqs')->group(function () {
    Route::get('/', [FaqController::class, 'index']);
    Route::post('/', [FaqController::class, 'store'])->middleware('auth');
    Route::put('{faq}', [FaqController::class, 'update'])->middleware('auth');
    Route::delete('{faq}', [FaqController::class, 'destroy'])->middleware('auth');
    Route::post('/reorder', [FaqController::class, 'reorder'])->middleware('auth');
    Route::get('/admin/index', [FaqController::class, 'adminIndex'])->middleware('auth');
});

// FAQ CATEGORIES ROUTES - Owner auth required for all operations
Route::prefix('api/faq-categories')->middleware(['auth', 'owner'])->group(function () {
    Route::get('/', [FAQCategoryController::class, 'index']);
    Route::post('/', [FAQCategoryController::class, 'store']);
    Route::put('{faqCategory}', [FAQCategoryController::class, 'update']);
    Route::delete('{faqCategory}', [FAQCategoryController::class, 'destroy']);
});

// RUSH FEES ROUTES - Public read, owner auth required for write operations
Route::prefix('api/rush-fees')->group(function () {
    Route::get('/', [RushFeeController::class, 'index']);
    Route::post('/upload-image', [RushFeeController::class, 'uploadImage'])->middleware(['auth', 'owner']);
    Route::post('/', [RushFeeController::class, 'store'])->middleware(['auth', 'owner']);
    Route::put('{rushFee}', [RushFeeController::class, 'update'])->middleware(['auth', 'owner']);
    Route::delete('{rushFee}', [RushFeeController::class, 'destroy'])->middleware(['auth', 'owner']);
    Route::patch('{rushFee}/reorder-timeframes', [RushFeeController::class, 'reorderTimeframes'])->middleware(['auth', 'owner']);
    Route::get('/admin/index', [RushFeeController::class, 'adminIndex'])->middleware(['auth', 'owner']);
});

// OWNER ROUTES
Route::middleware(['auth', 'owner'])->group(function () {

    // THE UPDATED DASHBOARD ROUTE!
    Route::get('/owner/pages/dashboard', function () {
        $launchYear = 2026;
        $currentYear = now()->year;
        $availableYears = range($launchYear, $currentYear);

        return view('owner.pages.dashboard', compact('availableYears', 'currentYear'));
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
