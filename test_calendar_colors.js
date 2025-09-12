const fs = require('fs');
const path = require('path');

/**
 * Test Calendar Colors Loading - Node.js Version
 */

console.log('🧪 Testing Calendar Colors Loading...');
console.log('====================================\n');

// Test 1: Check if calendar_colors.json exists and is readable
const calendarColorsFile = path.join(__dirname, 'backend', 'data', 'calendar_colors.json');
console.log('1. Checking calendar_colors.json file:');
let content;
if (fs.existsSync(calendarColorsFile)) {
    console.log('   ✅ File exists');
    content = fs.readFileSync(calendarColorsFile, 'utf8');
    console.log('   📄 File size:', content.length, 'bytes');
    console.log('   📄 Content preview:');
    console.log('   ' + content.substring(0, 200) + '...\n');
} else {
    console.log('   ❌ File does not exist\n');
    process.exit(1);
}

// Test 2: Parse JSON content
console.log('2. Parsing JSON content:');
let calendarColors;
try {
    calendarColors = JSON.parse(content);
    console.log('   ✅ JSON parsed successfully');
    console.log('   📊 Found', Object.keys(calendarColors).length, 'calendar colors\n');
} catch (error) {
    console.log('   ❌ Failed to parse JSON:', error.message, '\n');
    process.exit(1);
}

// Test 3: Check for gcal URL
console.log('3. Looking for gcal calendar:');
const gcalUrl = 'http://rc.mithi.com:18008/calendars/__uids__/80b5d808-0553-1040-8d6f-0f1266787052/270504F1-5121-3A64-AD06-EA742A150C39/';
const gcalColor = calendarColors[gcalUrl];

if (gcalColor) {
    console.log('   ✅ Found gcal color:', gcalColor);
    if (gcalColor === '#00ff00') {
        console.log('   ✅ Color is correct (green)');
    } else {
        console.log('   ❌ Color is wrong (expected #00ff00, got', gcalColor + ')');
    }
} else {
    console.log('   ❌ gcal color not found');
    console.log('   🔍 Available URLs:');
    Object.keys(calendarColors).forEach(url => {
        console.log('      -', url.substring(0, 80) + '...');
    });
}

console.log('\n4. Testing URL matching:');
const testUrls = [
    'http://rc.mithi.com:18008/calendars/__uids__/80b5d808-0553-1040-8d6f-0f1266787052/270504F1-5121-3A64-AD06-EA742A150C39/',
    'http://rc.mithi.com:18008/calendars/__uids__/80b5d808-0553-1040-8d6f-0f1266787052/270504F1-5121-3A64-AD06-EA742A150C39',
    'http://rc.mithi.com:18008/calendars/__uids__/80b5d808-0553-1040-8d6f-0f1266787052/270504F1-5121-3A64-AD06-EA742A150C39/',
];

testUrls.forEach(testUrl => {
    const color = calendarColors[testUrl] || 'NOT FOUND';
    console.log('   URL:', testUrl.substring(-20), '-> Color:', color);
});

console.log('\n🎯 Summary:');
if (gcalColor === '#00ff00') {
    console.log('✅ Calendar colors are correctly configured');
    console.log('✅ gcal should appear green');
} else {
    console.log('❌ Calendar colors need to be fixed');
    console.log('💡 Check the URL format in calendar_colors.json');
}
