const { Builder } = require('selenium-webdriver');
const chrome = require('selenium-webdriver/chrome');
const { By } = require('selenium-webdriver');

async function inspectUI() {
  console.log('Inspecting Calendar UI...');
  
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
    
    // Check login page
    console.log('\n=== LOGIN PAGE INSPECTION ===');
    await driver.get('http://[::1]:4200/login');
    await driver.sleep(2000);
    
    const loginInputs = await driver.findElements(By.css('input'));
    console.log(`Found ${loginInputs.length} input elements on login page:`);
    for (let i = 0; i < loginInputs.length; i++) {
      const type = await loginInputs[i].getAttribute('type');
      const name = await loginInputs[i].getAttribute('name');
      const placeholder = await loginInputs[i].getAttribute('placeholder');
      console.log(`  Input ${i}: type="${type}", name="${name}", placeholder="${placeholder}"`);
    }
    
    const loginButtons = await driver.findElements(By.css('button'));
    console.log(`Found ${loginButtons.length} button elements on login page:`);
    for (let i = 0; i < loginButtons.length; i++) {
      const type = await loginButtons[i].getAttribute('type');
      const text = await loginButtons[i].getText();
      const className = await loginButtons[i].getAttribute('class');
      console.log(`  Button ${i}: type="${type}", text="${text}", class="${className}"`);
    }
    
    // Check calendar page
    console.log('\n=== CALENDAR PAGE INSPECTION ===');
    await driver.get('http://[::1]:4200/calendar');
    await driver.sleep(2000);
    
    const calendarContainers = await driver.findElements(By.css('div[class*="calendar"], div[class*="Calendar"]'));
    console.log(`Found ${calendarContainers.length} calendar-related divs:`);
    for (let i = 0; i < calendarContainers.length; i++) {
      const className = await calendarContainers[i].getAttribute('class');
      console.log(`  Calendar div ${i}: class="${className}"`);
    }
    
    // Check for any elements with calendar in class name
    const allCalendarElements = await driver.findElements(By.css('[class*="calendar"], [class*="Calendar"]'));
    console.log(`Found ${allCalendarElements.length} elements with "calendar" in class name:`);
    for (let i = 0; i < Math.min(allCalendarElements.length, 10); i++) {
      const tagName = await allCalendarElements[i].getTagName();
      const className = await allCalendarElements[i].getAttribute('class');
      console.log(`  ${tagName}: class="${className}"`);
    }
    
    await driver.quit();
    console.log('\n✅ UI inspection completed!');
    
  } catch (error) {
    console.error('❌ Inspection failed:', error.message);
    if (driver) {
      await driver.quit();
    }
  }
}

inspectUI();
