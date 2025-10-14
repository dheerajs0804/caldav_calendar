const { Builder, By, until } = require('selenium-webdriver');
const edge = require('selenium-webdriver/edge');
const { Options: EdgeOptions } = require('selenium-webdriver/edge');

describe('Edge WebDriver Test', function() {
  let driver;
  
  this.timeout(120000);

  before(async function() {
    console.log('Setting up Edge driver...');
    
    try {
      const edgeOptions = new EdgeOptions();
      edgeOptions.addArguments('--no-sandbox');
      edgeOptions.addArguments('--disable-dev-shm-usage');
      edgeOptions.addArguments('--disable-gpu');
      edgeOptions.addArguments('--window-size=1920,1080');
      
      driver = await new Builder()
        .forBrowser('MicrosoftEdge')
        .setEdgeOptions(edgeOptions)
        .build();
      
      console.log('Edge driver created successfully');
      
    } catch (error) {
      console.error('Failed to create Edge driver:', error.message);
      throw error;
    }
  });

  after(async function() {
    if (driver) {
      await driver.quit();
    }
  });

  it('should navigate to the application', async function() {
    console.log('Navigating to application with Edge...');
    
    try {
      await driver.get('http://127.0.0.1:4200');
      await driver.sleep(5000);
      
      const title = await driver.getTitle();
      console.log('Page title:', title);
      
      expect(title).to.not.be.empty;
      
    } catch (error) {
      console.error('Navigation failed:', error.message);
      throw error;
    }
  });
});
