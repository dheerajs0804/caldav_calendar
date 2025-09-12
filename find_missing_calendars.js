const fs = require('fs');
const path = require('path');

/**
 * Find Missing Calendar URLs and Colors
 * This script will help identify which calendar URLs are missing from calendar_colors.json
 */

console.log('🔍 Finding Missing Calendar URLs and Colors...');
console.log('==============================================\n');

// Read current calendar colors
const calendarColorsFile = path.join(__dirname, 'backend', 'data', 'calendar_colors.json');
let calendarColors = {};

if (fs.existsSync(calendarColorsFile)) {
    const content = fs.readFileSync(calendarColorsFile, 'utf8');
    calendarColors = JSON.parse(content);
    console.log('📊 Current calendar colors:');
    Object.entries(calendarColors).forEach(([url, color]) => {
        const calendarName = url.split('/').pop() || 'Unknown';
        console.log(`   - ${calendarName}: ${color}`);
    });
} else {
    console.log('❌ calendar_colors.json not found');
    process.exit(1);
}

console.log('\n💡 Instructions:');
console.log('1. Open browser developer tools (F12)');
console.log('2. Go to Console tab');
console.log('3. Look for "📅 Calendar Events Debug:" messages');
console.log('4. Find the actual calendar URLs for gcal and redcalendar');
console.log('5. Add them to calendar_colors.json with the correct colors:');
console.log('   - gcal: #00ff00 (green)');
console.log('   - redcalendar: #ff0000 (red)');

console.log('\n🔧 Example format:');
console.log('{');
console.log('  "http://rc.mithi.com:18008/calendars/.../GCAL-ACTUAL-UUID/": "#00ff00",');
console.log('  "http://rc.mithi.com:18008/calendars/.../REDCALENDAR-ACTUAL-UUID/": "#ff0000"');
console.log('}');

console.log('\n🎯 The issue is that the backend is not finding the stored colors');
console.log('   because the actual calendar URLs don\'t match the stored URLs.');
