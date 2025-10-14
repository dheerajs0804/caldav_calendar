const { Builder } = require('selenium-webdriver');
const chrome = require('selenium-webdriver/chrome');
const { expect } = require('chai');

describe('Working Selenium Test Suite', function() {
  let driver;
  
  this.timeout(60000);

  before(async function() {
    console.log('Setting up Chrome driver...');
    
    const options = new chrome.Options();
    options.addArguments('--no-sandbox');
    options.addArguments('--disable-dev-shm-usage');
    
    // Use the running ChromeDriver
    driver = await new Builder()
      .forBrowser('chrome')
      .setChromeOptions(options)
      .usingServer('http://localhost:9515')
      .build();
    
    console.log('✅ Chrome driver created successfully');
  });

  after(async function() {
    if (driver) {
      await driver.quit();
    }
  });

  it('should connect to the calendar application', async function() {
    console.log('Testing connection to calendar app...');
    
    await driver.get('http://[::1]:4200');
    const title = await driver.getTitle();
    console.log('✅ Page title:', title);
    
    expect(title).to.not.be.empty;
  });

  it('should find login elements', async function() {
    console.log('Looking for login elements...');
    
    // Navigate to login page
    await driver.get('http://[::1]:4200/login');
    await driver.sleep(2000);
    
    // Check for input elements
    const inputs = await driver.findElements(require('selenium-webdriver').By.css('input'));
    const buttons = await driver.findElements(require('selenium-webdriver').By.css('button'));
    
    console.log(`✅ Found ${inputs.length} input elements`);
    console.log(`✅ Found ${buttons.length} button elements`);
    
    expect(inputs.length).to.be.greaterThan(0);
    expect(buttons.length).to.be.greaterThan(0);
  });

  it('should navigate to calendar page', async function() {
    console.log('Testing calendar page...');
    
    await driver.get('http://[::1]:4200/calendar');
    await driver.sleep(2000);
    
    const title = await driver.getTitle();
    console.log('✅ Calendar page title:', title);
    
    expect(title).to.not.be.empty;
  });
});
