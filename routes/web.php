<?php

use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/api/products', [ProductController::class, 'fetchProducts']);
Route::get('/download', [ProductController::class, 'downloadCsv']);
