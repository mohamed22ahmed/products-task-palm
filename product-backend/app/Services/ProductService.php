<?php

namespace App\Services;

use App\Repositories\ProductRepository;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class ProductService
{
    public static function getAllProducts(){
        return ProductRepository::getAll();
    }

    public static function scrapeAndSaveProduct(string $productUrl): ?\App\Models\Product
    {
        $productData = self::scrapeProduct($productUrl);

        if (!$productData) {
            return null;
        }
        return ProductRepository::create($productData);
    }

    public static function scrapeProduct(string $productUrl): ?array
    {
        $maxRetries = 3;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            $userAgent = 'linux';
            $clientConfig = [
                'headers' => $userAgent,
                'timeout' => 30,
                'allow_redirects' => ['max' => 5],
                'cookies' => true,
                'verify' => false,
            ];

            $client = new Client($clientConfig);

            try {
                $response = $client->get($productUrl);
                $html = $response->getBody()->getContents();

                return self::parseProduct($html, $productUrl);

            } catch (RequestException $e) {
                Log::error('Failed to fetch product page on attempt ' . $attempt . ': ' . $e->getMessage());

                if ($attempt === $maxRetries) {
                    return null;
                }

                sleep(2);
            }
        }

        return null;
    }

    private static function parseProduct(string $html, string $url): ?array
    {
        return [
            'title'     => 'title',
            'price'     => 'price',
            'image_url' => 'https://via.placeholder.com/300',
        ];
    }
}
