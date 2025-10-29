const { Builder, By, until } = require('selenium-webdriver');
const firefox = require('selenium-webdriver/firefox');

describe('Firefox Selenium Test', function() {
  let driver;
  
  this.timeout(60000);

  before(async function() {
    console.log('Creating Firefox driver...');
    
    try {
      const options = new firefox.Options();
      options.addArguments('--no-sandbox');
      
      driver = await new Builder()
        .forBrowser('firefox')
        .setFirefoxOptions(options)
        .build();
      
      console.log('Firefox driver created successfully');
      
    } catch (error) {
      console.error('Firefox driver creation failed:', error.message);
      throw error;
    }
  });

  after(async function() {
    if (driver) {
      await driver.quit();
    }
  });

  it('should work with Firefox', async function() {
    console.log('Testing Firefox...');
    
    try {
      await driver.get('https://www.google.com');
      const title = await driver.getTitle();
      console.log('Google title:', title);
      
      expect(title).to.include('Google');
      
    } catch (error) {
      console.error('Firefox test failed:', error.message);
      throw error;
    }
  });
});
