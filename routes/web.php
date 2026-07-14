<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Admin\UserController;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'show'])->name('register'); 
    Route::post('/register', [RegisterController::class, 'store']);
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('redis.throttle:5,1');
});

// Public Product Viewing (anyone can view)
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');

// eSewa Payment Callbacks (no auth middleware — eSewa redirects the browser back)
Route::get('/payment/esewa/success', [PaymentController::class, 'success'])->name('payment.esewa.success');
Route::get('/payment/esewa/failure', [PaymentController::class, 'failure'])->name('payment.esewa.failure');

// Khalti Payment Callbacks (no auth middleware — Khalti redirects the browser back)
Route::get('/payment/khalti/callback', [PaymentController::class, 'khaltiCallback'])->name('payment.khalti.callback');

// FonePay Payment Callbacks (no auth middleware — FonePay redirects the browser back)
Route::get('/payment/fonepay/callback', [PaymentController::class, 'fonepayCallback'])->name('payment.fonepay.callback');

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
    
    Route::post('/logout', [LogoutController::class, 'logout'])->name('logout');

    // eSewa Payment Initiation (must be logged in to buy)
    Route::post('/payment/esewa/{product}', [PaymentController::class, 'initiate'])->name('payment.esewa.initiate')->middleware('redis.throttle:10,1');

    // Khalti Payment Initiation (must be logged in to buy)
    Route::post('/payment/khalti/{product}', [PaymentController::class, 'khaltiInitiate'])->name('payment.khalti.initiate')->middleware('redis.throttle:10,1');

    // FonePay Payment Initiation (must be logged in to buy)
    Route::post('/payment/fonepay/{product}', [PaymentController::class, 'fonepayInitiate'])->name('payment.fonepay.initiate')->middleware('redis.throttle:10,1');
});

// Admin Routes (only for admins)
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    
    // Product Management
    Route::resource('products', ProductController::class)->except(['index', 'show']);
    Route::get('/products', [ProductController::class, 'adminIndex'])->name('products.index');
    
    // User Management
    Route::resource('users', UserController::class);
    Route::put('/users/{user}/promote', [UserController::class, 'promote'])->name('users.promote');
    Route::put('/users/{user}/demote', [UserController::class, 'demote'])->name('users.demote');
});