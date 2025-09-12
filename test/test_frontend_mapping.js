// Simple test to check calendar discovery order
console.log("=== Calendar Discovery Test ===");

// Simulate what the frontend would see
const mockCalendars = [
    { id: 1, name: "Calendar 1", url: "http://rc.mithi.com:18008/calendars/__uids__/80b5d808-0553-1040-8d6f-0f1266787052/52FC2A7E-ACAF-DB42-DB5D-883657574B6D/", color: "#ff0000" },
    { id: 2, name: "Calendar 2", url: "http://rc.mithi.com:18008/calendars/__uids__/80b5d808-0553-1040-8d6f-0f1266787052/B6A24496-9482-1561-F12A-05ACF41A1FF4/", color: "#ff0000" },
    { id: 3, name: "redcal", url: "http://rc.mithi.com:18008/calendars/__uids__/80b5d808-0553-1040-8d6f-0f1266787052/29186E4D-03B1-3410-16C5-2F6239B60681/", color: "#ff0000" }
];

console.log("Available calendars:");
mockCalendars.forEach(cal => {
    console.log(`ID: ${cal.id}, Name: ${cal.name}, Color: ${cal.color}`);
});

console.log("\nTesting event creation for calendar ID 3:");
const targetCalendar = mockCalendars.find(cal => cal.id === 3);
if (targetCalendar) {
    console.log(`Target calendar: ${targetCalendar.name}`);
    console.log(`Expected color: ${targetCalendar.color}`);
    console.log("✅ Frontend should send calendar_id: 3");
    console.log("✅ Backend should find red color: #ff0000");
} else {
    console.log("❌ Calendar ID 3 not found!");
}

console.log("\n=== Test Complete ===");
