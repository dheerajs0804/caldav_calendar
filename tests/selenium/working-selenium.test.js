const { Builder, By, until } = require('selenium-webdriver');
const chrome = require('selenium-webdriver/chrome');
const { Options: ChromeOptions } = require('selenium-webdriver/chrome');

describe('Working Selenium Test', function() {
  let driver;
  
  this.timeout(120000); // 2 minutes timeout

  before(async function() {
    console.log('Setting up Chrome driver...');
    
    try {
      const chromeOptions = new ChromeOptions();
      chromeOptions.addArguments('--no-sandbox');
      chromeOptions.addArguments('--disable-dev-shm-usage');
      chromeOptions.addArguments('--disable-gpu');
      chromeOptions.addArguments('--window-size=1920,1080');
      chromeOptions.addArguments('--disable-web-security');
      chromeOptions.addArguments('--allow-running-insecure-content');
      chromeOptions.addArguments('--disable-extensions');
      chromeOptions.addArguments('--disable-plugins');
      chromeOptions.addArguments('--remote-debugging-port=9222');
      
      // Set Chrome binary path explicitly
      chromeOptions.setChromeBinaryPath('C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe');
      
      // Create driver without service (let Selenium find ChromeDriver)
      driver = await new Builder()
        .forBrowser('chrome')
        .setChromeOptions(chromeOptions)
        .build();
      
      console.log('Chrome driver created successfully');
      
      // Set timeouts
      await driver.manage().setTimeouts({
        implicit: 10000,
        pageLoad: 30000,
        script: 30000
      });
      
    } catch (error) {
      console.error('Failed to create Chrome driver:', error.message);
      console.error('Full error:', error);
      throw error;
    }
  });

  after(async function() {
    if (driver) {
      try {
        await driver.quit();
        console.log('Driver closed successfully');
      } catch (error) {
        console.error('Error closing driver:', error.message);
      }
    }
  });

  it('should navigate to the application', async function() {
    console.log('Navigating to application...');
    
    try {
      await driver.get('http://127.0.0.1:4200');
      console.log('Page loaded, waiting for elements...');
      
      // Wait for page to load
      await driver.sleep(5000);
      
      const title = await driver.getTitle();
      console.log('Page title:', title);
      
      // Check if page loaded
      expect(title).to.not.be.empty;
      
    } catch (error) {
      console.error('Navigation failed:', error.message);
      
      // Take screenshot for debugging
      try {
        const screenshot = await driver.takeScreenshot();
        const fs = require('fs');
        fs.writeFileSync('error-screenshot.png', screenshot, 'base64');
        console.log('Screenshot saved as error-screenshot.png');
      } catch (screenshotError) {
        console.error('Failed to take screenshot:', screenshotError.message);
      }
      
      throw error;
    }
  });

  it('should find page elements', async function() {
    console.log('Looking for page elements...');
    
    try {
      // Wait for page to load
      await driver.sleep(3000);
      
      // Try to find any input field
      const inputs = await driver.findElements(By.css('input'));
      console.log(`Found ${inputs.length} input elements`);
      
      // Try to find any button
      const buttons = await driver.findElements(By.css('button'));
      console.log(`Found ${buttons.length} button elements`);
      
      // Try to find any div
      const divs = await driver.findElements(By.css('div'));
      console.log(`Found ${divs.length} div elements`);
      
      // Try to find body
      const body = await driver.findElement(By.css('body'));
      const bodyText = await body.getText();
      console.log('Body text length:', bodyText.length);
      
      // Basic check - should have some elements
      expect(inputs.length + buttons.length + divs.length).to.be.greaterThan(0);
      
    } catch (error) {
      console.error('Element search failed:', error.message);
      throw error;
    }
  });
});
