const { Builder, By, until } = require('selenium-webdriver');
const chrome = require('selenium-webdriver/chrome');
const { Options: ChromeOptions, ServiceBuilder: ChromeServiceBuilder } = require('selenium-webdriver/chrome');

describe('Selenium ChromeDriver Test', function() {
  let driver;
  
  this.timeout(60000);

  before(async function() {
    console.log('Setting up Chrome driver with webdriver-manager...');
    
    try {
      const chromeOptions = new ChromeOptions();
      chromeOptions.addArguments('--no-sandbox');
      chromeOptions.addArguments('--disable-dev-shm-usage');
      chromeOptions.addArguments('--disable-gpu');
      chromeOptions.addArguments('--window-size=1920,1080');
      chromeOptions.addArguments('--disable-web-security');
      chromeOptions.addArguments('--allow-running-insecure-content');
      
      // Try to find Chrome executable
      const possibleChromePaths = [
        'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe'
      ];
      
      for (const chromePath of possibleChromePaths) {
        try {
          chromeOptions.setChromeBinaryPath(chromePath);
          console.log(`Using Chrome path: ${chromePath}`);
          break;
        } catch (e) {
          console.log(`Chrome path not found: ${chromePath}`);
        }
      }
      
      // Create service
      const service = new ChromeServiceBuilder();
      
      driver = await new Builder()
        .forBrowser('chrome')
        .setChromeOptions(chromeOptions)
        .setChromeService(service)
        .build();
      
      console.log('Chrome driver created successfully');
      
    } catch (error) {
      console.error('Failed to create Chrome driver:', error.message);
      throw error;
    }
  });

  after(async function() {
    if (driver) {
      await driver.quit();
    }
  });

  it('should navigate to the application', async function() {
    console.log('Navigating to application...');
    
    try {
      await driver.get('http://127.0.0.1:4200');
      await driver.sleep(3000); // Wait for page to load
      
      const title = await driver.getTitle();
      console.log('Page title:', title);
      
      // Check if page loaded
      expect(title).to.not.be.empty;
      
    } catch (error) {
      console.error('Navigation failed:', error.message);
      throw error;
    }
  });

  it('should find page elements', async function() {
    console.log('Looking for page elements...');
    
    try {
      // Wait for page to load
      await driver.sleep(2000);
      
      // Try to find any input field
      const inputs = await driver.findElements(By.css('input'));
      console.log(`Found ${inputs.length} input elements`);
      
      // Try to find any button
      const buttons = await driver.findElements(By.css('button'));
      console.log(`Found ${buttons.length} button elements`);
      
      // Try to find any div
      const divs = await driver.findElements(By.css('div'));
      console.log(`Found ${divs.length} div elements`);
      
      // Basic check - should have some elements
      expect(inputs.length + buttons.length + divs.length).to.be.greaterThan(0);
      
    } catch (error) {
      console.error('Element search failed:', error.message);
      throw error;
    }
  });
});
