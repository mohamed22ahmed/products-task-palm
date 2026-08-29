<?php

namespace App\Services;

use App\Repositories\ProductRepository;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class ProductService
{
    private static function getProxyAndUserAgent(string $targetUrl): ?array
    {
        $proxyServiceUrl = env('PROXY_SERVICE_URL', 'http://localhost:8080');

        try {
            $client = new Client(['timeout' => 5]);
            $response = $client->post($proxyServiceUrl . '/proxy', [
                'json' => ['target_url' => $targetUrl],
                'headers' => ['Content-Type' => 'application/json']
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return [
                'proxy' => $data['proxy'] ?? null,
                'user_agent' => $data['user_agent'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get proxy from service: ' . $e->getMessage());
            // Fallback to direct connection if proxy service is unavailable
            return [
                'proxy' => null,
            ];
        }
    }
    public static function scrapeProduct(string $productUrl): ?array
    {
        $maxRetries = env('CAPTCHA_MAX_RETRIES', 3);

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            $proxyInfo = self::getProxyAndUserAgent($productUrl);
            $userAgent = $proxyInfo['user_agent'];

            // Generate realistic headers based on user agent
            $realisticHeaders = self::generateRealisticHeaders($userAgent);
            $realisticHeaders['User-Agent'] = $userAgent;

            $clientConfig = [
                'headers' => $realisticHeaders,
                'timeout' => 30,
                'allow_redirects' => ['max' => 5],
                'cookies' => true,
                'verify' => false,
            ];

            $client = new Client($clientConfig);

            try {
                $response = $client->get($productUrl);
                $html = $response->getBody()->getContents();

                // Check if CAPTCHA page (but don't try to solve it)
                if (self::isCaptchaPage($html)) {
                    Log::warning('CAPTCHA page detected on attempt ' . $attempt . ' of ' . $maxRetries);

                    // Don't try to solve CAPTCHA, just fail gracefully
                    if ($attempt === $maxRetries) {
                        Log::error('Max retries reached, giving up due to CAPTCHA');
                        return null;
                    }

                    // Wait before retry
                    sleep(3);
                    continue;
                }

                // No CAPTCHA, parse the product
                return self::parseProduct($html, $productUrl);

            } catch (RequestException $e) {
                Log::error('Failed to fetch product page on attempt ' . $attempt . ': ' . $e->getMessage());

                if ($attempt === $maxRetries) {
                    return null;
                }

                // Wait before retry
                sleep(2);
            }
        }

        return null;
    }

    // Keep backward compatibility
    public static function scrapeAmazonProduct(string $productUrl): ?array
    {
        return self::scrapeProduct($productUrl);
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

        // Detect platform from user agent
        $platform = '"Windows"';
        if (strpos($userAgent, 'Macintosh') !== false || strpos($userAgent, 'Mac OS X') !== false) {
            $platform = '"macOS"';
        } elseif (strpos($userAgent, 'Linux') !== false) {
            $platform = '"Linux"';
        }

        // Add Chrome-specific headers if Chrome user agent
        if (strpos($userAgent, 'Chrome') !== false && strpos($userAgent, 'Edg') === false) {
            $headers['Sec-Ch-Ua'] = '"Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"';
            $headers['Sec-Ch-Ua-Mobile'] = '?0';
            $headers['Sec-Ch-Ua-Platform'] = $platform;
        }

        // Add Firefox-specific headers if Firefox user agent
        if (strpos($userAgent, 'Firefox') !== false) {
            $headers['DNT'] = '1';
        }

        return $headers;
    }

    private static function parseProduct(string $html, string $url): ?array
    {
        // Check if this is actually a CAPTCHA page (more strict detection)
        if (self::isCaptchaPage($html)) {
            Log::warning('CAPTCHA page detected, skipping extraction');
            return null;
        }

        // First try to extract from JavaScript data (Vue.js, React, etc.)
        $jsData = self::extractFromJavaScriptData($html);
        if ($jsData) {
            Log::info('Extracted data from JavaScript - Title: ' . ($jsData['title'] ? 'found' : 'not found') . ', Price: ' . ($jsData['price'] ? 'found' : 'not found') . ', Image: ' . ($jsData['image'] ? 'found' : 'not found'));

            if ($jsData['title'] && $jsData['price']) {
                return [
                    'title' => self::cleanText($jsData['title']),
                    'price' => self::cleanPrice($jsData['price']),
                    'image_url' => $jsData['image'] ?: 'https://via.placeholder.com/300',
                ];
            }
        }

        // Fall back to HTML parsing
        $title = self::extractTitle($html);
        $price = self::extractPrice($html);
        $image = self::extractImage($html);

        Log::info('Extracted data from HTML - Title: ' . ($title ? 'found' : 'not found') . ', Price: ' . ($price ? 'found' : 'not found') . ', Image: ' . ($image ? 'found' : 'not found'));

        if (!$title || !$price) {
            Log::warning('Failed to extract required product data');
            return null;
        }

        return [
            'title' => self::cleanText($title),
            'price' => self::cleanPrice($price),
            'image_url' => $image ?: 'https://via.placeholder.com/300',
        ];
    }

    private static function isCaptchaPage(string $html): bool
    {
        // More strict CAPTCHA detection to avoid false positives
        $captchaIndicators = [
            // Platform-specific CAPTCHA indicators (must be very specific)
            '/punish-component\s*\/>/i',        // Alibaba - very specific
            '/aws-captcha\s/i',                   // AWS/Amazon - very specific
            '/g-recaptcha.*iframe/i',             // Google reCAPTCHA iframe
            '/hcaptcha.*iframe/i',               // hCaptcha iframe
            '/cf-challenge.*cloudflare/i',        // Cloudflare challenge
            '/turnstile.*challenge/i',           // Cloudflare Turnstile

            // Strong indicators that this is a CAPTCHA page
            '/security.*verification.*required/i',
            '/type the characters.*below/i',
            '/prove.*you.*are.*human/i',
            '/are.*you.*robot/i',
            '/detecting.*unusual.*activity/i',
        ];

        foreach ($captchaIndicators as $indicator) {
            if (preg_match($indicator, $html)) {
                return true;
            }
        }

        // Only return true if multiple CAPTCHA indicators are present
        $captchaCount = 0;

        // Check for common CAPTCHA-related HTML structures
        if (strpos($html, 'punish-component') !== false) $captchaCount++;      // Alibaba
        if (strpos($html, 'awsc-captcha') !== false) $captchaCount++;         // AWS/Amazon
        if (strpos($html, 'g-recaptcha') !== false) $captchaCount++;          // Google reCAPTCHA
        if (strpos($html, 'h-captcha') !== false) $captchaCount++;            // hCaptcha
        if (strpos($html, 'cf-challenge') !== false) $captchaCount++;         // Cloudflare

        // Only consider it a CAPTCHA page if multiple indicators are present
        return $captchaCount >= 2;
    }

    private static function extractCaptchaImage(string $html): ?string
    {
        // Try to extract CAPTCHA image from various patterns
        $patterns = [
            // Amazon CAPTCHA images
            '/<img[^>]*id="captcha[^"]*"[^>]*src="([^"]+)"/i',
            '/<img[^>]*class="[^"]*captcha[^"]*"[^>]*src="([^"]+)"/i',

            // General CAPTCHA images
            '/<img[^>]*alt="[^"]*captcha[^"]*"[^>]*src="([^"]+)"/i',
            '/<img[^>]*src="([^"]*captcha[^"]*)"/i',

            // Alibaba specific
            '/<img[^>]*class="[^"]*awsc[^"]*"[^>]*src="([^"]+)"/i',

            // Base64 encoded images
            '/<img[^>]*src="data:image\/([^;]+);base64,([^"]+)"/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                if (isset($matches[2]) && strpos($matches[1], 'base64') !== false) {
                    // Handle base64 encoded image
                    return 'data:image/' . $matches[1] . ';base64,' . $matches[2];
                } elseif (isset($matches[1])) {
                    return $matches[1];
                }
            }
        }

        return null;
    }

    private static function solveCaptcha(string $captchaImage): ?string
    {
        $apiKey = env('CAPTCHA_API_KEY');
        if (empty($apiKey)) {
            Log::warning('CAPTCHA API key not configured');
            return null;
        }

        $captchaEnabled = env('CAPTCHA_SOLVING_ENABLED', false);
        if (!$captchaEnabled) {
            Log::warning('CAPTCHA solving is disabled in configuration');
            return null;
        }

        try {
            // Step 1: Submit CAPTCHA to 2Captcha
            $client = new Client(['timeout' => 30]);

            // Handle base64 images
            if (strpos($captchaImage, 'data:image') === 0) {
                // For base64 images, we need to extract the base64 data
                if (preg_match('/data:image\/[^;]+;base64,(.+)/', $captchaImage, $matches)) {
                    $base64Data = $matches[1];
                    $captchaImage = $base64Data;
                }
            }

            $response = $client->post('http://2captcha.com/in.php', [
                'form_params' => [
                    'key' => $apiKey,
                    'method' => 'base64',
                    'body' => $captchaImage,
                    'json' => 1,
                ]
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            if (!isset($result['status']) || $result['status'] !== 1) {
                Log::error('Failed to submit CAPTCHA: ' . json_encode($result));
                return null;
            }

            $captchaId = $result['request'];
            Log::info('CAPTCHA submitted successfully, ID: ' . $captchaId);

            // Step 2: Poll for solution
            $timeout = env('CAPTCHA_TIMEOUT', 120);
            $maxRetries = env('CAPTCHA_MAX_RETRIES', 30);
            $sleepTime = 2;

            for ($i = 0; $i < $maxRetries; $i++) {
                sleep($sleepTime);

                $response = $client->get('http://2captcha.com/res.php', [
                    'query' => [
                        'key' => $apiKey,
                        'action' => 'get',
                        'id' => $captchaId,
                        'json' => 1,
                    ]
                ]);

                $result = json_decode($response->getBody()->getContents(), true);

                if (isset($result['status']) && $result['status'] === 1) {
                    Log::info('CAPTCHA solved successfully: ' . $result['request']);
                    return $result['request'];
                }

                if (isset($result['request']) && $result['request'] === 'CAPCHA_NOT_READY') {
                    continue;
                }

                Log::warning('CAPTCHA solving error: ' . json_encode($result));
            }

            Log::error('CAPTCHA solving timeout after ' . $timeout . ' seconds');
            return null;

        } catch (\Exception $e) {
            Log::error('CAPTCHA solving exception: ' . $e->getMessage());
            return null;
        }
    }

    private static function handleCaptcha(string $html, string $url): ?string
    {
        Log::info('Attempting to handle CAPTCHA for: ' . $url);

        // Extract CAPTCHA image from the page
        $captchaImage = self::extractCaptchaImage($html);

        if (!$captchaImage) {
            Log::error('Could not extract CAPTCHA image from page');
            return null;
        }

        Log::info('CAPTCHA image extracted successfully');

        // Solve the CAPTCHA
        $captchaSolution = self::solveCaptcha($captchaImage);

        if (!$captchaSolution) {
            Log::error('CAPTCHA solving failed');
            return null;
        }

        return $captchaSolution;
    }

    // Keep backward compatibility
    private static function parseAmazonProduct(string $html): ?array
    {
        return self::parseProduct($html, '');
    }


    private static function extractTitle(string $html): ?string
    {
        // First try meta tags (most reliable for full product names)
        $metaPatterns = [
            
        ];

        foreach ($metaPatterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $title = $matches[1];

                // Clean up title
                $title = html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $title = trim($title);

                // Remove common e-commerce prefixes/suffixes
                $siteNames = ['Amazon.com', 'eBay', 'Walmart', 'Target', 'Best Buy', 'AliExpress', 'Etsy'];
                foreach ($siteNames as $siteName) {
                    $title = preg_replace('/\s*[-|]\s*' . preg_quote($siteName, '/') . '.*$/i', '', $title);
                    $title = preg_replace('/^' . preg_quote($siteName, '/') . ':\s*/i', '', $title);
                    $title = preg_replace('/^' . preg_quote($siteName, '/') . '\s*:\s*/i', '', $title);
                }

                // Remove category suffixes (like ": Office Products")
                // More precise pattern matching
                $title = preg_replace('/\s*:\s*(Office Products|Electronics|Home & Kitchen|Clothing|Books|Sports|Toys|Health|Beauty|Automotive|Tools|Garden|Pet Supplies|Baby|Grocery|Arts & Crafts|Industrial|Software|Video Games|Musical Instruments|Cell Phones|Accessories|Camera|Photo|Furniture|Bedding|Bath|Kitchen|Dining|Appliances|Jewelry|Watches|Shoes|Handbags|Luggage|Computers|Tablets|TV|Audio|Home Audio|Speakers|Headphones|Cables|Adapters|Office|School|Business)$/i', '', $title);
                $title = preg_replace('/\s*[-|]\s*(Office Products|Electronics|Home & Kitchen|Clothing|Books|Sports|Toys|Health|Beauty|Automotive|Tools|Garden|Pet Supplies|Baby|Grocery|Arts & Crafts|Industrial|Software|Video Games|Musical Instruments|Cell Phones|Accessories|Camera|Photo|Furniture|Bedding|Bath|Kitchen|Dining|Appliances|Jewelry|Watches|Shoes|Handbags|Luggage|Computers|Tablets|TV|Audio|Home Audio|Speakers|Headphones|Cables|Adapters|Office|School|Business)$/i', '', $title);

                // Final cleanup: remove trailing separators and any remaining category-like text
                $title = preg_replace('/\s*[-|:]\s*$/', '', $title);
                $title = preg_replace('/\s*[-|:]\s*[A-Z][a-z]+(\s+[A-Z][a-z]+)*\s*$/', '', $title);

                // Remove extra whitespace
                $title = preg_replace('/\s+/', ' ', $title);
                $title = trim($title);

                // Check if this looks like a real product title (length and content)
                if (!empty($title) && strlen($title) > 10 &&
                    !preg_match('/^(Product|Item|Summary|Page|Details|Home|Make a|Select|Choose|Color|Size|Quantity|Add to|Buy Now|Continue|Please|Enter|Type|Characters)$/i', $title)) {
                    return $title;
                }
            }
        }

        // Fallback to other patterns if meta tags don't work
        $patterns = [
            
        ];

        // Check if this is an Amazon page
        $isAmazon = strpos($html, 'amazon.com') !== false || strpos($html, 'Amazon') !== false;

        $foundTitles = [];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $html, $matches)) {
                foreach ($matches[1] as $match) {
                    $title = $match;

                    // Clean up title
                    $title = html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $title = trim($title);

                    // Remove common e-commerce prefixes/suffixes
                    $siteNames = ['Amazon.com', 'eBay', 'Walmart', 'Target', 'Best Buy', 'AliExpress', 'Etsy'];
                    foreach ($siteNames as $siteName) {
                        $title = preg_replace('/\s*[-|]\s*' . preg_quote($siteName, '/') . '.*$/i', '', $title);
                        $title = preg_replace('/^' . preg_quote($siteName, '/') . ':\s*/i', '', $title);
                    }

                    // Remove common suffixes
                    $title = preg_replace('/\s*[-|]\s*.*$/i', '', $title);
                    $title = preg_replace('/\s*\|\s*.*$/i', '', $title);

                    // Remove extra whitespace
                    $title = preg_replace('/\s+/', ' ', $title);
                    $title = trim($title);

                    // Filter out generic titles and very short titles
                    if (!empty($title) && strlen($title) > 3) {
                        // Filter out obviously generic titles and UI elements
                        if (!preg_match('/^(Product|Item|Summary|Page|Details|Home|Make a|Select|Choose|Color|Size|Quantity|Add to|Buy Now|Continue|Please|Enter|Type|Characters|Image|Video|Description|Specification|Features|Benefits|Reviews|Questions|Answers|Shipping|Returns|Warranty|Support|Contact|About|Terms|Privacy|Policy|Conditions|Copyright|Rights|Reserved)$/i', $title)) {
                            // Also filter out UI options and form labels
                            if (!preg_match('/(Make a|Select a|Choose a|Please select|Select option|Choose option|Size:|Color:|Quantity:|Add to|Buy now|Continue shopping|Back to|Return to|View all|See all|Show more|Read more|Learn more|Shop now|Order now|Get it now|Limited time|Special offer|Best seller|Top rated|Most popular|New arrival|Featured|Recommended|Suggested|Related|Similar|Alternative|Compatible|Original|Genuine|Authentic|Premium|Quality|Standard|Basic|Advanced|Professional|Commercial|Industrial|Personal|Home|Office|Business|School|College|University|Student|Teacher|Kids|Children|Adults|Men|Women|Unisex|All ages|All sizes|All colors|Free shipping|Fast delivery|Same day|Next day|Express|Standard|Economy|International|Worldwide|Global|Local|Domestic|Domestic|International|Domestic|Domestic)$/i', $title)) {
                                $foundTitles[] = $title;
                            }
                        }
                    }
                }
            }
        }

        if (empty($foundTitles)) {
            // Fallback: Try to extract using the between tags method for common IDs
            $fallbackIds = ['productTitle', 'product-name', 'title', 'name', 'product_title'];
            foreach ($fallbackIds as $id) {
                $fallbackTitle = self::extractBetweenTags($html, '<span id="' . $id . '"', '</span>');
                if (!$fallbackTitle) {
                    $fallbackTitle = self::extractBetweenTags($html, '<h1 id="' . $id . '"', '</h1>');
                }
                if (!$fallbackTitle) {
                    $fallbackTitle = self::extractBetweenTags($html, '<div id="' . $id . '"', '</div>');
                }

                if ($fallbackTitle) {
                    $fallbackTitle = html_entity_decode($fallbackTitle, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $fallbackTitle = trim($fallbackTitle);
                    $fallbackTitle = preg_replace('/\s+/', ' ', $fallbackTitle);

                    if (!empty($fallbackTitle) && strlen($fallbackTitle) > 3) {
                        return $fallbackTitle;
                    }
                }
            }

            return null;
        }

        // For Amazon, prioritize longer titles (likely the full product name)
        if ($isAmazon) {
            // Sort by length (longer is usually more detailed)
            usort($foundTitles, function($a, $b) {
                return strlen($b) - strlen($a);
            });

            // Return the longest title (most likely the full product name)
            return $foundTitles[0];
        }

        // For non-Amazon, return the first reasonable title
        return $foundTitles[0];
    }

    private static function extractBetweenTags(string $html, string $startTag, string $endTag): ?string
    {
        $startPos = strpos($html, $startTag);
        if ($startPos === false) {
            return null;
        }

        $startPos = strpos($html, '>', $startPos);
        if ($startPos === false) {
            return null;
        }
        $startPos++;

        $endPos = strpos($html, $endTag, $startPos);
        if ($endPos === false) {
            return null;
        }

        return substr($html, $startPos, $endPos - $startPos);
    }

    private static function extractPrice(string $html): ?string
    {
        // Detect platform for specific handling
        $isJumia = strpos($html, 'jumia.com') !== false || strpos($html, 'Jumia') !== false;
        $isAlibaba = strpos($html, 'alibaba.com') !== false || strpos($html, 'Alibaba') !== false;
        $isAmazon = strpos($html, 'amazon.com') !== false || strpos($html, 'Amazon') !== false;

        $patterns = [
            
        ];

        $foundPrices = [];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $html, $matches)) {
                foreach ($matches[1] as $index => $match) {
                    $price = null;

                    // Handle patterns with separate whole and fraction parts
                    if (isset($matches[2]) && !empty($matches[2][$index])) {
                        $price = $matches[1][$index] . '.' . $matches[2][$index];
                    } elseif (isset($matches[1][$index]) && !empty($matches[1][$index])) {
                        $price = $matches[1][$index];
                    }

                    if ($price) {
                        // Clean up price (remove currency symbols, commas, etc.)
                        $cleanPrice = preg_replace('/[^0-9.]/', '', $price);

                        // Validate price format
                        if (preg_match('/^[0-9]+\.?[0-9]*$/', $cleanPrice) && floatval($cleanPrice) > 0) {
                            $foundPrices[] = floatval($cleanPrice);
                        }
                    }
                }
            }
        }

        if (empty($foundPrices)) {
            return null;
        }

        // Platform-specific price selection logic
        if ($isJumia) {
            // Jumia: Filter for reasonable price range and use lowest (current sale price)
            $reasonablePrices = array_filter($foundPrices, function($price) {
                return $price > 10 && $price < 10000; // Filter out very low prices
            });

            if (!empty($reasonablePrices)) {
                // Use lowest price (likely the current/sale price)
                sort($reasonablePrices);
                $lowestPrice = $reasonablePrices[0];

                Log::info('Jumia prices found: ' . implode(', ', $reasonablePrices) . ', selected lowest: ' . $lowestPrice);
                return number_format($lowestPrice, 2, '.', '');
            }
        }

        if ($isAlibaba) {
            // Alibaba often has price ranges, use the median for representative price
            sort($foundPrices);
            $medianIndex = floor(count($foundPrices) / 2);
            return number_format($foundPrices[$medianIndex], 2, '.', '');
        }

        if ($isAmazon) {
            // Amazon-specific logic: filter outliers and use most common price
            $reasonablePrices = array_filter($foundPrices, function($price) {
                return $price > 5 && $price < 10000; // Filter out extreme values
            });

            if (!empty($reasonablePrices)) {
                // Use most common price (statistical mode)
                $stringPrices = array_map('strval', $reasonablePrices);
                $priceCounts = array_count_values($stringPrices);
                arsort($priceCounts);
                $mostCommonPrice = array_key_first($priceCounts);

                return number_format($mostCommonPrice, 2, '.', '');
            }
        }

        // For other e-commerce sites, use median price (representative value)
        sort($foundPrices);
        $medianIndex = floor(count($foundPrices) / 2);
        $medianPrice = $foundPrices[$medianIndex];

        return number_format($medianPrice, 2, '.', '');
    }

    private static function extractImage(string $html): ?string
    {
        $patterns = [

        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $imageUrl = $matches[1];

                // Clean up URL
                $imageUrl = trim($imageUrl);

                // Handle srcset with multiple URLs
                if (strpos($imageUrl, ',') !== false) {
                    $urls = explode(',', $imageUrl);
                    $imageUrl = trim($urls[0]);
                    // Remove resolution from srcset
                    $imageUrl = preg_replace('/\s+[0-9]+w\s*$/', '', $imageUrl);
                }

                // Remove any query parameters for cleaner URL
                $imageUrl = preg_replace('/\?.*$/', '', $imageUrl);

                // Ensure it's a valid URL
                if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                    // Prefer high-resolution images (Amazon-specific)
                    $imageUrl = str_replace('._AC_SX300_SY300_.jpg', '._AC_SX800_SY800_.jpg', $imageUrl);
                    $imageUrl = str_replace('._AC_SX300_SY300_.png', '._AC_SX800_SY800_.png', $imageUrl);

                    // Convert relative URLs to absolute
                    if (strpos($imageUrl, 'http') !== 0) {
                        $imageUrl = 'https://' . ltrim($imageUrl, '/');
                    }

                    return $imageUrl;
                }
            }
        }

        // Fallback: Look for meta og:image (most reliable universal fallback)
        if (preg_match('/<m', $html, $matches)) {
            $imageUrl = $matches[1];
            if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                return $imageUrl;
            }
        }

        // Final fallback: Try to find any image in the main content area
        $contentPatterns = [

        ];

        foreach ($contentPatterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $imageUrl = $matches[1];
                if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                    return $imageUrl;
                }
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

    public static function scrapeAndSaveProduct(string $productUrl): ?\App\Models\Product
    {
        $productData = self::scrapeProduct($productUrl);

        if (!$productData) {
            return null;
        }
        return ProductRepository::create($productData);
    }
}
