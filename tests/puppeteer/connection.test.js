const { expect } = require('chai');
const TestConfig = require('./config/test-config');

describe('Calendar Connection Test (Puppeteer)', function() {
  let browser;
  let page;
  let testConfig;
  
  this.timeout(60000);

  before(async function() {
    testConfig = new TestConfig();
    browser = await testConfig.createBrowser(false); // Set to true for headless
    page = await testConfig.createPage(browser);
  });

  after(async function() {
    if (browser) {
      await browser.close();
    }
  });

  it('should connect to the application', async function() {
    console.log(`Testing connection to: ${testConfig.baseUrl}`);
    
    try {
      await page.goto(testConfig.baseUrl);
      await page.waitForLoadState('networkidle');
      
      const title = await page.title();
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
      await page.goto(`${testConfig.baseUrl}/login`);
      await page.waitForLoadState('networkidle');
      
      const title = await page.title();
      console.log(`Login page title: ${title}`);
      
      // Check if login page loads
      expect(title).to.not.be.empty;
      
      // Check for login form elements
      const emailInput = await page.$('input[type="email"], input[name="username"], input[placeholder*="email"]');
      const passwordInput = await page.$('input[type="password"]');
      const loginButton = await page.$('button[type="submit"], .login-button');
      
      expect(emailInput).to.not.be.null;
      expect(passwordInput).to.not.be.null;
      expect(loginButton).to.not.be.null;
      
    } catch (error) {
      console.error('Login page test failed:', error.message);
      throw error;
    }
  });

  it('should load calendar page', async function() {
    try {
      await page.goto(`${testConfig.baseUrl}/calendar`);
      await page.waitForLoadState('networkidle');
      
      const title = await page.title();
      console.log(`Calendar page title: ${title}`);
      
      // Check if calendar page loads
      expect(title).to.not.be.empty;
      
      // Check for calendar elements
      const calendarElement = await page.$('.calendar-container, .calendar, [data-testid="calendar"]');
      expect(calendarElement).to.not.be.null;
      
    } catch (error) {
      console.error('Calendar page test failed:', error.message);
      throw error;
    }
  });
});
