<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ProxyManagementService
{
    private array $proxies;

    private array $userAgents = [
        // Chrome on Windows
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 11.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        // Chrome on macOS
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        // Firefox on Windows
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:120.0) Gecko/20100101 Firefox/120.0',
        'Mozilla/5.0 (Windows NT 11.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
        // Firefox on macOS
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:121.0) Gecko/20100101 Firefox/121.0',
        // Safari on macOS
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Safari/605.1.15',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Safari/605.1.15',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Safari/605.1.15',
        // Chrome on Linux
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36',
        // Edge on Windows
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0',
        'Mozilla/5.0 (Windows NT 11.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0',
        // Mobile devices
        'Mozilla/5.0 (iPhone; CPU iPhone OS 17_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Mobile/15E148 Safari/604.1',
        'Mozilla/5.0 (iPad; CPU OS 17_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Mobile/15E148 Safari/604.1',
        'Mozilla/5.0 (Linux; Android 13; SM-S908B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36',
    ];

    public function __construct()
    {
        $this->proxies = $this->initializeProxies();
    }

    private function initializeProxies(): array
    {
        return [
            [
                'address' => 'direct',
                'protocol' => 'http',
                'last_used' => now(),
                'fail_count' => 0,
                'is_healthy' => true,
            ],
        ];
    }

    public function getNextProxy(): ?array
    {
        $healthyProxies = array_filter($this->proxies, function ($proxy) {
            return $proxy['is_healthy'];
        });

        if (empty($healthyProxies)) {
            foreach ($this->proxies as &$proxy) {
                $proxy['is_healthy'] = true;
                $proxy['fail_count'] = 0;
            }
            $healthyProxies = $this->proxies;
        }

        $healthyProxies = array_values($healthyProxies);

        if (count($healthyProxies) > 0) {
            usort($healthyProxies, function ($a, $b) {
                return $a['last_used'] <=> $b['last_used'];
            });

            $selectionPool = $healthyProxies;
            if (count($healthyProxies) > 3) {
                $selectionPool = array_slice($healthyProxies, 0, 3);
            }

            $index = array_rand($selectionPool);
            $selectedProxy = $selectionPool[$index];

            foreach ($this->proxies as &$proxy) {
                if ($proxy['address'] === $selectedProxy['address']) {
                    $proxy['last_used'] = now();
                    break;
                }
            }

            return $selectedProxy;
        }

        return null;
    }

    public function getRandomUserAgent(): string
    {
        return $this->userAgents[array_rand($this->userAgents)];
    }

    public function markProxyFailed(string $proxyAddress): void
    {
        foreach ($this->proxies as &$proxy) {
            if ($proxy['address'] === $proxyAddress) {
                $proxy['fail_count']++;
                if ($proxy['fail_count'] >= 3) {
                    $proxy['is_healthy'] = false;
                }
                break;
            }
        }
    }

    public function markProxySuccess(string $proxyAddress): void
    {
        foreach ($this->proxies as &$proxy) {
            if ($proxy['address'] === $proxyAddress) {
                $proxy['fail_count'] = 0;
                $proxy['is_healthy'] = true;
                break;
            }
        }
    }

    public function getProxyAndUserAgent(string $targetUrl): array
    {
        $proxy = $this->getNextProxy();

        if ($proxy === null) {
            return [
                'proxy' => null,
                'user_agent' => null,
                'error' => 'No healthy proxies available',
            ];
        }

        $userAgent = $this->getRandomUserAgent();

        return [
            'proxy' => $proxy['address'],
            'user_agent' => $userAgent,
        ];
    }

    public function reportProxyResult(string $proxyAddress, bool $success): void
    {
        if ($success) {
            $this->markProxySuccess($proxyAddress);
        } else {
            $this->markProxyFailed($proxyAddress);
        }
    }
}
