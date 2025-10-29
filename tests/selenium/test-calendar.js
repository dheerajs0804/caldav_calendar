const { Builder } = require('selenium-webdriver');
const chrome = require('selenium-webdriver/chrome');

async function testCalendarApp() {
  console.log('Testing Calendar Application...');
  
  let driver;
  try {
    const options = new chrome.Options();
    options.addArguments('--no-sandbox');
    options.addArguments('--disable-dev-shm-usage');
    
    driver = await new Builder()
      .forBrowser('chrome')
      .setChromeOptions(options)
      .usingServer('http://localhost:9515')
      .build();
    
    console.log('✅ Driver created successfully!');
    
    // Test calendar app
    console.log('Navigating to calendar app...');
    await driver.get('http://[::1]:4200');
    
    const title = await driver.getTitle();
    console.log('✅ Calendar app loaded:', title);
    
    // Wait a bit for page to load
    await driver.sleep(3000);
    
    // Check for elements
    const inputs = await driver.findElements(require('selenium-webdriver').By.css('input'));
    const buttons = await driver.findElements(require('selenium-webdriver').By.css('button'));
    
    console.log(`✅ Found ${inputs.length} input elements`);
    console.log(`✅ Found ${buttons.length} button elements`);
    
    await driver.quit();
    console.log('✅ Calendar app test passed!');
    
  } catch (error) {
    console.error('❌ Calendar app test failed:', error.message);
    if (driver) {
      await driver.quit();
    }
  }
}

testCalendarApp();
