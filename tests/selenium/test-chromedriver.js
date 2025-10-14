const { Builder } = require('selenium-webdriver');
const chrome = require('selenium-webdriver/chrome');

async function testChromeDriver() {
  console.log('Testing ChromeDriver...');
  
  let driver;
  try {
    const options = new chrome.Options();
    options.addArguments('--no-sandbox');
    options.addArguments('--disable-dev-shm-usage');
    
    console.log('Creating driver...');
    driver = await new Builder()
      .forBrowser('chrome')
      .setChromeOptions(options)
      .build();
    
    console.log('✅ ChromeDriver is working!');
    
    console.log('Navigating to Google...');
    await driver.get('https://www.google.com');
    
    const title = await driver.getTitle();
    console.log('✅ Page title:', title);
    
    await driver.quit();
    console.log('✅ Test completed successfully!');
    
  } catch (error) {
    console.error('❌ Test failed:', error.message);
    if (driver) {
      await driver.quit();
    }
  }
}

testChromeDriver();
