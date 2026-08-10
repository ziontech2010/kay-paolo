<?php

use App\Http\Controllers\ZionApiProxyController;
use Illuminate\Support\Facades\Route;

Route::prefix('kay-paolo')->name('api.kay-paolo.')->group(function () {
    Route::post('/login', [ZionApiProxyController::class, 'login'])->name('login');
    Route::get('/countries', [ZionApiProxyController::class, 'countries'])->name('countries');
    Route::get('/payment-options', [ZionApiProxyController::class, 'paymentOptions'])->name('payment-options');
    Route::post('/fetch-user-for-quote', [ZionApiProxyController::class, 'fetchUserForQuote'])->name('fetch-user-for-quote');
    Route::post('/consignee-list', [ZionApiProxyController::class, 'consigneeList'])->name('consignee-list');
    Route::post('/flat-rates', [ZionApiProxyController::class, 'flatRates'])->name('flat-rates');
    Route::post('/save-consignee', [ZionApiProxyController::class, 'saveConsignee'])->name('save-consignee');
    Route::post('/quote', [ZionApiProxyController::class, 'quote'])->name('quote');
    Route::post('/shipping', [ZionApiProxyController::class, 'createShipment'])->name('shipping');
    Route::post('/store-shipment-document-context', [ZionApiProxyController::class, 'storeShipmentDocumentContext'])->name('store-shipment-document-context');
    Route::post('/shipping-history', [ZionApiProxyController::class, 'shippingHistory'])->name('shipping-history');
    Route::post('/void-shipping', [ZionApiProxyController::class, 'voidShipment'])->name('void-shipping');
    Route::post('/validate-tracking', [ZionApiProxyController::class, 'tracking'])->name('validate-tracking');
    Route::post('/email-shipment', [ZionApiProxyController::class, 'emailShipment'])->name('email-shipment');
});
