<?php

namespace Database\Seeders;

use App\Models\Pattern;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PatternsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Pattern::query()->delete();

        $patterns = $this->getPatterns();
        DB::table('patterns')->insert($patterns);
    }

    private function getPatterns(): array
    {
        return [
            // title patterns
            [
                'name' => '/<meta[^>]*property="og:title"[^>]*content="([^"]+)"/i',
                'type' => 'title',
                'domain'  => 'general'
            ],
            [
                'name' => '/<meta[^>]*name="twitter:title"[^>]*content="([^"]+)"/i',
                'type' => 'title',
                'domain'  => 'general'
            ],
            [
                'name' => '/<title>([^<]+)<\/title>/i',
                'type' => 'title',
                'domain'  => 'general'
            ],
            [
                'name' => '/<span[^>]*id="productTitle"[^>]*>([^<]+)<\/span>/i',
                'type' => 'title',
                'domain'  => 'amazon'
            ],
            [
                'name' => '/<h1[^>]*id="title"[^>]*>.*?<span[^>]*id="productTitle"[^>]*>([^<]+)<\/span>/s',
                'type' => 'title',
                'domain'  => 'amazon'
            ],
            [
                'name' => '/<h1[^>]*data-feature-name="productTitle"[^>]*>([^<]+)<\/h1>/i',
                'type' => 'title',
                'domain'  => 'amazon'
            ],
            [
                'name' => '/<h1[^>]*class="[^"]*title[^"]*"[^>]*>([^<]+)<\/h1>/i',
                'type' => 'title',
                'domain'  => 'amazon'
            ],
            [
                'name' => '/<h1[^>]*class="[^"]*a-size-large[^"]*"[^>]*>([^<]+)<\/h1>/i',
                'type' => 'title',
                'domain'  => 'amazon'
            ],
            [
                'name' => '/<h1[^>]*class="[^"]*a-spacing-small[^"]*"[^>]*>([^<]+)<\/h1>/i',
                'type' => 'title',
                'domain'  => 'amazon'
            ],
            [
                'name' => '/<h1[^>]*class="[^"]*(?:product|title|name)[^"]*"[^>]*>([^<]+)<\/h1>/i',
                'type' => 'title',
                'domain'  => 'general'
            ],
            [
                'name' => '/<h1[^>]*itemprop="name"[^>]*>([^<]+)<\/h1>/i',
                'type' => 'title',
                'domain'  => 'general'
            ],
            [
                'name' => '/<h1[^>]*data-product-title="[^"]*"[^>]*>([^<]+)<\/h1>/i',
                'type' => 'title',
                'domain'  => 'general'
            ],
            [
                'name' => '/<h1[^>]*id="[^"]*(?:product|title|name)[^"]*"[^>]*>([^<]+)<\/h1>/i',
                'type' => 'title',
                'domain'  => 'general'
            ],
            [
                'name' => '/<span[^>]*id="[^"]*(?:product|title|name)[^"]*"[^>]*>([^<]+)<\/span>/i',
                'type' => 'title',
                'domain'  => 'general'
            ],
            [
                'name' => '/<div[^>]*id="[^"]*(?:product|title|name)[^"]*"[^>]*>([^<]+)<\/div>/i',
                'type' => 'title',
                'domain'  => 'general'
            ],
            [
                'name' => '/<span[^>]*id="name"[^>]*>([^<]+)<\/span>/i',
                'type' => 'title',
                'domain'  => 'general'
            ],
            [
                'name' => '/<span[^>]*name="name"[^>]*>([^<]+)<\/span>/i',
                'type' => 'title',
                'domain'  => 'general'
            ],
            [
                'name' => '/<span[^>]*class="[^"]*product-title[^"]*"[^>]*>([^<]+)<\/span>/i',
                'type' => 'title',
                'domain'  => 'general'
            ],
            [
                'name' => '/<span[^>]*class="[^"]*product-name[^"]*"[^>]*>([^<]+)<\/span>/i',
                'type' => 'title',
                'domain'  => 'general'
            ],
            [
                'name' => '/<span[^>]*class="[^"]*title[^"]*"[^>]*>([^<]+)<\/span>/i',
                'type' => 'title',
                'domain'  => 'general'
            ],
            [
                'name' => '/<meta[^>]*name="title"[^>]*content="([^"]+)"/i',
                'type' => 'title',
                'domain'  => 'general'
            ],
            [
                'name' => '/<meta[^>]*name="product:title"[^>]*content="([^"]+)"/i',
                'type' => 'title',
                'domain'  => 'general'
            ],
            [
                'name' => '/"name"\s*:\s*"([^"]+)"/i',
                'type' => 'title',
                'domain'  => 'general'
            ],
            [
                'name' => '/<span[^>]*class="[^"]*breadcrumb[^"]*"[^>]*>.*?<span[^>]*class="[^"]*last[^"]*"[^>]*>([^<]+)<\/span>/s',
                'type' => 'title',
                'domain'  => 'breadcrumb'
            ],
            [
                'name' => '/<span[^>]*itemprop="name"[^>]*>([^<]+)<\/span>/i',
                'type' => 'title',
                'domain'  => 'general'
            ],
            [
                'name' => '/<div[^>]*itemprop="name"[^>]*>([^<]+)<\/div>/i',
                'type' => 'title',
                'domain'  => 'general'
            ],


            // price patterns
            [
                'name' => '/<span[^>]*class="[^"]*price[^"]*"[^>]*>([^<]+)<\/span>/i',
                'type' => 'price',
                'domain'  => 'general'
            ],
            [
                'name' => '/<span[^>]*class="[^"]*product-price[^"]*"[^>]*>([^<]+)<\/span>/i',
                'type' => 'price',
                'domain'  => 'general'
            ],
            [
                'name' => '/<span[^>]*class="[^"]*current-price[^"]*"[^>]*>([^<]+)<\/span>/i',
                'type' => 'price',
                'domain'  => 'general'
            ],
            [
                'name' => '/<span[^>]*class="[^"]*sale-price[^"]*"[^>]*>([^<]+)<\/span>/i',
                'type' => 'price',
                'domain'  => 'general'
            ],
            [
                'name' => '/<span[^>]*class="[^"]*regular-price[^"]*"[^>]*>([^<]+)<\/span>/i',
                'type' => 'price',
                'domain'  => 'general'
            ],
            [
                'name' => '/<span[^>]*itemprop="price"[^>]*>([^<]+)<\/span>/i',
                'type' => 'price',
                'domain'  => 'general'
            ],
            [
                'name' => '/<div[^>]*itemprop="price"[^>]*>([^<]+)<\/div>/i',
                'type' => 'price',
                'domain'  => 'general'
            ],
            [
                'name' => '/<meta[^>]*itemprop="price"[^>]*content="([^"]+)"/i',
                'type' => 'price',
                'domain'  => 'general'
            ],
            [
                'name' => '/<span[^>]*data-price="([^"]+)"/i',
                'type' => 'price',
                'domain'  => 'general'
            ],
            [
                'name' => '/<span[^>]*data-product-price="([^"]+)"/i',
                'type' => 'price',
                'domain'  => 'general'
            ],
            [
                'name' => '/<div[^>]*data-price="([^"]+)"/i',
                'type' => 'price',
                'domain'  => 'general'
            ],
            [
                'name' => '/<span[^>]*id="[^"]*price[^"]*"[^>]*>([^<]+)<\/span>/i',
                'type' => 'price',
                'domain'  => 'general'
            ],
            [
                'name' => '/<span[^>]*id="[^"]*product-price[^"]*"[^>]*>([^<]+)<\/span>/i',
                'type' => 'price',
                'domain'  => 'general'
            ],
            [
                'name' => '/<div[^>]*id="[^"]*price[^"]*"[^>]*>([^<]+)<\/div>/i',
                'type' => 'price',
                'domain'  => 'general'
            ],
            [
                'name' => '/([0-9,]+\.?[0-9]*)\s*EGP/i',
                'type' => 'price',
                'domain'  => 'jumia'
            ],
            [
                'name' => '/<span[^>]*class="[^"]*price[^"]*"[^>]*>([^<]+)\s*EGP/i',
                'type' => 'price',
                'domain'  => 'jumia'
            ],
            [
                'name' => '/<span[^>]*class="[^"]*price[^"]*"[^>]*>.*?([0-9,]+\.?[0-9]*)\s*EGP/i',
                'type' => 'price',
                'domain'  => 'jumia'
            ],
            [
                'name' => '/<div[^>]*class="[^"]*price[^"]*"[^>]*>([^<]+)\s*EGP/i',
                'type' => 'price',
                'domain'  => 'jumia'
            ],
            [
                'name' => '/<div[^>]*class="[^"]*price[^"]*"[^>]*>.*?([0-9,]+\.?[0-9]*)\s*EGP/i',
                'type' => 'price',
                'domain'  => 'jumia'
            ],
            [
                'name' => '/<span[^>]*class="[^"]*product-price[^"]*"[^>]*>([^<]+)\s*EGP/i',
                'type' => 'price',
                'domain'  => 'jumia'
            ],
            [
                'name' => '/<span[^>]*class="[^"]*product-price[^"]*"[^>]*>.*?([0-9,]+\.?[0-9]*)\s*EGP/i',
                'type' => 'price',
                'domain'  => 'jumia'
            ],
            [
                'name' => '/<span[^>]*class="[^"]*price-range[^"]*"[^>]*>([^<]+)<\/span>/i',
                'type' => 'price',
                'domain'  => 'alibaba'
            ],
            [
                'name' => '/<span[^>]*class="[^"]*offer-price[^"]*"[^>]*>([^<]+)<\/span>/i',
                'type' => 'price',
                'domain'  => 'alibaba'
            ],
            [
                'name' => '/<span[^>]*class="[^"]*unit-price[^"]*"[^>]*>([^<]+)<\/span>/i',
                'type' => 'price',
                'domain'  => 'alibaba'
            ],
            [
                'name' => '/<span[^>]*class="[^"]*min-order[^"]*"[^>]*>[^<]*\$?([0-9,]+\.?[0-9]*)/i',
                'type' => 'price',
                'domain'  => 'alibaba'
            ],
            [
                'name' => '/<div[^>]*class="[^"]*price[^"]*"[^>]*>[^<]*\$?([0-9,]+\.?[0-9]*)/i',
                'type' => 'price',
                'domain'  => 'alibaba'
            ],
            [
                'name' => '/<span[^>]*class="[^"]*a-price[^"]*"[^>]*>.*?<span[^>]*class="[^"]*a-offscreen[^"]*"[^>]*>\$([0-9,]+\.?[0-9]*)<\/span>/i',
                'type' => 'price',
                'domain'  => 'amazon'
            ],
            [
                'name' => '/<span[^>]*class="[^"]*a-price-whole[^"]*"[^>]*>([0-9,]+)<\/span>.*?<span[^>]*class="[^"]*a-price-fraction[^"]*"[^>]*>([0-9]+)/i',
                'type' => 'price',
                'domain'  => 'amazon'
            ],
            [
                'name' => '/<span[^>]*data-asin-price="([^"]+)"/i',
                'type' => 'price',
                'domain'  => 'amazon'
            ],
            [
                'name' => '/<span[^>]*aria-label="[^"]*\$([0-9,]+\.?[0-9]*)[^"]*"[^>]*class="[^"]*a-price[^"]*"/i',
                'type' => 'price',
                'domain'  => 'amazon'
            ],
            [
                'name' => '/<span[^>]*id="priceblock_ourprice"[^>]*>\$([0-9,]+\.?[0-9]*)<\/span>/i',
                'type' => 'price',
                'domain'  => 'amazon'
            ],
            [
                'name' => '/<input[^>]*type="hidden"[^>]*name="price"[^>]*value="([0-9,]+\.?[0-9]*)"/i',
                'type' => 'price',
                'domain'  => 'general'
            ],
            [
                'name' => '/<input[^>]*type="hidden"[^>]*name="product_price"[^>]*value="([0-9,]+\.?[0-9]*)"/i',
                'type' => 'price',
                'domain'  => 'general'
            ],
            [
                'name' => '/"price"\s*:\s*"([0-9,]+\.?[0-9]*)"/i',
                'type' => 'price',
                'domain'  => 'general'
            ],
            [
                'name' => '/"priceAmount"\s*:\s*"([0-9,]+\.?[0-9]*)"/i',
                'type' => 'price',
                'domain'  => 'general'
            ],
            [
                'name' => '/"price"\s*:\s*([0-9,]+\.?[0-9]*)/i',
                'type' => 'price',
                'domain'  => 'general'
            ],
            [
                'name' => '/\$\s*([0-9,]+\.?[0-9]*)/',
                'type' => 'price',
                'domain'  => 'general'
            ],
            [
                'name' => '/€\s*([0-9,]+\.?[0-9]*)/',
                'type' => 'price',
                'domain'  => 'general'
            ],
            [
                'name' => '/£\s*([0-9,]+\.?[0-9]*)/',
                'type' => 'price',
                'domain'  => 'general'
            ],
            [
                'name' => '/¥\s*([0-9,]+\.?[0-9]*)/',
                'type' => 'price',
                'domain'  => 'general'
            ],
            [
                'name' => '/₹\s*([0-9,]+\.?[0-9]*)/',
                'type' => 'price',
                'domain'  => 'general'
            ],
            [
                'name' => '/₽\s*([0-9,]+\.?[0-9]*)/',
                'type' => 'price',
                'domain'  => 'general'
            ],
            [
                'name' => '/₩\s*([0-9,]+\.?[0-9]*)/',
                'type' => 'price',
                'domain'  => 'general'
            ],
            [
                'name' => '/RM\s*([0-9,]+\.?[0-9]*)/',
                'type' => 'price',
                'domain'  => 'general'
            ],
            [
                'name' => '/S\$\s*([0-9,]+\.?[0-9]*)/',
                'type' => 'price',
                'domain'  => 'general'
            ],
            [
                'name' => '/A\$\s*([0-9,]+\.?[0-9]*)/',
                'type' => 'price',
                'domain'  => 'general'
            ],
            [
                'name' => '/C\$\s*([0-9,]+\.?[0-9]*)/',
                'type' => 'price',
                'domain'  => 'general'
            ],
            [
                'name' => '/E\£\s*([0-9,]+\.?[0-9]*)/',
                'type' => 'price',
                'domain'  => 'general'
            ],
            [
                'name' => '/([0-9,]+\.?[0-9]*)\s*USD/i',
                'type' => 'price',
                'domain'  => 'general'
            ],
            [
                'name' => '/([0-9,]+\.?[0-9]*)\s*EUR/i',
                'type' => 'price',
                'domain'  => 'general'
            ],
            [
                'name' => '/([0-9,]+\.?[0-9]*)\s*GBP/i',
                'type' => 'price',
                'domain'  => 'general'
            ],
            [
                'name' => '/([0-9,]+\.?[0-9]*)\s*JPY/i',
                'type' => 'price',
                'domain'  => 'general'
            ],
            [
                'name' => '/([0-9,]+\.?[0-9]*)\s*CNY/i',
                'type' => 'price',
                'domain'  => 'general'
            ],
            [
                'name' => '/([0-9,]+\.?[0-9]*)\s*INR/i',
                'type' => 'price',
                'domain'  => 'general'
            ],
            [
                'name' => '/([0-9,]+\.?[0-9]*)\s*RUB/i',
                'type' => 'price',
                'domain'  => 'general'
            ],
            [
                'name' => '/([0-9,]+\.?[0-9]*)\s*KRW/i',
                'type' => 'price',
                'domain'  => 'general'
            ],
            [
                'name' => '/([0-9,]+\.?[0-9]*)\s*MYR/i',
                'type' => 'price',
                'domain'  => 'general'
            ],
            [
                'name' => '/([0-9,]+\.?[0-9]*)\s*SGD/i',
                'type' => 'price',
                'domain'  => 'general'
            ],
            [
                'name' => '/([0-9,]+\.?[0-9]*)\s*AUD/i',
                'type' => 'price',
                'domain'  => 'general'
            ],
            [
                'name' => '/([0-9,]+\.?[0-9]*)\s*CAD/i',
                'type' => 'price',
                'domain'  => 'general'
            ],
            [
                'name' => '/([0-9,]+\.?[0-9]*)\s*EGP/i',
                'type' => 'price',
                'domain'  => 'general'
            ],
            [
                'name' => '/<meta[^>]*property="product:price:amount"[^>]*content="([^"]+)"/i',
                'type' => 'price',
                'domain'  => 'general'
            ],
            [
                'name' => '/<meta[^>]*property="og:price:amount"[^>]*content="([^"]+)"/i',
                'type' => 'price',
                'domain'  => 'general'
            ],


            // image patterns
            [
                'name' => '/<img[^>]*class="[^"]*product-image[^"]*"[^>]*src="([^"]+)"/i',
                'type' => 'image',
                'domain' => 'general'
            ],
            [
                'name' => '/<img[^>]*class="[^"]*main-image[^"]*"[^>]*src="([^"]+)"/i',
                'type' => 'image',
                'domain' => 'general'
            ],
            [
                'name' => '/<img[^>]*class="[^"]*primary-image[^"]*"[^>]*src="([^"]+)"/i',
                'type' => 'image',
                'domain' => 'general'
            ],
            [
                'name' => '/<img[^>]*class="[^"]*featured-image[^"]*"[^>]*src="([^"]+)"/i',
                'type' => 'image',
                'domain' => 'general'
            ],
            [
                'name' => '/<img[^>]*id="[^"]*product-image[^"]*"[^>]*src="([^"]+)"/i',
                'type' => 'image',
                'domain' => 'general'
            ],
            [
                'name' => '/<img[^>]*id="[^"]*main-image[^"]*"[^>]*src="([^"]+)"/i',
                'type' => 'image',
                'domain' => 'general'
            ],
            [
                'name' => '/<img[^>]*id="[^"]*primary-image[^"]*"[^>]*src="([^"]+)"/i',
                'type' => 'image',
                'domain' => 'general'
            ],
            [
                'name' => '/<img[^>]*id="[^"]*hero-image[^"]*"[^>]*src="([^"]+)"/i',

                'type' => 'image',
                'domain' => 'general'
            ],
            [
                'name' => '/<img[^>]*data-src="([^"]+)"/i',
                'type' => 'image',
                'domain' => 'general'
            ],
            [
                'name' => '/<img[^>]*data-original="([^"]+)"/i',
                'type' => 'image',
                'domain' => 'general'
            ],
            [
                'name' => '/<img[^>]*data-lazy-src="([^"]+)"/i',
                'type' => 'image',
                'domain' => 'general'
            ],
            [
                'name' => '/<img[^>]*itemprop="image"[^>]*src="([^"]+)"/i',
                'type' => 'image',
                'domain' => 'general'
            ],
            [
                'name' => '/<meta[^>]*itemprop="image"[^>]*content="([^"]+)"/i',
                'type' => 'image',
                'domain' => 'general'
            ],
            [
                'name' => '/<meta[^>]*property="og:image"[^>]*content="([^"]+)"/i',
                'type' => 'image',
                'domain' => 'general'
            ],
            [
                'name' => '/<meta[^>]*property="og:image:url"[^>]*content="([^"]+)"/i',
                'type' => 'image',
                'domain' => 'general'
            ],
            [
                'name' => '/<meta[^>]*name="twitter:image"[^>]*content="([^"]+)"/i',
                'type' => 'image',
                'domain' => 'general'
            ],
            [
                'name' => '/<link[^>]*rel="image_src"[^>]*href="([^"]+)"/i',
                'type' => 'image',
                'domain' => 'general'
            ],
            [
                'name' => '/"image"\s*:\s*"([^"]+)"/i',
                'type' => 'image',
                'domain' => 'general'
            ],
            [
                'name' => '/<img[^>]*class="[^"]*a-dynamic-image[^"]*"[^>]*data-src="([^"]+)"/i',
                'type' => 'image',
                'domain' => 'amazon'
            ],
            [
                'name' => '/<img[^>]*id="landingImage"[^>]*data-src="([^"]+)"/i',
                'type' => 'image',
                'domain' => 'amazon'
            ],
            [
                'name' => '/<img[^>]*id="imgBlkFront"[^>]*data-src="([^"]+)"/i',
                'type' => 'image',
                'domain' => 'amazon'
            ],
            [
                'name' => '/<img[^>]*id="main-image"[^>]*src="([^"]+)"/i',
                'type' => 'image',
                'domain' => 'amazon'
            ],
            [
                'name' => '/<img[^>]*class="[^"]*itemNo0[^"]*"[^>]*src="([^"]+)"/i',
                'type' => 'image',
                'domain' => 'amazon'
            ],
            [
                'name' => '/<img[^>]*data-old-hires="([^"]+)"/i',
                'type' => 'image',
                'domain' => 'amazon'
            ],
            [
                'name' => '/<img[^>]*srcset="([^"]+)"[^>]*class="[^"]*a-dynamic-image[^"]*"/i',
                'type' => 'image',
                'domain' => 'amazon'
            ],
            [
                'name' => '/"large":"([^"]+\.jpg[^"]*)"/i',
                'type' => 'image',
                'domain' => 'amazon'
            ],
            [
                'name' => '/<img[^>]*src="([^"]*\.jpg[^"]*)"/i',
                'type' => 'image',
                'domain' => 'general'
            ],
            [
                'name' => '/<img[^>]*src="([^"]*\.jpeg[^"]*)"/i',
                'type' => 'image',
                'domain' => 'general'
            ],
            [
                'name' => '/<img[^>]*src="([^"]*\.png[^"]*)"/i',
                'type' => 'image',
                'domain' => 'general'
            ],
            [
                'name' => '/<img[^>]*src="([^"]*\.webp[^"]*)"/i',
                'type' => 'image',
                'domain' => 'general'
            ],
            [
                'name' => '/<img[^>]*src="([^"]*\.gif[^"]*)"/i',
                'type' => 'image',
                'domain' => 'general'
            ],
            [
                'name' => '/<img[^>]*alt="[^"]*product[^"]*image[^"]*"[^>]*src="([^"]+)"/i',
                'type' => 'image',
                'domain' => 'general'
            ],
            [
                'name' => '/<img[^>]*alt="[^"]*main[^"]*image[^"]*"[^>]*src="([^"]+)"/i',
                'type' => 'image',
                'domain' => 'general'
            ],
            [
                'name' => '/<meta[^>]*property="og:image"[^>]*content="([^"]+)"/i',
                'type' => 'image',
                'domain' => 'general'
            ],
            [
                'name' => '/<div[^>]*class="[^"]*product[^"]*"[^>]*>.*?<img[^>]*src="([^"]+)"/s',
                'type' => 'image',
                'domain' => 'general'
            ],
            [
                'name' => '/<div[^>]*class="[^"]*main[^"]*"[^>]*>.*?<img[^>]*src="([^"]+)"/s',
                'type' => 'image',
                'domain' => 'general'
            ],
            [
                'name' => '/<div[^>]*id="[^"]*product[^"]*"[^>]*>.*?<img[^>]*src="([^"]+)"/s',
                'type' => 'image',
                'domain' => 'general'
            ],
        ];
    }
}
