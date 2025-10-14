const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

console.log('Installing ChromeDriver...');

try {
  // Install webdriver-manager globally
  console.log('Installing webdriver-manager...');
  execSync('npm install -g webdriver-manager', { stdio: 'inherit' });
  
  // Update webdriver-manager
  console.log('Updating webdriver-manager...');
  execSync('webdriver-manager update', { stdio: 'inherit' });
  
  // Start webdriver-manager
  console.log('Starting webdriver-manager...');
  execSync('webdriver-manager start', { stdio: 'inherit' });
  
} catch (error) {
  console.error('Failed to install ChromeDriver:', error.message);
  console.log('\nManual installation steps:');
  console.log('1. Go to https://chromedriver.chromium.org/');
  console.log('2. Download ChromeDriver for your Chrome version');
  console.log('3. Extract chromedriver.exe to C:\\Windows\\System32\\');
  console.log('4. Or add the extracted folder to your PATH');
}
