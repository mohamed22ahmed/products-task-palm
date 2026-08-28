<?php

namespace App\Repositories;

use App\Models\Product;

class ProductRepository
{
    public static function getAll(){
        return Product::all();
    }
}
