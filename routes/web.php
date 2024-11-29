<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/batch-api', function (Request $request) {
    $subscribers = collect($request->subscribers);
    $batchIndex = (int) $request->batch_index;
    foreach ($subscribers->values() as $subscriberIndex => $subscriber) {
        $updates = collect($subscriber)->except('email')->map(function ($value, $key) {
            return "$key: '$value'";
        })->join(', ');
        // Log the formatted message
        $logIndex = $batchIndex * 2 + $subscriberIndex;
        Log::info("[$logIndex] $updates");
    }
    return response()->json(['success' => true]);
})->name('batch-api');

Route::get('/single-api', function (Request $request) {
    $subscriber = collect($request->subscriber);
    $index = (int) $request->batch_index;
    // Add log for updates
    $updates = collect($subscriber)->except('email')->map(function ($value, $key) {
        return "$key: '$value'";
    })->join(', ');
    // Log the formatted message
    $logIndex = $index + 50000;
    Log::info("[$logIndex] $updates");
    return response()->json(['success' => true]);
})->name('single-api');