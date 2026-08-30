<?php

namespace App\Services;

use App\Models\Pattern;
use App\Repositories\ProductRepository;
use GuzzleHttp\Client;

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
            $clientConfig = self::getClientConfig($productUrl);
            $client = new Client($clientConfig);
            $response = $client->get($productUrl);
            $html = $response->getBody()->getContents();

            return self::parseProduct($html, $productUrl) ?? null;
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
        $domain = strtolower(self::getDomain($url));
        $title  = self::extractData($html, $domain, 'title');
        $price  = self::extractData($html, $domain, 'price');
        $image  = self::extractData($html, $domain, 'image');

        return [
            'title'     => self::cleanText($title),
            'price'     => self::cleanPrice($price),
            'image_url' => self::cleanText($image)
        ];
    }

    private static function getDomain($url): string
    {
        $parsedUrl = parse_url($url);
        $domain = explode('.', $parsedUrl['host'])[1];

        return ($domain === 'amazon' || $domain === 'alibaba' || $domain === 'jumia' || $domain === 'breadcrumb') ? $domain : 'general';
    }

    private static function extractData(string $html, string $domain, string $type): ?string
    {
        $patterns = Pattern::where('type', $type)
            ->orderByRaw("CASE WHEN domain = '".$domain."' THEN 0 ELSE 1 END")
            ->get();

        foreach ($patterns as $pattern) {
            if (preg_match($pattern->name, $html, $matches)) {
                if($type === 'price' && $matches[1] == 0.0)
                    continue;
                return $matches[1];
            }
        }

        return null;
    }

    private static function cleanText(string $text): string
    {
        return trim(strip_tags($text));
    }

    private static function cleanPrice(string $price): float
    {
        $price = preg_replace('/[^0-9.]/', '', $price);
        return (float) $price;
    }
}
