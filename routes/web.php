<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'api-playground')->name('api-playground');
Route::view('/api-tester', 'api-playground');
