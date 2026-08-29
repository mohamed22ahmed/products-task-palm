# Advanced Setup Guide - Residential Proxies & CAPTCHA Solving

This guide explains how to configure residential proxies and CAPTCHA solving for production use.

## 1. Residential Proxy Configuration

### Why Residential Proxies?

Residential proxies use IP addresses from real internet service providers (ISPs), making them much harder for websites like Amazon to detect compared to datacenter proxies.

### Recommended Providers

1. **Bright Data (Luminati)** - Industry leader, excellent reliability
   - Website: https://brightdata.com
   - Starting price: ~$500/month
   - Features: 72M+ IPs, worldwide coverage

2. **Oxylabs** - Premium service with good performance
   - Website: https://oxylabs.io
   - Starting price: ~$300/month
   - Features: 100M+ IPs, advanced rotation

3. **Smartproxy** - Good balance of price and performance
   - Website: https://smartproxy.com
   - Starting price: ~$75/month
   - Features: 40M+ IPs, easy setup

4. **IPRoyal** - Budget-friendly option
   - Website: https://iproyal.com
   - Starting price: ~$1.8/GB
   - Features: 2M+ IPs, pay-as-you-go

### Configuration Steps

#### Option 1: Using PHP Mock Service (Recommended for Testing)

1. Sign up with a residential proxy provider
2. Get your proxy credentials (username:password)
3. Edit `proxy-service/mock-proxy.php`:

```php
$proxies = [
    ['address' => 'direct', 'protocol' => 'http', 'last_used' => date('c'), 'fail_count' => 0, 'is_healthy' => true],
    
    // Add your residential proxies
    ['address' => 'your-username:your-password@gate.smartproxy.com:10000', 'protocol' => 'http', 'last_used' => date('c'), 'fail_count' => 0, 'is_healthy' => true],
    ['address' => 'your-username:your-password@gate.smartproxy.com:10001', 'protocol' => 'http', 'last_used' => date('c'), 'fail_count' => 0, 'is_healthy' => true],
];
```

4. Restart the proxy service:
```bash
cd proxy-service
php -S localhost:8080 mock-proxy.php
```

#### Option 2: Using Go Proxy Service (Recommended for Production)

1. Install Go if not already installed
2. Edit `proxy-service/main.go`:

```go
proxies := []Proxy{
    {Address: "direct", Protocol: "http", LastUsed: time.Now(), FailCount: 0, IsHealthy: true},
    
    // Add your residential proxies
    {Address: "your-username:your-password@gate.smartproxy.com:10000", Protocol: "http", LastUsed: time.Now(), FailCount: 0, IsHealthy: true},
    {Address: "your-username:your-password@gate.smartproxy.com:10001", Protocol: "http", LastUsed: time.Now(), FailCount: 0, IsHealthy: true},
}
```

3. Build and run:
```bash
cd proxy-service
go build -o proxy-service main.go
./proxy-service
```

### Proxy Format

The standard format for authenticated proxies is:
```
username:password@proxy-address:port
```

Examples:
- Smartproxy: `username:password@gate.smartproxy.com:10000`
- Oxylabs: `customer-username:password@residential-proxy.oxylabs.io:8000`
- Bright Data: `brd-customer-hl_abc123-zone-residential:xyz789@brd.superproxy.io:22225`

## 2. CAPTCHA Solving Configuration

### Why CAPTCHA Solving?

Even with residential proxies, sophisticated sites like Amazon may still serve CAPTCHAs. A CAPTCHA solving service automatically solves these challenges.

### Recommended Providers

1. **2Captcha** - Most popular, cost-effective
   - Website: https://2captcha.com
   - Price: ~$0.5-3 per 1000 CAPTCHAs
   - Speed: 10-20 seconds average

2. **Anti-Captcha** - Good alternative
   - Website: https://anti-captcha.com
   - Price: ~$0.5-3 per 1000 CAPTCHAs
   - Speed: 5-15 seconds average

3. **DeathByCaptcha** - Premium service
   - Website: https://deathbycaptcha.com
   - Price: ~$1.99 per 1000 CAPTCHAs
   - Speed: 5-10 seconds average

### Configuration Steps

1. Sign up with a CAPTCHA solving service (we recommend 2Captcha)
2. Get your API key from the dashboard
3. Add the API key to your Laravel `.env` file:

```env
CAPTCHA_API_KEY=your_2captcha_api_key_here
```

4. The system will automatically:
   - Detect CAPTCHAs in Amazon responses
   - Send them to the solving service
   - Wait for the solution
   - Retry the request with the solution

### Testing CAPTCHA Configuration

Test that your CAPTCHA service is working:

```bash
# Check if CAPTCHA solving is enabled
curl -X POST http://localhost:8000/api/v1/products/scrape \
  -H "Content-Type: application/json" \
  -d '{"url":"https://test-proxy.example.com"}'
```

Check the logs:
```bash
tail -f product-backend/storage/logs/laravel.log
```

You should see messages like:
- `CAPTCHA API key not configured` (if not set up)
- `Found CAPTCHA, attempting to solve using 2Captcha` (if configured)
- `CAPTCHA solved successfully` (if working)

## 3. Complete Production Setup

### Step-by-Step Production Configuration

1. **Environment Setup**
```bash
cd product-backend
cp .env.example .env
php artisan key:generate
```

2. **Configure Environment Variables**
Edit `.env`:
```env
PROXY_SERVICE_URL=http://localhost:8080
CAPTCHA_API_KEY=your_actual_api_key
```

3. **Configure Residential Proxies**
Edit either `proxy-service/mock-proxy.php` or `proxy-service/main.go` with your actual proxy credentials.

4. **Start Services**
```bash
# Terminal 1: Start proxy service
cd proxy-service
php -S localhost:8080 mock-proxy.php

# Terminal 2: Start Laravel
cd product-backend
php artisan serve
```

5. **Test Configuration**
```bash
# Test with a real Amazon product
curl -X POST http://localhost:8000/api/v1/products/scrape \
  -H "Content-Type: application/json" \
  -d '{"url":"https://www.amazon.com/dp/B0001EMM0G"}'
```

## 4. Troubleshooting

### Proxy Issues

**Problem**: "Failed to connect to proxy"
- **Solution**: Check proxy credentials and format
- **Solution**: Verify proxy service is running
- **Solution**: Test proxy directly: `curl --proxy username:password@proxy:port https://amazon.com`

**Problem**: High failure rate
- **Solution**: Add more residential proxies to rotation
- **Solution**: Check proxy provider dashboard for proxy health
- **Solution**: Increase delay between requests

### CAPTCHA Issues

**Problem**: "CAPTCHA API key not configured"
- **Solution**: Add `CAPTCHA_API_KEY` to `.env` file
- **Solution**: Ensure API key is valid and has credits

**Problem**: "CAPTCHA solving timed out"
- **Solution**: Check 2Captcha account has sufficient credits
- **Solution**: Try a different CAPTCHA service
- **Solution**: Increase timeout in `solveCaptcha()` function

**Problem**: CAPTCHA still appears after solving
- **Solution**: Amazon may have very sophisticated detection
- **Solution**: Consider using residential proxies with better IP quality
- **Solution**: Add more randomization to request patterns

### General Issues

**Problem**: Still getting blocked
- **Solution**: Use high-quality residential proxies
- **Solution**: Reduce request frequency
- **Solution**: Add more user agents and headers
- **Solution**: Consider using browser automation (Puppeteer/Selenium)

## 5. Cost Estimation

### Monthly Cost Breakdown (Approximate)

**Budget Setup:**
- Residential Proxies (IPRoyal): ~$50-100/month
- CAPTCHA Solving (2Captcha): ~$10-30/month
- **Total: ~$60-130/month**

**Premium Setup:**
- Residential Proxies (Bright Data): ~$500/month
- CAPTCHA Solving (DeathByCaptcha): ~$50/month
- **Total: ~$550/month**

**Note**: Costs vary based on usage volume and proxy quality.

## 6. Best Practices

1. **Start Small**: Begin with budget proxies, upgrade if needed
2. **Monitor Usage**: Track proxy usage and CAPTCHA solving costs
3. **Rotate Proxies**: Use multiple proxies for better distribution
4. **Respect Rate Limits**: Don't overwhelm target websites
5. **Legal Compliance**: Ensure compliance with website terms of service
6. **Regular Testing**: Test your setup regularly to ensure it's working

## 7. Security Considerations

1. **Never commit credentials**: Keep `.env` file out of version control
2. **Use environment variables**: Always use env vars for sensitive data
3. **Monitor costs**: Set up alerts for unexpected usage
4. **Regular rotation**: Rotate API keys and credentials periodically
5. **Access control**: Limit access to proxy and CAPTCHA accounts

## 8. Alternative Approaches

If residential proxies and CAPTCHA solving don't work:

1. **Official API**: Use Amazon Product Advertising API (requires approval)
2. **Browser Automation**: Use Puppeteer/Selenium with residential proxies
3. **Third-party Services**: Use ready-made scraping services like:
   - ScraperAPI
   - ZenRows
   - Apify
4. **Manual Scraping**: Consider if automated scraping is absolutely necessary

## Support

For issues specific to:
- **Residential Proxies**: Contact your proxy provider's support
- **CAPTCHA Solving**: Contact 2Captcha support
- **Laravel/PHP**: Check Laravel documentation
- **General Issues**: Review logs in `storage/logs/laravel.log`