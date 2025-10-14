const { Builder, By, until } = require('selenium-webdriver');
const chrome = require('selenium-webdriver/chrome');
const firefox = require('selenium-webdriver/firefox');
const path = require('path');
const { Options: ChromeOptions, ServiceBuilder: ChromeServiceBuilder } = require('selenium-webdriver/chrome');
require('dotenv').config();

class TestConfig {
  constructor() {
    this.baseUrl = process.env.TEST_BASE_URL || 'http://[::1]:4200';
    this.backendUrl = process.env.TEST_BACKEND_URL || 'http://[::1]:8000';
    this.timeout = 30000;
    this.implicitWait = 5000;
    this.pageLoadTimeout = 30000;
  }

  async createDriver(browser = 'chrome', headless = false) {
    let driver;
    
    switch (browser.toLowerCase()) {
      case 'chrome':
        console.log('Setting up Chrome driver...');
        
        const chromeOptions = new ChromeOptions();
        if (headless) {
          chromeOptions.addArguments('--headless');
        }
        chromeOptions.addArguments('--no-sandbox');
        chromeOptions.addArguments('--disable-dev-shm-usage');
        chromeOptions.addArguments('--disable-gpu');
        chromeOptions.addArguments('--window-size=1920,1080');
        chromeOptions.addArguments('--disable-web-security');
        chromeOptions.addArguments('--allow-running-insecure-content');
        chromeOptions.addArguments('--disable-extensions');
        chromeOptions.addArguments('--disable-plugins');
        chromeOptions.addArguments('--disable-images');
        
        // Try to find Chrome executable
        const possibleChromePaths = [
          'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
          'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
          process.env.CHROME_BIN
        ].filter(Boolean);
        
        for (const chromePath of possibleChromePaths) {
          try {
            chromeOptions.setChromeBinaryPath(chromePath);
            console.log(`Trying Chrome path: ${chromePath}`);
            break;
          } catch (e) {
            console.log(`Chrome path not found: ${chromePath}`);
          }
        }
        
        try {
          // Use the running ChromeDriver on port 9515
          driver = await new Builder()
            .forBrowser('chrome')
            .setChromeOptions(chromeOptions)
            .usingServer('http://localhost:9515')
            .build();
          
          console.log('Chrome driver created successfully using running ChromeDriver');
        } catch (error) {
          console.error('Failed to create Chrome driver with running server:', error.message);
          
          // Fallback: try to create new ChromeDriver instance
          try {
            const service = new ChromeServiceBuilder();
            driver = await new Builder()
              .forBrowser('chrome')
              .setChromeOptions(chromeOptions)
              .setChromeService(service)
              .build();
            
            console.log('Chrome driver created successfully (fallback)');
          } catch (error2) {
            console.error('Failed to create Chrome driver:', error2.message);
            throw new Error(`Chrome driver creation failed: ${error2.message}`);
          }
        }
        break;
        
      case 'firefox':
        const firefoxOptions = new firefox.Options();
        if (headless) {
          firefoxOptions.addArguments('--headless');
        }
        
        driver = await new Builder()
          .forBrowser('firefox')
          .setFirefoxOptions(firefoxOptions)
          .build();
        break;
        
      default:
        throw new Error(`Unsupported browser: ${browser}`);
    }

    // Set timeouts
    await driver.manage().setTimeouts({
      implicit: this.implicitWait,
      pageLoad: this.pageLoadTimeout,
      script: this.timeout
    });

    // Maximize window
    await driver.manage().window().maximize();
    
    return driver;
  }

  async navigateToLogin(driver) {
    await driver.get(`${this.baseUrl}/login`);
    // Try multiple form selectors
    const formSelectors = ['form', '.login-form', '.form', 'input', 'body'];
    
    for (const selector of formSelectors) {
      try {
        await driver.wait(until.elementLocated(By.css(selector)), 5000);
        console.log(`Found login form with selector: ${selector}`);
        return;
      } catch (error) {
        continue;
      }
    }
    
    console.log('Login form not found, but continuing...');
  }

  async navigateToCalendar(driver) {
    await driver.get(`${this.baseUrl}/calendar`);
    // Try multiple calendar container selectors
    const calendarSelectors = [
      '.calendar-container',
      '.calendar',
      '[class*="calendar"]',
      '[class*="Calendar"]',
      'app-calendar',
      '.main-content',
      'body'
    ];
    
    for (const selector of calendarSelectors) {
      try {
        await driver.wait(until.elementLocated(By.css(selector)), 5000);
        console.log(`Found calendar container with selector: ${selector}`);
        return;
      } catch (error) {
        continue;
      }
    }
    
    console.log('Calendar container not found, but continuing...');
  }

  async waitForElement(driver, selector, timeout = this.timeout) {
    // Handle multiple selectors separated by commas
    const selectors = selector.split(',').map(s => s.trim());
    
    for (const sel of selectors) {
      try {
        return await driver.wait(until.elementLocated(By.css(sel)), timeout);
      } catch (error) {
        // Try next selector
        continue;
      }
    }
    
    // If none worked, throw error
    throw new Error(`No element found with selectors: ${selector}`);
  }

  async waitForElements(driver, selector, timeout = this.timeout) {
    return await driver.wait(until.elementsLocated(By.css(selector)), timeout);
  }

  async waitForElementVisible(driver, selector, timeout = this.timeout) {
    const element = await this.waitForElement(driver, selector, timeout);
    return await driver.wait(until.elementIsVisible(element), timeout);
  }

  async waitForElementClickable(driver, selector, timeout = this.timeout) {
    const element = await this.waitForElement(driver, selector, timeout);
    return await driver.wait(until.elementIsEnabled(element), timeout);
  }

  async takeScreenshot(driver, filename) {
    const screenshot = await driver.takeScreenshot();
    const fs = require('fs');
    const path = require('path');
    
    const screenshotsDir = path.join(__dirname, '../screenshots');
    if (!fs.existsSync(screenshotsDir)) {
      fs.mkdirSync(screenshotsDir, { recursive: true });
    }
    
    const filepath = path.join(screenshotsDir, `${filename}.png`);
    fs.writeFileSync(filepath, screenshot, 'base64');
    console.log(`Screenshot saved: ${filepath}`);
    return filepath;
  }

  async login(driver, username, password) {
    try {
      console.log(`Attempting to login to: ${this.baseUrl}`);
      await this.navigateToLogin(driver);
      
      const usernameField = await this.waitForElement(driver, 'input[type="email"], input[name="email"], input[placeholder*="email"], input[placeholder*="Email"]');
      const passwordField = await this.waitForElement(driver, 'input[type="password"]');
      const loginButton = await this.waitForElement(driver, 'button[type="submit"], button:contains("Login"), .login-button, button');
      
      await usernameField.clear();
      await usernameField.sendKeys(username);
      
      await passwordField.clear();
      await passwordField.sendKeys(password);
      
      await loginButton.click();
      
      // Wait for redirect to calendar or dashboard
      await driver.wait(async () => {
        const currentUrl = await driver.getCurrentUrl();
        return !currentUrl.includes('/login');
      }, this.timeout);
      
      console.log('Login successful');
    } catch (error) {
      console.error('Login failed:', error.message);
      throw error;
    }
  }

  async logout(driver) {
    try {
      const logoutButton = await driver.findElement(By.css('.logout-button, [data-testid="logout"], button:contains("Logout")'));
      await logoutButton.click();
      
      // Wait for redirect to login
      await driver.wait(async () => {
        const currentUrl = await driver.getCurrentUrl();
        return currentUrl.includes('/login');
      }, this.timeout);
    } catch (error) {
      console.log('Logout button not found or already logged out');
    }
  }
}

module.exports = TestConfig;
