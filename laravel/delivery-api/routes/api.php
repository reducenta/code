<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/slots/availability', [\App\Http\Controllers\AvailabilityController::class, 'index'])->name('get_slots');
Route::post('/slots/{id}/hold', [\App\Http\Controllers\HoldController::class, 'store'])
    ->whereNumber('id')
    ->name('hold_slot');
Route::post('/holds/{id}/confirm', [\App\Http\Controllers\HoldController::class, 'confirm'])
    ->whereNumber('id')
    ->name('confirm_hold');
Route::delete('/holds/{id}', [\App\Http\Controllers\HoldController::class, 'cancel'])
    ->whereNumber('id')
    ->name('delete_hold');
