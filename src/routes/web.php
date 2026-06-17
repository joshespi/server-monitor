<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// TODO: Add auth middleware before exposing to a shared network, e.g. ->middleware('auth')
Route::get('/monitor', fn () => view('monitor'));
Route::get('/monitor/ports', fn () => view('ports'));
