# CAPTCHA Solving Integration Guide

## 🎯 Overview

The scraper now includes **2Captcha integration** for solving CAPTCHAs, but there are important limitations and requirements to understand.

## ⚙️ Configuration

### **Environment Variables**

Add these to your `.env` file:

```bash
# 2Captcha API Configuration
CAPTCHA_API_KEY=your_2captcha_api_key_here
CAPTCHA_SOLVING_ENABLED=true
CAPTCHA_TIMEOUT=120
CAPTCHA_MAX_RETRIES=3
```

### **Getting 2Captcha API Key**

1. Visit [2Captcha.com](https://2captcha.com/)
2. Register for an account
3. Get your API key from the dashboard
4. Add funds to your account (pay-per-use pricing)

## 🔧 How It Works

### **Current Implementation**

The CAPTCHA solving integration works as follows:

1. **CAPTCHA Detection**: Detects when a page returns a CAPTCHA
2. **Image Extraction**: Extracts the CAPTCHA image from the HTML
3. **2Captcha Submission**: Sends the image to 2Captcha for solving
4. **Polling**: Continuously checks for the solution
5. **Solution Return**: Returns the solved CAPTCHA text

### **Code Flow**

```php
// When scraping a product
if (self::isCaptchaPage($html)) {
    $captchaSolution = self::handleCaptcha($html, $url);
    if ($captchaSolution) {
        // Retry the request (currently limited)
        continue;
    }
}
```

## ⚠️ Important Limitations

### **1. HTTP-Based CAPTCHA Solving Limitations**

The current implementation has **significant limitations** because it uses simple HTTP requests:

**What Works:**
- ✅ Detects CAPTCHA pages
- ✅ Extracts CAPTCHA images
- ✅ Submits to 2Captcha
- ✅ Gets solution text

**What Doesn't Work:**
- ❌ Cannot submit CAPTCHA solution back to the site
- ❌ Cannot handle JavaScript-based CAPTCHAs
- ❌ Cannot interact with CAPTCHA forms
- ❌ Cannot maintain session context
- ❌ Cannot handle reCAPTCHA v2/v3, hCaptcha

### **2. Platform-Specific Issues**

#### **Amazon**
- Uses AWS CAPTCHA (JavaScript-based)
- Requires browser automation to submit solution
- Current method: **Cannot work**

#### **Alibaba**
- Uses complex "punish-component" system
- Requires JavaScript execution
- Current method: **Cannot work**

#### **Google reCAPTCHA**
- Requires token-based solving (different API)
- Requires site keys and validation
- Current method: **Cannot work**

## 🛠️ Required for Full CAPTCHA Solving

To make CAPTCHA solving work effectively, you need:

### **1. Browser Automation**
```php
// Required to submit CAPTCHA solutions
use Facebook\WebDriver\Chrome\ChromeDriver;

$driver = ChromeDriver::start();
$driver->get($url);

// Find CAPTCHA input field
$captchaInput = $driver->findElement(WebDriverBy::id('captcha'));
$captchaInput->sendKeys($captchaSolution);

// Submit form
$driver->findElement(WebDriverBy::id('submit'))->click();
```

### **2. Session Management**
- Maintain cookies and session state
- Handle CAPTCHA submission forms
- Navigate post-CAPTCHA pages

### **3. Advanced CAPTCHA Services**
For JavaScript-based CAPTCHAs, you need different approaches:

#### **For reCAPTCHA v2/v3:**
```bash
# Use 2Captcha's API for reCAPTCHA
POST http://2captcha.com/in.php
Parameters: googlekey, pageurl, method=userrecaptcha
```

#### **For hCaptcha:**
```bash
# Use 2Captcha's API for hCaptcha
POST http://2captcha.com/in.php  
Parameters: sitekey, pageurl, method=hcaptcha
```

## 📊 Current vs. Required Architecture

### **Current (HTTP-Based)**
```
HTTP Request → CAPTCHA Page → Extract Image → 2Captcha → Solution Text
                                                            ↓
                                                      [Cannot submit back]
```

### **Required (Browser Automation)**
```
Browser → CAPTCHA Page → Extract Image → 2Captcha → Solution Text → Submit Form → Product Page
```

## 🚀 Implementation Requirements

### **To Make CAPTCHA Solving Work:**

1. **Install Browser Automation**
```bash
composer require php-webdriver/php-webdriver
# Install ChromeDriver
# Install Selenium/ChromeDriver
```

2. **Implement Browser Automation**
```php
// Replace current HTTP-based scraping with browser automation
$driver = RemoteWebDriver::create(
    'http://localhost:4444/wd/hub',
    DesiredCapabilities::chrome()
);
```

3. **Add CAPTCHA Form Handling**
```php
// Find and submit CAPTCHA form elements
$captchaField = $driver->findElement(WebDriverBy::name('captcha'));
$captchaField->sendKeys($solution);
$submitButton = $driver->findElement(WebDriverBy::id('submit'));
$submitButton->click();
```

4. **Enhanced CAPTCHA API Calls**
```php
// Handle different CAPTCHA types
if ($captchaType === 'recaptcha') {
    $solution = solveReCaptcha($siteKey, $pageUrl);
} elseif ($captchaType === 'image') {
    $solution = solveImageCaptcha($imageData);
}
```

## 🎯 Alternative Approaches

### **Option 1: Browser Automation with CAPTCHA (Recommended)**
- **Pros**: Can handle most CAPTCHA types, ~80% success rate
- **Cons**: Slower, resource-intensive, requires more infrastructure
- **Cost**: Higher (browser resources + CAPTCHA service)

### **Option 2: Residential Proxies (Partial Solution)**
- **Pros**: May avoid CAPTCHA entirely
- **Cons**: Expensive, not guaranteed to work
- **Cost**: High (proxy services)

### **Option 3: Official APIs (Best Solution)**
- **Pros**: Reliable, legal, no CAPTCHA issues
- **Cons**: Requires business registration, may have costs
- **Cost**: Variable (API fees or revenue sharing)

### **Option 4: Third-Party Scraping Services**
- **Pros**: No infrastructure needed, high success rate
- **Cons**: Most expensive, less control
- **Cost**: Very high

## 📋 Current Implementation Status

### **✅ Implemented**
- CAPTCHA page detection
- CAPTCHA image extraction
- 2Captcha API integration
- Configuration management
- Error handling and logging
- Retry logic structure

### **❌ Missing (Requires Browser Automation)**
- CAPTCHA solution submission
- Form interaction
- Session management
- JavaScript-based CAPTCHA handling
- reCAPTCHA/hCaptcha support

### **🔄 Current Behavior**
- **With CAPTCHA disabled**: Returns null when CAPTCHA detected (fast)
- **With CAPTCHA enabled**: Attempts to solve but cannot submit solution (wastes API credits)

## 💰 Cost Considerations

### **2Captcha Pricing**
- Image CAPTCHA: ~$0.50-1.00 per 1000 solves
- reCAPTCHA: ~$2.00-3.00 per 1000 solves
- Current implementation would **waste credits** without browser automation

### **Infrastructure Costs**
- Browser automation servers: $50-200/month
- Residential proxies: $100-500/month
- Total: $150-700/month for effective scraping

## 🎓 Recommendation

### **For Testing Purposes:**
1. **Keep current HTTP-based approach**
2. **Test on smaller sites without CAPTCHA**
3. **Use official APIs when available**

### **For Production Scraping:**
1. **Implement browser automation** (Selenium/Puppeteer)
2. **Add residential proxy rotation**
3. **Use enhanced CAPTCHA solving** (with form submission)
4. **Consider official APIs** (more reliable and legal)

### **Immediate Next Steps:**
1. **Get 2Captcha API key** (if you want to test)
2. **Enable CAPTCHA solving** in `.env`
3. **Test on sites with simple image CAPTCHAs**
4. **Monitor API credit usage** (don't waste credits)

## 🔧 Testing the Current Implementation

### **Without CAPTCHA API Key:**
```bash
# Current behavior - returns null on CAPTCHA
curl -X POST http://localhost:8000/api/v1/products/scrape \
  -H "Content-Type: application/json" \
  -d '{"url":"https://www.alibaba.com/product-detail/..."}'
# Result: {"error":"Failed to scrape product"}
# Log: "CAPTCHA API key not configured"
```

### **With CAPTCHA API Key (Current Limitation):**
```bash
# Current behavior - solves CAPTCHA but cannot submit
# Result: Wastes API credits, still fails to get product data
# Log: "CAPTCHA solved successfully" but still returns null
```

## 📞 Support and Resources

### **2Captcha Documentation:**
- [Official API Docs](https://2captcha.com/2captcha-api)
- [Pricing](https://2captcha.com/payments)
- [Supported CAPTCHA Types](https://2captcha.com/supported-captchas)

### **Browser Automation:**
- [PHP WebDriver](https://github.com/php-webdriver/php-webdriver)
- [Selenium](https://www.selenium.dev/)
- [Puppeteer](https://pptr.dev/)

## ⚡ Summary

The current CAPTCHA integration is **partially implemented** - it can detect and solve simple image CAPTCHAs but cannot submit the solutions back to the sites without browser automation. 

**To make it fully functional, you need to invest in browser automation infrastructure, which significantly increases complexity and operational costs.**

For most use cases, the recommended approach is to:
1. Use the current HTTP-based scraper for less-protected sites
2. Use official APIs for major platforms when available
3. Only implement full CAPTCHA solving if you have the budget and infrastructure requirements.