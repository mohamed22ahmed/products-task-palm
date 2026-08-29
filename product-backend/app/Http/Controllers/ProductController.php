<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Services\ProductService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(){
        return ProductService::getAllProducts();
    }

    public function scrape(ProductRequest $request){
        $product = ProductService::scrapeAndSaveProduct($request->url);

        if (!$product) {
            return response()->json(['error' => 'Failed to scrape product'], 500);
        }

        return new ProductResource($product);
    }
}
