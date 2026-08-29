<?php

namespace App\Repositories;

use App\Models\Product;

class ProductRepository
{
    public static function getAll(){
        return Product::all();
    }

    public static function create(array $data): Product
    {
        return Product::create([
            'title' => $data['title'],
            'price' => $data['price'],
            'image_url' => $data['image_url'],
        ]);
    }
}
