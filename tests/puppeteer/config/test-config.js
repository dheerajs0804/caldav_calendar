const puppeteer = require('puppeteer');
require('dotenv').config();

class TestConfig {
  constructor() {
    this.baseUrl = process.env.TEST_BASE_URL || 'http://127.0.0.1:4200';
    this.backendUrl = process.env.TEST_BACKEND_URL || 'http://127.0.0.1:8000';
    this.timeout = 30000;
  }

  async createBrowser(headless = false) {
    console.log('Launching browser...');
    
    const browser = await puppeteer.launch({
      headless: headless,
      args: [
        '--no-sandbox',
        '--disable-setuid-sandbox',
        '--disable-dev-shm-usage',
        '--disable-gpu',
        '--window-size=1920,1080'
      ],
      defaultViewport: {
        width: 1920,
        height: 1080
      }
    });

    console.log('Browser launched successfully');
    return browser;
  }

  async createPage(browser) {
    const page = await browser.newPage();
    
    // Set timeouts
    page.setDefaultTimeout(this.timeout);
    page.setDefaultNavigationTimeout(this.timeout);
    
    return page;
  }

  async navigateToLogin(page) {
    console.log(`Navigating to login: ${this.baseUrl}/login`);
    await page.goto(`${this.baseUrl}/login`);
    await page.waitForSelector('form, input[type="email"], input[type="password"]', { timeout: 10000 });
  }

  async navigateToCalendar(page) {
    console.log(`Navigating to calendar: ${this.baseUrl}/calendar`);
    await page.goto(`${this.baseUrl}/calendar`);
    await page.waitForSelector('.calendar-container, .calendar, [data-testid="calendar"]', { timeout: 10000 });
  }

  async login(page, username, password) {
    try {
      console.log(`Attempting to login to: ${this.baseUrl}`);
      await this.navigateToLogin(page);
      
      // Wait for and fill username field
      await page.waitForSelector('input[type="email"], input[name="username"], input[placeholder*="email"]');
      await page.type('input[type="email"], input[name="username"], input[placeholder*="email"]', username);
      
      // Wait for and fill password field
      await page.waitForSelector('input[type="password"]');
      await page.type('input[type="password"]', password);
      
      // Click login button
      await page.click('button[type="submit"], button:contains("Login"), .login-button');
      
      // Wait for redirect
      await page.waitForFunction(() => !window.location.href.includes('/login'), { timeout: 10000 });
      
      console.log('Login successful');
    } catch (error) {
      console.error('Login failed:', error.message);
      throw error;
    }
  }

  async takeScreenshot(page, filename) {
    const screenshot = await page.screenshot({ fullPage: true });
    const fs = require('fs');
    const path = require('path');
    
    const screenshotsDir = path.join(__dirname, '../screenshots');
    if (!fs.existsSync(screenshotsDir)) {
      fs.mkdirSync(screenshotsDir, { recursive: true });
    }
    
    const filepath = path.join(screenshotsDir, `${filename}.png`);
    fs.writeFileSync(filepath, screenshot);
    console.log(`Screenshot saved: ${filepath}`);
    return filepath;
  }
}

module.exports = TestConfig;
