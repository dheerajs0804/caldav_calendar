const { expect } = require('chai');
const TestConfig = require('./config/test-config');

describe('Connection Test', function() {
  let driver;
  let testConfig;
  
  this.timeout(30000);

  before(async function() {
    testConfig = new TestConfig();
    driver = await testConfig.createDriver('chrome', false);
  });

  after(async function() {
    if (driver) {
      await driver.quit();
    }
  });

  it('should connect to the application', async function() {
    console.log(`Testing connection to: ${testConfig.baseUrl}`);
    
    try {
      await driver.get(testConfig.baseUrl);
      const title = await driver.getTitle();
      console.log(`Page title: ${title}`);
      
      // Just check if we can load the page
      expect(title).to.not.be.empty;
    } catch (error) {
      console.error('Connection test failed:', error.message);
      throw error;
    }
  });

  it('should load login page', async function() {
    try {
      await driver.get(`${testConfig.baseUrl}/login`);
      const title = await driver.getTitle();
      console.log(`Login page title: ${title}`);
      
      // Check if login page loads
      expect(title).to.not.be.empty;
    } catch (error) {
      console.error('Login page test failed:', error.message);
      throw error;
    }
  });
});
