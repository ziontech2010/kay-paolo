<?php

use App\Http\Controllers\PageController;
use App\Http\Controllers\ZionApiProxyController;
use App\Http\Controllers\ZionSessionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/services', [PageController::class, 'services'])->name('services');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::get('/blog', [PageController::class, 'blog'])->name('blog');
Route::get('/blog/post', [PageController::class, 'blogPost'])->name('blog.post');

Route::get('/login', [ZionSessionController::class, 'showLogin'])->name('login');
Route::post('/login', [ZionSessionController::class, 'login'])->name('login.submit');
Route::post('/logout', [ZionSessionController::class, 'logout'])->name('logout');
Route::get('/dashboard', [ZionSessionController::class, 'dashboard'])->name('dashboard');

Route::get('/quote', fn () => view('pages.quote'))->name('quote');
Route::get('/tracking', fn () => view('pages.tracking'))->name('tracking');

Route::prefix('zion-api')->name('zion-api.')->group(function () {
    Route::post('/fetch-user-for-quote', [ZionApiProxyController::class, 'fetchUserForQuote'])->name('fetch-user-for-quote');
    Route::post('/consignee-list', [ZionApiProxyController::class, 'consigneeList'])->name('consignee-list');
    Route::post('/flat-rates', [ZionApiProxyController::class, 'flatRates'])->name('flat-rates');
    Route::post('/save-consignee', [ZionApiProxyController::class, 'saveConsignee'])->name('save-consignee');
    Route::post('/quote', [ZionApiProxyController::class, 'quote'])->name('quote');
    Route::post('/shipping', [ZionApiProxyController::class, 'createShipment'])->name('shipping');
    Route::post('/shipping-history', [ZionApiProxyController::class, 'shippingHistory'])->name('shipping-history');
    Route::post('/tracking', [ZionApiProxyController::class, 'tracking'])->name('tracking');
});
