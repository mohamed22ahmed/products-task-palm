import puppeteer from 'puppeteer';

/**
 * Captcha Solver using Puppeteer
 * This script handles automated captcha solving using headless browser
 */

async function solveCaptcha(pageUrl, captchaType = 'auto') {
    try {
        const browser = await puppeteer.launch({
            headless: 'new',
            args: ['--no-sandbox', '--disable-setuid-sandbox']
        });
        
        const page = await browser.newPage();
        
        // Set realistic user agent
        await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
        
        await page.goto(pageUrl, { waitUntil: 'networkidle2' });
        
        // Wait for captcha to load
        await page.waitForTimeout(2000);
        
        let token = null;
        
        // Try reCAPTCHA v2
        if (captchaType === 'auto' || captchaType === 'recaptcha_v2') {
            token = await solveRecaptchaV2(page);
        }
        
        // Try hCaptcha if reCAPTCHA failed
        if (!token && (captchaType === 'auto' || captchaType === 'hcaptcha')) {
            token = await solveHCaptcha(page);
        }
        
        await browser.close();
        
        if (token) {
            console.log(token);
            return token;
        }
        
        console.error('Failed to solve captcha');
        process.exit(1);
        
    } catch (error) {
        console.error(error.message);
        process.exit(1);
    }
}

async function solveRecaptchaV2(page) {
    try {
        const checkbox = await page.$('.recaptcha-checkbox-border');
        if (checkbox) {
            await checkbox.click();
            await page.waitForTimeout(3000);
            
            // Check if it was solved
            const token = await page.evaluate(() => {
                const textarea = document.querySelector('#g-recaptcha-response');
                return textarea ? textarea.value : null;
            });
            
            if (token) {
                return token;
            }
        }
        return null;
    } catch (e) {
        return null;
    }
}

async function solveHCaptcha(page) {
    try {
        const checkbox = await page.$('[aria-label="hCaptcha"]');
        if (checkbox) {
            await checkbox.click();
            await page.waitForTimeout(3000);
            
            const token = await page.evaluate(() => {
                const textarea = document.querySelector('[name="h-captcha-response"]');
                return textarea ? textarea.value : null;
            });
            
            if (token) {
                return token;
            }
        }
        return null;
    } catch (e) {
        return null;
    }
}

// Command line interface
const args = process.argv.slice(2);
const pageUrl = args[0];
const captchaType = args[1] || 'auto';

if (!pageUrl) {
    console.error('Usage: node captcha-solver.js <page_url> [captcha_type]');
    console.error('Captcha types: auto, recaptcha_v2, hcaptcha');
    process.exit(1);
}

solveCaptcha(pageUrl, captchaType);

export { solveCaptcha, solveRecaptchaV2, solveHCaptcha };
