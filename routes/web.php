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
Route::get('/account', [ZionSessionController::class, 'dashboard'])->name('account');

Route::get('/quote', fn () => view('pages.quote'))->name('quote');
Route::get('/quote-details', fn () => view('pages.quote-details'))->name('quote.details');
Route::get('/create-shipment', fn () => view('pages.create-shipment'))->name('create-shipment');
Route::get('/shipment-history', fn () => view('pages.shipment-history'))->name('shipment-history');
Route::get('/tracking', fn () => view('pages.tracking'))->name('tracking');
Route::get('/tracking-detail', fn () => view('pages.tracking-detail'))->name('tracking.detail');
Route::get('/shipment-confirmation', fn () => view('pages.shipment-confirmation'))->name('shipment.confirmation');
Route::redirect('/confirmation', '/shipment-confirmation');
Route::get('/invoice', fn () => view('pages.invoice'))->name('invoice');
Route::get('/receipt', fn () => view('pages.receipt'))->name('receipt');
Route::get('/receipt-a4', fn () => view('pages.receipt-a4'))->name('receipt.a4');

Route::redirect('/index.html', '/');
Route::redirect('/about.html', '/about');
Route::redirect('/services.html', '/services');
Route::redirect('/contact.html', '/contact');
Route::redirect('/blog.html', '/blog');
Route::redirect('/blog-post.html', '/blog/post');
Route::redirect('/login.html', '/login');
Route::redirect('/quote.html', '/quote');
Route::redirect('/quote-details.html', '/quote-details');
Route::redirect('/create-shipment.html', '/create-shipment');
Route::redirect('/shipment-history.html', '/shipment-history');
Route::redirect('/tracking.html', '/tracking');
Route::redirect('/tracking-detail.html', '/tracking-detail');
Route::redirect('/account.html', '/account');
Route::redirect('/confirmation.html', '/shipment-confirmation');
Route::redirect('/shipment-confirmation.html', '/shipment-confirmation');
Route::redirect('/invoice.html', '/invoice');
Route::redirect('/receipt.html', '/receipt');
Route::redirect('/receipt-a4.html', '/receipt-a4');

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
