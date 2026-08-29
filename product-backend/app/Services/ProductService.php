<?php

namespace App\Services;

use App\Repositories\ProductRepository;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use App\Services\ProxyManagementService;

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
            $clientConfig = self::getClientConfig($productUrl);
            $client = new Client($clientConfig);
            $response = $client->get($productUrl);
            $html = $response->getBody()->getContents();

            return self::parseProduct($html, $productUrl);
        }

        return null;
    }

    private static function getClientConfig($productUrl): array
    {
        $proxyService = new ProxyManagementService();

        $proxyInfo = $proxyService->getProxyAndUserAgent($productUrl);

        $userAgent = $proxyInfo['user_agent'] ?? 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
        $realisticHeaders = self::generateRealisticHeaders($userAgent);

        $clientConfig = [
            'headers' => $realisticHeaders,
            'timeout' => 30,
            'allow_redirects' => ['max' => 5],
            'cookies' => true,
            'verify' => false,
        ];

        if ($proxyInfo['proxy'] && $proxyInfo['proxy'] !== 'direct') {
            $clientConfig['proxy'] = $proxyInfo['proxy'];
        }

        return $clientConfig;
    }

    private static function generateRealisticHeaders(string $userAgent): array
    {
        $headers = [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Connection' => 'keep-alive',
            'Upgrade-Insecure-Requests' => '1',
            'Sec-Fetch-Dest' => 'document',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => 'none',
            'Sec-Fetch-User' => '?1',
            'Cache-Control' => 'max-age=0',
        ];

        $platform = '"Windows"';
        if (strpos($userAgent, 'Macintosh') !== false || strpos($userAgent, 'Mac OS X') !== false) {
            $platform = '"macOS"';
        } elseif (strpos($userAgent, 'Linux') !== false) {
            $platform = '"Linux"';
        }

        if (strpos($userAgent, 'Chrome') !== false && strpos($userAgent, 'Edg') === false) {
            $headers['Sec-Ch-Ua'] = '"Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"';
            $headers['Sec-Ch-Ua-Mobile'] = '?0';
            $headers['Sec-Ch-Ua-Platform'] = $platform;
        }

        if (strpos($userAgent, 'Firefox') !== false) {
            $headers['DNT'] = '1';
        }

        return $headers;
    }

    private static function parseProduct(string $html, string $url): ?array
    {
        return [
            'title'     => 'title',
            'price'     => 0,
            'image_url' => 'https://via.placeholder.com/300',
        ];
    }
}
