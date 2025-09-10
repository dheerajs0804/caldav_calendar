/**
 * Frontend Automated Test Suite for CalDAV Calendar Application
 * Browser-based testing for all completed features
 */

class FrontendTestSuite {
    constructor() {
        this.baseUrl = 'http://localhost:8000';
        this.testResults = {};
        this.authToken = null;
        
        console.log('🧪 Starting Frontend Automated Test Suite');
        console.log('==========================================');
    }
    
    /**
     * Run all frontend tests
     */
    async runAllTests() {
        await this.testUserLogin();
        await this.testListCalendars();
        await this.testAddCalendar();
        await this.testCalendarViews();
        await this.testBasicEventAddDelete();
        await this.testReminderPopup();
        await this.testReload();
        
        this.printSummary();
    }
    
    /**
     * Test 1: User Login
     */
    async testUserLogin() {
        console.log('🔐 Testing: User Login');
        console.log('----------------------');
        
        try {
            // Test valid login
            const loginResponse = await this.makeRequest('POST', '/auth/login', {
                username: 'test',
                password: 'test'
            });
            
            if (loginResponse.success && loginResponse.data?.user?.username) {
                this.testResults.login_valid = 'PASS';
                console.log('✅ Login with valid credentials: PASS');
                this.authToken = loginResponse.data.token || 'session-based';
            } else {
                this.testResults.login_valid = 'FAIL';
                console.log('❌ Login with valid credentials: FAIL');
            }
            
            // Test invalid login
            const invalidLoginResponse = await this.makeRequest('POST', '/auth/login', {
                username: 'invalid',
                password: 'invalid'
            });
            
            if (!invalidLoginResponse.success) {
                this.testResults.login_invalid = 'PASS';
                console.log('✅ Login with invalid credentials (rejection): PASS');
            } else {
                this.testResults.login_invalid = 'FAIL';
                console.log('❌ Login with invalid credentials (rejection): FAIL');
            }
            
            // Test auth status
            const statusResponse = await this.makeRequest('GET', '/auth/status');
            
            if (statusResponse.success && statusResponse.data?.authenticated) {
                this.testResults.auth_status = 'PASS';
                console.log('✅ Authentication status check: PASS');
            } else {
                this.testResults.auth_status = 'FAIL';
                console.log('❌ Authentication status check: FAIL');
            }
            
        } catch (error) {
            console.error('❌ Login tests failed:', error);
            this.testResults.login_valid = 'FAIL';
            this.testResults.login_invalid = 'FAIL';
            this.testResults.auth_status = 'FAIL';
        }
        
        console.log('');
    }
    
    /**
     * Test 2: List Calendars
     */
    async testListCalendars() {
        console.log('📅 Testing: List Calendars');
        console.log('--------------------------');
        
        try {
            const response = await this.makeRequest('GET', '/calendars/user');
            
            if (response.success && Array.isArray(response.data?.calendars)) {
                this.testResults.list_calendars = 'PASS';
                console.log(`✅ List calendars: PASS (Found ${response.data.calendars.length} calendars)`);
                
                response.data.calendars.forEach(calendar => {
                    console.log(`   📋 Calendar: ${calendar.name} (${calendar.url})`);
                });
            } else {
                this.testResults.list_calendars = 'FAIL';
                console.log('❌ List calendars: FAIL');
            }
            
            // Test calendar discovery
            const discoveryResponse = await this.makeRequest('GET', '/calendars');
            
            if (discoveryResponse.success && Array.isArray(discoveryResponse.data)) {
                this.testResults.discover_calendars = 'PASS';
                console.log('✅ Calendar discovery: PASS');
            } else {
                this.testResults.discover_calendars = 'FAIL';
                console.log('❌ Calendar discovery: FAIL');
            }
            
        } catch (error) {
            console.error('❌ Calendar listing tests failed:', error);
            this.testResults.list_calendars = 'FAIL';
            this.testResults.discover_calendars = 'FAIL';
        }
        
        console.log('');
    }
    
    /**
     * Test 3: Add Calendar
     */
    async testAddCalendar() {
        console.log('➕ Testing: Add Calendar');
        console.log('-----------------------');
        
        try {
            const calendarData = {
                name: `Test Calendar ${new Date().toISOString()}`,
                description: 'Automated test calendar',
                color: '#ff6b6b'
            };
            
            const response = await this.makeRequest('POST', '/calendars', calendarData);
            
            if (response.success && response.data?.name) {
                this.testResults.add_calendar = 'PASS';
                console.log('✅ Add calendar: PASS');
                console.log(`   📋 Created: ${response.data.name}`);
                console.log(`   🔗 URL: ${response.data.url}`);
            } else {
                this.testResults.add_calendar = 'FAIL';
                console.log('❌ Add calendar: FAIL');
                if (response.message) {
                    console.log(`   Error: ${response.message}`);
                }
            }
            
        } catch (error) {
            console.error('❌ Add calendar test failed:', error);
            this.testResults.add_calendar = 'FAIL';
        }
        
        console.log('');
    }
    
    /**
     * Test 4: Calendar Views
     */
    async testCalendarViews() {
        console.log('📊 Testing: Calendar Views');
        console.log('--------------------------');
        
        const views = ['day', 'week', 'month', 'agenda'];
        
        for (const view of views) {
            try {
                const response = await this.makeRequest('GET', `/events?view=${view}`);
                
                if (response.success) {
                    this.testResults[`view_${view}`] = 'PASS';
                    console.log(`✅ ${view.charAt(0).toUpperCase() + view.slice(1)} view: PASS`);
                } else {
                    this.testResults[`view_${view}`] = 'FAIL';
                    console.log(`❌ ${view.charAt(0).toUpperCase() + view.slice(1)} view: FAIL`);
                }
            } catch (error) {
                console.error(`❌ ${view} view test failed:`, error);
                this.testResults[`view_${view}`] = 'FAIL';
            }
        }
        
        console.log('');
    }
    
    /**
     * Test 5: Basic Event Add/Delete
     */
    async testBasicEventAddDelete() {
        console.log('📝 Testing: Basic Event Add/Delete');
        console.log('----------------------------------');
        
        try {
            const eventData = {
                title: `Test Event ${new Date().toISOString()}`,
                description: 'Automated test event',
                location: 'Test Location',
                start_time: new Date(Date.now() + 60 * 60 * 1000).toISOString().slice(0, 19),
                end_time: new Date(Date.now() + 2 * 60 * 60 * 1000).toISOString().slice(0, 19),
                all_day: false,
                attendees: [],
                reminder: {
                    enabled: false,
                    type: 'message',
                    time: 15,
                    unit: 'minutes',
                    relativeTo: 'start'
                }
            };
            
            const addResponse = await this.makeRequest('POST', '/events', eventData);
            
            if (addResponse.success && addResponse.data?.id) {
                this.testResults.add_event = 'PASS';
                console.log('✅ Add event: PASS');
                console.log(`   📝 Event ID: ${addResponse.data.id}`);
                console.log(`   📝 Title: ${addResponse.data.title}`);
                
                // Test delete
                const deleteResponse = await this.makeRequest('DELETE', `/events/${addResponse.data.id}`);
                
                if (deleteResponse.success) {
                    this.testResults.delete_event = 'PASS';
                    console.log('✅ Delete event: PASS');
                } else {
                    this.testResults.delete_event = 'FAIL';
                    console.log('❌ Delete event: FAIL');
                }
            } else {
                this.testResults.add_event = 'FAIL';
                console.log('❌ Add event: FAIL');
                if (addResponse.message) {
                    console.log(`   Error: ${addResponse.message}`);
                }
            }
            
        } catch (error) {
            console.error('❌ Event add/delete tests failed:', error);
            this.testResults.add_event = 'FAIL';
            this.testResults.delete_event = 'FAIL';
        }
        
        console.log('');
    }
    
    /**
     * Test 6: Reminder Popup
     */
    async testReminderPopup() {
        console.log('⏰ Testing: Reminder Popup');
        console.log('-------------------------');
        
        try {
            const eventData = {
                title: 'Reminder Test Event',
                description: 'Event with reminder for testing',
                start_time: new Date(Date.now() + 5 * 60 * 1000).toISOString().slice(0, 19),
                end_time: new Date(Date.now() + 6 * 60 * 1000).toISOString().slice(0, 19),
                all_day: false,
                reminder: {
                    enabled: true,
                    type: 'message',
                    time: 1,
                    unit: 'minutes',
                    relativeTo: 'start'
                }
            };
            
            const response = await this.makeRequest('POST', '/events', eventData);
            
            if (response.success && response.data?.reminder?.enabled) {
                this.testResults.reminder_popup = 'PASS';
                console.log('✅ Reminder popup (event with reminder): PASS');
                console.log(`   ⏰ Reminder: ${response.data.reminder.time} ${response.data.reminder.unit} before`);
            } else {
                this.testResults.reminder_popup = 'FAIL';
                console.log('❌ Reminder popup: FAIL');
            }
            
        } catch (error) {
            console.error('❌ Reminder popup test failed:', error);
            this.testResults.reminder_popup = 'FAIL';
        }
        
        console.log('');
    }
    
    /**
     * Test 7: Reload
     */
    async testReload() {
        console.log('🔄 Testing: Reload');
        console.log('------------------');
        
        try {
            // Test calendar reload
            const calendarResponse = await this.makeRequest('GET', '/calendars');
            
            if (calendarResponse.success) {
                this.testResults.reload_calendars = 'PASS';
                console.log('✅ Calendar reload: PASS');
            } else {
                this.testResults.reload_calendars = 'FAIL';
                console.log('❌ Calendar reload: FAIL');
            }
            
            // Test events reload
            const eventsResponse = await this.makeRequest('GET', '/events');
            
            if (eventsResponse.success) {
                this.testResults.reload_events = 'PASS';
                console.log('✅ Events reload: PASS');
            } else {
                this.testResults.reload_events = 'FAIL';
                console.log('❌ Events reload: FAIL');
            }
            
        } catch (error) {
            console.error('❌ Reload tests failed:', error);
            this.testResults.reload_calendars = 'FAIL';
            this.testResults.reload_events = 'FAIL';
        }
        
        console.log('');
    }
    
    /**
     * Make HTTP request
     */
    async makeRequest(method, endpoint, data = null) {
        const url = this.baseUrl + endpoint;
        
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            credentials: 'include' // Include cookies for session management
        };
        
        if (data && method === 'POST') {
            options.body = JSON.stringify(data);
        }
        
        try {
            const response = await fetch(url, options);
            const responseData = await response.json();
            
            return {
                success: response.ok,
                data: responseData,
                status: response.status
            };
        } catch (error) {
            return {
                success: false,
                error: error.message
            };
        }
    }
    
    /**
     * Print test summary
     */
    printSummary() {
        console.log('📊 Test Summary');
        console.log('===============');
        
        const totalTests = Object.keys(this.testResults).length;
        const passedTests = Object.values(this.testResults).filter(result => result === 'PASS').length;
        const failedTests = totalTests - passedTests;
        
        console.log(`Total Tests: ${totalTests}`);
        console.log(`Passed: ${passedTests} ✅`);
        console.log(`Failed: ${failedTests} ❌`);
        console.log(`Success Rate: ${((passedTests / totalTests) * 100).toFixed(2)}%`);
        console.log('');
        
        console.log('Detailed Results:');
        console.log('-----------------');
        
        Object.entries(this.testResults).forEach(([test, result]) => {
            const status = result === 'PASS' ? '✅' : '❌';
            const testName = test.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            console.log(`${status} ${testName}`);
        });
        
        console.log('');
        
        if (failedTests > 0) {
            console.log('⚠️  Some tests failed. Check the logs above for details.');
        } else {
            console.log('🎉 All tests passed! Your CalDAV Calendar App is working perfectly!');
        }
    }
}

// Auto-run tests when loaded in browser
if (typeof window !== 'undefined') {
    window.FrontendTestSuite = FrontendTestSuite;
    
    // Run tests automatically
    document.addEventListener('DOMContentLoaded', () => {
        const testSuite = new FrontendTestSuite();
        testSuite.runAllTests();
    });
}

// Export for Node.js if needed
if (typeof module !== 'undefined' && module.exports) {
    module.exports = FrontendTestSuite;
}
