const { expect } = require('chai');
const TestConfig = require('./config/test-config');
const TestHelpers = require('./utils/test-helpers');

describe('Calendar Login Tests', function() {
  let driver;
  let testConfig;
  let helpers;
  
  // Increase timeout for all tests
  this.timeout(60000);

  before(async function() {
    testConfig = new TestConfig();
    driver = await testConfig.createDriver('chrome', false); // Set to true for headless
    helpers = new TestHelpers(driver);
  });

  after(async function() {
    if (driver) {
      await driver.quit();
    }
  });

  beforeEach(async function() {
    // Navigate to login page before each test
    await testConfig.navigateToLogin(driver);
  });

  describe('Login Page Elements', function() {
    it('should display login form elements', async function() {
      // Check for username/email field
      await helpers.assertElementPresent('input[type="email"], input[name="username"], input[placeholder*="email"]');
      
      // Check for password field
      await helpers.assertElementPresent('input[type="password"]');
      
      // Check for login button
      await helpers.assertElementPresent('button[type="submit"], button:contains("Login"), .login-button');
    });

    it('should display proper page title', async function() {
      const title = await driver.getTitle();
      expect(title).to.include('Login');
    });
  });

  describe('Login Functionality', function() {
    it('should show validation error for empty fields', async function() {
      const loginButton = await driver.findElement(require('selenium-webdriver').By.css('button[type="submit"], .login-button'));
      await loginButton.click();
      
      // Wait for validation messages
      await driver.sleep(1000);
      
      // Check for validation errors (common patterns)
      const hasValidationError = await helpers.isElementPresent('.error, .validation-error, .alert-danger, [data-testid="error"]');
      expect(hasValidationError).to.be.true;
    });

    it('should show error for invalid credentials', async function() {
      await helpers.typeText('input[type="email"], input[name="username"]', 'invalid@email.com');
      await helpers.typeText('input[type="password"]', 'wrongpassword');
      
      const loginButton = await driver.findElement(require('selenium-webdriver').By.css('button[type="submit"], .login-button'));
      await loginButton.click();
      
      // Wait for error message
      await driver.sleep(2000);
      
      // Check for error message
      const hasError = await helpers.isElementPresent('.error, .alert-danger, .login-error, [data-testid="error"]');
      expect(hasError).to.be.true;
    });

    it('should successfully login with valid credentials', async function() {
      const validUsername = process.env.TEST_USERNAME || 'test@example.com';
      const validPassword = process.env.TEST_PASSWORD || 'password123';
      
      await helpers.typeText('input[type="email"], input[name="username"]', validUsername);
      await helpers.typeText('input[type="password"]', validPassword);
      
      const loginButton = await driver.findElement(require('selenium-webdriver').By.css('button[type="submit"], .login-button'));
      await loginButton.click();
      
      // Wait for redirect
      await driver.sleep(3000);
      
      // Check if redirected away from login page
      const currentUrl = await driver.getCurrentUrl();
      expect(currentUrl).to.not.include('/login');
      
      // Should be on calendar or dashboard page
      expect(currentUrl).to.match(/\/(calendar|dashboard|home)/);
    });
  });

  describe('Login Form Behavior', function() {
    it('should clear fields when form is reset', async function() {
      await helpers.typeText('input[type="email"], input[name="username"]', 'test@example.com');
      await helpers.typeText('input[type="password"]', 'password123');
      
      // Find and click reset button if it exists
      const resetButton = await driver.findElement(require('selenium-webdriver').By.css('button[type="reset"], .reset-button'));
      await resetButton.click();
      
      // Check if fields are cleared
      const emailValue = await driver.findElement(require('selenium-webdriver').By.css('input[type="email"], input[name="username"]')).getAttribute('value');
      const passwordValue = await driver.findElement(require('selenium-webdriver').By.css('input[type="password"]')).getAttribute('value');
      
      expect(emailValue).to.be.empty;
      expect(passwordValue).to.be.empty;
    });

    it('should handle keyboard navigation', async function() {
      const emailField = await driver.findElement(require('selenium-webdriver').By.css('input[type="email"], input[name="username"]'));
      const passwordField = await driver.findElement(require('selenium-webdriver').By.css('input[type="password"]'));
      
      // Tab navigation
      await emailField.click();
      await emailField.sendKeys(require('selenium-webdriver').Key.TAB);
      
      // Check if focus moved to password field
      const activeElement = await driver.switchTo().activeElement();
      const activeTagName = await activeElement.getTagName();
      const activeType = await activeElement.getAttribute('type');
      
      expect(activeTagName).to.equal('input');
      expect(activeType).to.equal('password');
    });
  });

  describe('Login Page Responsiveness', function() {
    it('should be responsive on mobile viewport', async function() {
      // Set mobile viewport
      await driver.manage().window().setRect({ width: 375, height: 667 });
      await driver.sleep(1000);
      
      // Check if form elements are still visible and accessible
      await helpers.assertElementPresent('input[type="email"], input[name="username"]');
      await helpers.assertElementPresent('input[type="password"]');
      await helpers.assertElementPresent('button[type="submit"], .login-button');
      
      // Reset to desktop view
      await driver.manage().window().maximize();
    });

    it('should be responsive on tablet viewport', async function() {
      // Set tablet viewport
      await driver.manage().window().setRect({ width: 768, height: 1024 });
      await driver.sleep(1000);
      
      // Check if form elements are still visible and accessible
      await helpers.assertElementPresent('input[type="email"], input[name="username"]');
      await helpers.assertElementPresent('input[type="password"]');
      await helpers.assertElementPresent('button[type="submit"], .login-button');
      
      // Reset to desktop view
      await driver.manage().window().maximize();
    });
  });

  describe('Login Security', function() {
    it('should not display password in plain text', async function() {
      await helpers.typeText('input[type="password"]', 'testpassword');
      
      const passwordField = await driver.findElement(require('selenium-webdriver').By.css('input[type="password"]'));
      const passwordType = await passwordField.getAttribute('type');
      
      expect(passwordType).to.equal('password');
    });

    it('should handle special characters in credentials', async function() {
      const specialUsername = 'test@example.com';
      const specialPassword = 'p@ssw0rd!@#$%^&*()';
      
      await helpers.typeText('input[type="email"], input[name="username"]', specialUsername);
      await helpers.typeText('input[type="password"]', specialPassword);
      
      // Verify the fields accept special characters
      const usernameValue = await driver.findElement(require('selenium-webdriver').By.css('input[type="email"], input[name="username"]')).getAttribute('value');
      const passwordValue = await driver.findElement(require('selenium-webdriver').By.css('input[type="password"]')).getAttribute('value');
      
      expect(usernameValue).to.equal(specialUsername);
      expect(passwordValue).to.equal(specialPassword);
    });
  });
});
