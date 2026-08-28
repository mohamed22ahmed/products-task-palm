<?php

namespace App\Services;

use App\Repositories\ProductRepository;

class ProductService
{
    public static function getAllProducts(): \Illuminate\Database\Eloquent\Collection
    {
        return ProductRepository::getAll();
    }
}
