const { Builder } = require('selenium-webdriver');
const chrome = require('selenium-webdriver/chrome');

async function quickTest() {
  console.log('Quick ChromeDriver test...');
  
  try {
    const options = new chrome.Options();
    options.addArguments('--no-sandbox');
    
    // Use the running ChromeDriver
    const driver = await new Builder()
      .forBrowser('chrome')
      .setChromeOptions(options)
      .usingServer('http://localhost:9515')
      .build();
    
    console.log('✅ Driver created successfully!');
    
    await driver.get('https://www.google.com');
    const title = await driver.getTitle();
    console.log('✅ Page loaded:', title);
    
    await driver.quit();
    console.log('✅ Test passed!');
    
  } catch (error) {
    console.error('❌ Error:', error.message);
  }
}

quickTest();
