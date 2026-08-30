<?php

namespace App\Repositories;

use App\Models\Product;

class ProductRepository
{
    public static function getAll(): \Illuminate\Database\Eloquent\Collection
    {
        return Product::all();
    }

    public static function show($id): Product
    {
        return Product::where('id', $id)->first();
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
