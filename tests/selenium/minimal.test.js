const { Builder, By, until } = require('selenium-webdriver');
const chrome = require('selenium-webdriver/chrome');

describe('Minimal Selenium Test', function() {
  let driver;
  
  this.timeout(60000);

  before(async function() {
    console.log('Creating minimal Chrome driver...');
    
    try {
      // Minimal Chrome options
      const options = new chrome.Options();
      options.addArguments('--no-sandbox');
      options.addArguments('--disable-dev-shm-usage');
      
      // Create driver with minimal configuration
      driver = await new Builder()
        .forBrowser('chrome')
        .setChromeOptions(options)
        .build();
      
      console.log('Driver created successfully');
      
    } catch (error) {
      console.error('Driver creation failed:', error.message);
      console.log('\nTroubleshooting steps:');
      console.log('1. Make sure Chrome is installed');
      console.log('2. Download ChromeDriver from https://chromedriver.chromium.org/');
      console.log('3. Add ChromeDriver to your PATH or C:\\Windows\\System32\\');
      console.log('4. Check Chrome version matches ChromeDriver version');
      throw error;
    }
  });

  after(async function() {
    if (driver) {
      await driver.quit();
    }
  });

  it('should work', async function() {
    console.log('Testing basic functionality...');
    
    try {
      // Simple test - just navigate to Google
      await driver.get('https://www.google.com');
      const title = await driver.getTitle();
      console.log('Google title:', title);
      
      expect(title).to.include('Google');
      
    } catch (error) {
      console.error('Test failed:', error.message);
      throw error;
    }
  });
});
