const { Builder } = require('selenium-webdriver');
const chrome = require('selenium-webdriver/chrome');

describe('WebDriver Debug Test', function() {
  this.timeout(60000);

  it('should create Chrome driver', async function() {
    console.log('Attempting to create Chrome driver...');
    
    try {
      const chromeOptions = new chrome.Options();
      chromeOptions.addArguments('--no-sandbox');
      chromeOptions.addArguments('--disable-dev-shm-usage');
      chromeOptions.addArguments('--disable-gpu');
      
      const driver = await new Builder()
        .forBrowser('chrome')
        .setChromeOptions(chromeOptions)
        .build();
      
      console.log('Chrome driver created successfully');
      
      // Test basic navigation
      await driver.get('http://127.0.0.1:4200');
      const title = await driver.getTitle();
      console.log('Page title:', title);
      
      await driver.quit();
      console.log('Test completed successfully');
      
    } catch (error) {
      console.error('WebDriver creation failed:', error.message);
      console.error('Full error:', error);
      throw error;
    }
  });
});
