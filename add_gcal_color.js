const fs = require('fs');
const path = require('path');

/**
 * Find and Fix Gcal Color - Node.js Version
 * This script will help find the gcal calendar URL and add green color
 */

console.log('🔍 Finding and fixing gcal color...');
console.log('==================================\n');

// Read the current calendar colors
const calendarColorsFile = path.join(__dirname, 'backend', 'data', 'calendar_colors.json');
let calendarColors = {};

if (fs.existsSync(calendarColorsFile)) {
    calendarColors = JSON.parse(fs.readFileSync(calendarColorsFile, 'utf8'));
}

console.log('📊 Current calendar colors:');
Object.entries(calendarColors).forEach(([url, color]) => {
    const calendarName = url.split('/').pop() || 'Unknown';
    console.log(`   - ${calendarName}: ${color}`);
});

console.log('\n💡 Instructions:');
console.log('1. Open browser developer tools (F12)');
console.log('2. Go to Console tab');
console.log('3. Look for "📅 Calendar Events Debug:" messages');
console.log('4. Find the gcal calendar URL');
console.log('5. Copy the URL and run this command:');
console.log('   node add_gcal_color.js "ACTUAL_GCAL_URL"');

// If URL provided as argument, add it
const gcalUrl = process.argv[2];
if (gcalUrl) {
    console.log(`\n🔧 Adding green color (#00ff00) to gcal...`);
    console.log(`📍 URL: ${gcalUrl}`);
    
    calendarColors[gcalUrl] = '#00ff00';
    
    // Write back to file
    fs.writeFileSync(calendarColorsFile, JSON.stringify(calendarColors, null, 4));
    
    console.log('✅ Added green color (#00ff00) to gcal');
    console.log('\n🎯 Next Steps:');
    console.log('1. Restart the backend server');
    console.log('2. Refresh the Angular frontend');
    console.log('3. The gcal1 event should now appear green!');
} else {
    console.log('\n❌ No gcal URL provided');
    console.log('Usage: node add_gcal_color.js "http://rc.mithi.com:18008/calendars/.../GCAL-UUID/"');
}
