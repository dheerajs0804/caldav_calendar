const { Builder, By, until } = require('selenium-webdriver');
const chrome = require('selenium-webdriver/chrome');

describe('Simple Calendar Test', function() {
  let driver;
  
  this.timeout(60000);

  before(async function() {
    console.log('Setting up Chrome driver...');
    
    const options = new chrome.Options();
    options.addArguments('--no-sandbox');
    options.addArguments('--disable-dev-shm-usage');
    options.addArguments('--disable-gpu');
    options.addArguments('--disable-web-security');
    options.addArguments('--allow-running-insecure-content');
    
    try {
      driver = await new Builder()
        .forBrowser('chrome')
        .setChromeOptions(options)
        .build();
      
      console.log('Chrome driver created successfully');
    } catch (error) {
      console.error('Failed to create driver:', error.message);
      throw error;
    }
  });

  after(async function() {
    if (driver) {
      await driver.quit();
    }
  });

  it('should load the calendar application', async function() {
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

  it('should find login elements', async function() {
    console.log('Looking for login elements...');
    
    try {
      // Try to find any input field
      const inputs = await driver.findElements(By.css('input'));
      console.log(`Found ${inputs.length} input elements`);
      
      // Try to find any button
      const buttons = await driver.findElements(By.css('button'));
      console.log(`Found ${buttons.length} button elements`);
      
      // Basic check - should have some elements
      expect(inputs.length + buttons.length).to.be.greaterThan(0);
      
    } catch (error) {
      console.error('Element search failed:', error.message);
      throw error;
    }
  });
});
