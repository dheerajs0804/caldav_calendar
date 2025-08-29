# Mithi Calendar - Comprehensive Test Cases

## 📋 Test Cases Overview
**Total Test Cases: 120+**
**Coverage Areas**: Frontend (Angular), Backend (PHP), CalDAV Integration, Email System, Database Operations

---

## 🎯 **1. User Authentication & Authorization (15 Test Cases)**

| TC# | Test Case | Test Steps | Expected Result | Priority | Category |
|-----|-----------|------------|-----------------|----------|----------|
| TC001 | Valid CalDAV Login | 1. Enter valid CalDAV credentials<br>2. Submit login form | User authenticated, calendar loads | High | Auth |
| TC002 | Invalid CalDAV Login | 1. Enter invalid credentials<br>2. Submit login form | Error message displayed | High | Auth |
| TC003 | Empty Credentials | 1. Leave username/password empty<br>2. Submit form | Validation error shown | High | Auth |
| TC004 | Special Characters in Credentials | 1. Use special chars in username/password<br>2. Submit form | Handled correctly | Medium | Auth |
| TC005 | Long Credentials | 1. Use very long username/password<br>2. Submit form | Handled correctly | Medium | Auth |
| TC006 | Session Timeout | 1. Login successfully<br>2. Wait for session timeout<br>3. Try to access calendar | Redirected to login | High | Auth |
| TC007 | Concurrent Login Attempts | 1. Open multiple tabs<br>2. Login in each tab | All sessions work correctly | Medium | Auth |
| TC008 | Logout Functionality | 1. Login successfully<br>2. Click logout | Session cleared, redirected to login | High | Auth |
| TC009 | Remember Me Functionality | 1. Check "Remember Me"<br>2. Close browser<br>3. Reopen | Still logged in | Medium | Auth |
| TC010 | Password Reset | 1. Click "Forgot Password"<br>2. Enter email | Reset email sent | Medium | Auth |
| TC011 | Account Lockout | 1. Enter wrong password 5 times | Account temporarily locked | High | Auth |
| TC012 | SSL/TLS Connection | 1. Check connection to CalDAV server | Secure connection established | High | Auth |
| TC013 | Token Refresh | 1. Use expired token<br>2. Make API call | Token automatically refreshed | Medium | Auth |
| TC014 | Multi-Factor Authentication | 1. Enable 2FA<br>2. Login with 2FA | 2FA prompt shown | Medium | Auth |
| TC015 | Role-Based Access | 1. Login as different user roles<br>2. Check permissions | Correct access levels | High | Auth |

---

## 📅 **2. Calendar View & Navigation (20 Test Cases)**

| TC# | Test Case | Test Steps | Expected Result | Priority | Category |
|-----|-----------|------------|-----------------|----------|----------|
| TC016 | Month View Display | 1. Navigate to month view<br>2. Check calendar grid | Month view displayed correctly | High | UI |
| TC017 | Week View Display | 1. Switch to week view<br>2. Check week layout | Week view displayed correctly | High | UI |
| TC018 | Day View Display | 1. Switch to day view<br>2. Check day layout | Day view displayed correctly | High | UI |
| TC019 | Agenda View Display | 1. Switch to agenda view<br>2. Check event list | Agenda view displayed correctly | Medium | UI |
| TC020 | Navigation Between Months | 1. Click next/previous month | Month changes correctly | High | UI |
| TC021 | Navigation Between Years | 1. Click next/previous year | Year changes correctly | High | UI |
| TC022 | Today Button Functionality | 1. Navigate to different date<br>2. Click "Today" | Returns to current date | High | UI |
| TC023 | Date Picker Functionality | 1. Click on date picker<br>2. Select different date | Calendar jumps to selected date | High | UI |
| TC024 | Week Number Display | 1. Enable week numbers<br>2. Check display | Week numbers shown correctly | Medium | UI |
| TC025 | Working Hours Highlight | 1. Check business hours | Working hours highlighted | Medium | UI |
| TC026 | Weekend Styling | 1. Check weekend days | Weekends styled differently | Medium | UI |
| TC027 | Holiday Display | 1. Check holiday dates | Holidays marked correctly | Medium | UI |
| TC028 | Timezone Display | 1. Change timezone<br>2. Check calendar | Timezone applied correctly | High | UI |
| TC029 | Daylight Saving Time | 1. Navigate through DST period | DST handled correctly | Medium | UI |
| TC030 | Responsive Design - Mobile | 1. Resize to mobile dimensions | Mobile layout works | High | UI |
| TC031 | Responsive Design - Tablet | 1. Resize to tablet dimensions | Tablet layout works | High | UI |
| TC032 | Responsive Design - Desktop | 1. Resize to desktop dimensions | Desktop layout works | High | UI |
| TC033 | Touch Gestures | 1. Use touch gestures on mobile | Gestures work correctly | Medium | UI |
| TC034 | Keyboard Navigation | 1. Use arrow keys to navigate | Keyboard navigation works | Medium | UI |
| TC035 | Accessibility Features | 1. Check screen reader support | Accessibility features work | Medium | UI |

---

## ➕ **3. Event Creation & Management (25 Test Cases)**

| TC# | Test Case | Test Steps | Expected Result | Priority | Category |
|-----|-----------|------------|-----------------|----------|----------|
| TC036 | Create Simple Event | 1. Click "New Event"<br>2. Fill basic details<br>3. Save | Event created successfully | High | Events |
| TC037 | Create Event with Title Only | 1. Enter only title<br>2. Save event | Event created with default times | High | Events |
| TC038 | Create All-Day Event | 1. Check "All Day" option<br>2. Set start/end dates | All-day event created | High | Events |
| TC039 | Create Multi-Day Event | 1. Set different start/end dates | Multi-day event created | High | Events |
| TC040 | Create Recurring Event | 1. Set recurrence pattern<br>2. Save event | Recurring event created | High | Events |
| TC041 | Create Event with Description | 1. Add description<br>2. Save event | Description saved correctly | Medium | Events |
| TC042 | Create Event with Location | 1. Add location<br>2. Save event | Location saved correctly | Medium | Events |
| TC043 | Create Event with Reminders | 1. Set reminder time<br>2. Save event | Reminder configured | Medium | Events |
| TC044 | Create Event with Categories | 1. Select category<br>2. Save event | Category assigned | Medium | Events |
| TC045 | Create Event with Color | 1. Choose event color<br>2. Save event | Color applied | Low | Events |
| TC046 | Create Event with Attachments | 1. Add file attachment<br>2. Save event | Attachment saved | Medium | Events |
| TC047 | Create Event with Custom Fields | 1. Fill custom fields<br>2. Save event | Custom fields saved | Medium | Events |
| TC048 | Edit Existing Event | 1. Click on event<br>2. Modify details<br>3. Save | Event updated successfully | High | Events |
| TC049 | Delete Event | 1. Select event<br>2. Click delete<br>3. Confirm | Event deleted | High | Events |
| TC050 | Duplicate Event | 1. Select event<br>2. Click duplicate | Event duplicated | Medium | Events |
| TC051 | Move Event | 1. Drag event to new date/time | Event moved correctly | High | Events |
| TC052 | Resize Event | 1. Drag event edges to resize | Event duration changed | High | Events |
| TC053 | Copy Event | 1. Select event<br>2. Copy to clipboard | Event copied | Medium | Events |
| TC054 | Event Validation - Required Fields | 1. Try to save without title | Validation error shown | High | Events |
| TC055 | Event Validation - Time Logic | 1. Set end time before start time | Validation error shown | High | Events |
| TC056 | Event Validation - Date Range | 1. Set date far in past/future | Validation error shown | Medium | Events |
| TC057 | Event Search | 1. Use search function<br>2. Enter event title | Search results displayed | Medium | Events |
| TC058 | Event Filtering | 1. Apply category filter<br>2. Check results | Filtered events shown | Medium | Events |
| TC059 | Event Sorting | 1. Change sort order<br>2. Check results | Events sorted correctly | Medium | Events |
| TC060 | Bulk Event Operations | 1. Select multiple events<br>2. Perform bulk action | Bulk operation completed | Medium | Events |

---

## 👥 **4. Attendee Management (15 Test Cases)**

| TC# | Test Case | Test Steps | Expected Result | Priority | Category |
|-----|-----------|------------|-----------------|----------|----------|
| TC061 | Add Single Attendee | 1. Add attendee email<br>2. Save event | Attendee added successfully | High | Attendees |
| TC062 | Add Multiple Attendees | 1. Add multiple email addresses<br>2. Save event | All attendees added | High | Attendees |
| TC063 | Add Attendee with Name | 1. Add email + name<br>2. Save event | Name and email saved | Medium | Attendees |
| TC064 | Add Attendee with Role | 1. Set attendee role<br>2. Save event | Role assigned correctly | Medium | Attendees |
| TC065 | Remove Attendee | 1. Select attendee<br>2. Click remove | Attendee removed | High | Attendees |
| TC066 | Edit Attendee Details | 1. Click on attendee<br>2. Modify details | Details updated | Medium | Attendees |
| TC067 | Attendee Email Validation | 1. Enter invalid email format | Validation error shown | High | Attendees |
| TC068 | Duplicate Attendee Prevention | 1. Try to add same email twice | Duplicate prevented | Medium | Attendees |
| TC069 | Attendee Response Tracking | 1. Send invitation<br>2. Check response status | Response tracked | High | Attendees |
| TC070 | Attendee Availability Check | 1. Check attendee calendar | Availability shown | Medium | Attendees |
| TC071 | Attendee Permission Levels | 1. Set different permissions<br>2. Test access | Permissions enforced | High | Attendees |
| TC072 | Attendee Group Management | 1. Create attendee group<br>2. Add to events | Group functionality works | Medium | Attendees |
| TC073 | Attendee Import/Export | 1. Import attendee list<br>2. Export attendee data | Import/export works | Low | Attendees |
| TC074 | Attendee Communication History | 1. Check communication log | History displayed | Medium | Attendees |
| TC075 | Attendee Preferences | 1. Set attendee preferences<br>2. Apply to events | Preferences respected | Low | Attendees |

---

## 📧 **5. Email & Invitation System (20 Test Cases)**

| TC# | Test Case | Test Steps | Expected Result | Priority | Category |
|-----|-----------|------------|-----------------|----------|----------|
| TC076 | Send Event Invitation | 1. Create event with attendees<br>2. Save event | Invitation emails sent | High | Email |
| TC077 | Email Template Rendering | 1. Check email content | Template renders correctly | High | Email |
| TC078 | iCalendar Attachment | 1. Check email attachments | .ics file attached | High | Email |
| TC079 | HTML Email Format | 1. Check email format | HTML email sent | Medium | Email |
| TC080 | Plain Text Email Format | 1. Check email format | Plain text version sent | Medium | Email |
| TC081 | Email Subject Line | 1. Check email subject | Subject line correct | Medium | Email |
| TC082 | Email Sender Address | 1. Check from address | Sender address correct | High | Email |
| TC083 | Email Reply-To Address | 1. Check reply-to | Reply-to address correct | Medium | Email |
| TC084 | Email BCC Functionality | 1. Enable BCC<br>2. Send invitation | BCC recipients added | Medium | Email |
| TC085 | Email Delivery Confirmation | 1. Send invitation<br>2. Check delivery status | Delivery confirmed | Medium | Email |
| TC086 | Email Bounce Handling | 1. Send to invalid email | Bounce handled correctly | Medium | Email |
| TC087 | Email Rate Limiting | 1. Send multiple emails quickly | Rate limiting applied | Medium | Email |
| TC088 | Email Queue Management | 1. Send many invitations | Queue processed correctly | Medium | Email |
| TC089 | Email Template Customization | 1. Modify email template | Custom template used | Low | Email |
| TC090 | Email Language Support | 1. Change language setting | Email in correct language | Low | Email |
| TC091 | Email Signature | 1. Check email signature | Signature included | Low | Email |
| TC092 | Email Tracking | 1. Send invitation<br>2. Check open/click rates | Tracking data collected | Low | Email |
| TC093 | Email Scheduling | 1. Schedule email for later | Email sent at scheduled time | Medium | Email |
| TC094 | Email Reminder System | 1. Set email reminders | Reminders sent correctly | Medium | Email |
| TC095 | Email Unsubscribe | 1. Include unsubscribe link | Unsubscribe functionality works | Medium | Email |

---

## 🔄 **6. CalDAV Integration (15 Test Cases)**

| TC# | Test Case | Test Steps | Expected Result | Priority | Category |
|-----|-----------|------------|-----------------|----------|----------|
| TC096 | CalDAV Server Connection | 1. Test server connectivity | Connection established | High | CalDAV |
| TC097 | Calendar Discovery | 1. Discover available calendars | Calendars found | High | CalDAV |
| TC098 | Calendar Authentication | 1. Authenticate with server | Authentication successful | High | CalDAV |
| TC099 | Event Sync to CalDAV | 1. Create event locally<br>2. Check server | Event synced to server | High | CalDAV |
| TC100 | Event Sync from CalDAV | 1. Create event on server<br>2. Check local | Event synced locally | High | CalDAV |
| TC101 | Event Update Sync | 1. Update event locally<br>2. Check server | Update synced to server | High | CalDAV |
| TC102 | Event Delete Sync | 1. Delete event locally<br>2. Check server | Deletion synced to server | High | CalDAV |
| TC103 | Conflict Resolution | 1. Create conflicting events | Conflict resolved correctly | Medium | CalDAV |
| TC104 | Offline Mode | 1. Disconnect from server<br>2. Create events | Offline mode works | Medium | CalDAV |
| TC105 | Sync on Reconnect | 1. Reconnect to server<br>2. Check sync | Sync completed | Medium | CalDAV |
| TC106 | Large Calendar Sync | 1. Sync calendar with many events | Large sync completed | Medium | CalDAV |
| TC107 | Calendar Sharing | 1. Share calendar with user | Sharing works correctly | Medium | CalDAV |
| TC108 | Calendar Permissions | 1. Set different permission levels | Permissions enforced | High | CalDAV |
| TC109 | Calendar Export | 1. Export calendar data | Export completed | Low | CalDAV |
| TC110 | Calendar Import | 1. Import calendar file | Import completed | Low | CalDAV |

---

## 🗄️ **7. Database & Storage (10 Test Cases)**

| TC# | Test Case | Test Steps | Expected Result | Priority | Category |
|-----|-----------|------------|-----------------|----------|----------|
| TC111 | Local Event Storage | 1. Create event<br>2. Check local storage | Event stored locally | High | Storage |
| TC112 | Event Data Persistence | 1. Restart application<br>2. Check events | Events persist | High | Storage |
| TC113 | Data Backup | 1. Perform backup operation | Backup created | Medium | Storage |
| TC114 | Data Restore | 1. Restore from backup | Data restored correctly | Medium | Storage |
| TC115 | Data Migration | 1. Migrate to new schema | Migration completed | Medium | Storage |
| TC116 | Data Validation | 1. Check data integrity | Data valid | High | Storage |
| TC117 | Performance with Large Data | 1. Load many events | Performance acceptable | Medium | Storage |
| TC118 | Data Cleanup | 1. Run cleanup routine | Old data removed | Low | Storage |
| TC119 | Data Export | 1. Export all data | Export completed | Low | Storage |
| TC120 | Data Import | 1. Import data file | Import completed | Low | Storage |

---

## 🧪 **8. Performance & Load Testing (5 Test Cases)**

| TC# | Test Case | Test Steps | Expected Result | Priority | Category |
|-----|-----------|------------|-----------------|----------|----------|
| TC121 | Page Load Performance | 1. Measure page load time | Load time < 3 seconds | High | Performance |
| TC122 | Event Rendering Performance | 1. Load calendar with 1000 events | Smooth scrolling | Medium | Performance |
| TC123 | Search Performance | 1. Search in large dataset | Results in < 1 second | Medium | Performance |
| TC124 | Memory Usage | 1. Monitor memory consumption | Memory usage stable | Medium | Performance |
| TC125 | Concurrent User Load | 1. Simulate 100 concurrent users | System remains responsive | High | Performance |

---

## 🔒 **9. Security Testing (5 Test Cases)**

| TC# | Test Case | Test Steps | Expected Result | Priority | Category |
|-----|-----------|------------|-----------------|----------|----------|
| TC126 | SQL Injection Prevention | 1. Attempt SQL injection | Attack prevented | High | Security |
| TC127 | XSS Prevention | 1. Attempt XSS attack | Attack prevented | High | Security |
| TC128 | CSRF Protection | 1. Attempt CSRF attack | Attack prevented | High | Security |
| TC129 | Input Validation | 1. Enter malicious input | Input sanitized | High | Security |
| TC130 | Authentication Bypass | 1. Attempt to bypass auth | Access denied | High | Security |

---

## 📱 **10. Cross-Platform Testing (5 Test Cases)**

| TC# | Test Case | Test Steps | Expected Result | Priority | Category |
|-----|-----------|------------|-----------------|----------|----------|
| TC131 | Chrome Browser | 1. Test in Chrome | All features work | High | Cross-Platform |
| TC132 | Firefox Browser | 1. Test in Firefox | All features work | High | Cross-Platform |
| TC133 | Safari Browser | 1. Test in Safari | All features work | High | Cross-Platform |
| TC134 | Edge Browser | 1. Test in Edge | All features work | High | Cross-Platform |
| TC135 | Mobile Browsers | 1. Test on mobile | Mobile features work | High | Cross-Platform |

---

## 📊 **Test Execution Summary**

### **Priority Distribution:**
- **High Priority**: 65 test cases (Critical functionality)
- **Medium Priority**: 40 test cases (Important features)
- **Low Priority**: 15 test cases (Nice-to-have features)

### **Category Distribution:**
- **Authentication & Authorization**: 15 test cases
- **Calendar View & Navigation**: 20 test cases
- **Event Management**: 25 test cases
- **Attendee Management**: 15 test cases
- **Email System**: 20 test cases
- **CalDAV Integration**: 15 test cases
- **Database & Storage**: 10 test cases
- **Performance & Load**: 5 test cases
- **Security**: 5 test cases
- **Cross-Platform**: 5 test cases

### **Testing Tools Recommended:**
- **Frontend Testing**: Jasmine, Karma, Protractor
- **Backend Testing**: PHPUnit, Codeception
- **API Testing**: Postman, Newman
- **Performance Testing**: Apache JMeter, K6
- **Security Testing**: OWASP ZAP, Burp Suite
- **Cross-Browser Testing**: BrowserStack, Sauce Labs

### **Test Environment Requirements:**
- **Development**: Local PHP server, Angular dev server
- **Staging**: Staging CalDAV server, test SMTP
- **Production**: Production CalDAV server, live SMTP
- **Mobile Testing**: Various mobile devices and browsers
