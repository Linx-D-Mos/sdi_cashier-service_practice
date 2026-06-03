<?php

use App\Http\Controllers\ReconcileBag\ReconcileBagController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/v1/collected-bags/{collectedBag}/reconcile', ReconcileBagController::class)
    ->middleware(['web', 'auth']);
