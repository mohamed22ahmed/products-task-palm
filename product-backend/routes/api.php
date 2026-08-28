<?php
use App\Http\Controllers\ProductController;


Route::controller(ProductController::class)
    ->prefix('v1')
    ->group(function () {
        Route::get('/products', 'index');
});
