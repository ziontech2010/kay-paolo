<?php

use App\Http\Controllers\ZionApiProxyController;
use Illuminate\Support\Facades\Route;

Route::prefix('kay-paolo')->name('api.kay-paolo.')->group(function () {
    Route::post('/validate-tracking', [ZionApiProxyController::class, 'tracking'])->name('validate-tracking');
});
