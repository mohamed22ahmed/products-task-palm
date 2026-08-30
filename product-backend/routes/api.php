<?php
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ScraperController;
use Illuminate\Support\Facades\Route;

Route::controller(ProductController::class)
    ->prefix('v1/products')
    ->group(function () {
        Route::get('/', 'index');
        Route::post('/scrape', 'scrape');
});

Route::controller(ScraperController::class)
    ->prefix('v1/scraper')
    ->group(function () {
        Route::post('/scrape', 'scrape');
        Route::post('/batch', 'batchScrape');
});
