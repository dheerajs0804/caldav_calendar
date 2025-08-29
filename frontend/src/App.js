import React, { useState, useEffect } from 'react';
import './App.css';
import DayView from './components/Calendar/DayView.js';
import WeekView from './components/Calendar/WeekView.tsx';
import MonthView from './components/Calendar/MonthView.tsx';

function App() {
  console.log('App component is loading...'); // Debug log
  
  const views = [
    { type: 'day', label: 'Day', icon: '📅' },
    { type: 'week', label: 'Week', icon: '📅' },
    { type: 'month', label: 'Month', icon: '📅' },
    { type: 'agenda', label: 'Agenda', icon: '📋' }
  ];
  
  const [currentView, setCurrentView] = useState(views[1]); // Start with week view
  const [currentDate, setCurrentDate] = useState(new Date());
  const [calendars, setCalendars] = useState([]);
  const [events, setEvents] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [showAddEventModal, setShowAddEventModal] = useState(false);
  const [newEvent, setNewEvent] = useState({
    summary: '',
    location: '',
    description: '',
    start_date: '',
    start_time: '',
    end_date: '',
    end_time: '',
    all_day: false,
    reminder: {
      enabled: false,
      type: 'message',
      time: 15,
      unit: 'minutes',
      relativeTo: 'start'
    }
  });
  
  
  // Fetch calendars from backend
  useEffect(() => {
    fetchCalendars();
  }, []);

  // Fetch events when calendars change
  useEffect(() => {
    if (calendars.length > 0) {
      fetchEvents();
    }
  }, [calendars]);

  // Check for reminders every minute
  useEffect(() => {
    if (events.length > 0) {
      const reminderInterval = setInterval(() => {
        checkReminders();
      }, 60000); // Check every minute

      return () => clearInterval(reminderInterval);
    }
  }, [events]);

  // Initialize new event with current date when modal opens
  useEffect(() => {
    if (showAddEventModal) {
      const now = new Date();
      const currentTime = now.toTimeString().slice(0, 5);
      const endTime = new Date(now.getTime() + 60 * 60 * 1000).toTimeString().slice(0, 5);
      
             setNewEvent({
         summary: '',
         location: '',
         description: '',
         start_date: now.toISOString().split('T')[0],
         start_time: currentTime,
         end_date: now.toISOString().split('T')[0],
         end_time: endTime,
         all_day: false,
         reminder: {
           enabled: false,
           type: 'message',
           time: 15,
           unit: 'minutes',
           relativeTo: 'start'
         }
       });
    }
  }, [showAddEventModal]);

  const fetchCalendars = async () => {
    try {
      setLoading(true);
      const response = await fetch('http://localhost:8000/calendars');
      const data = await response.json();
      
      if (data.success) {
        setCalendars(data.data);
        console.log('Calendars loaded:', data.data);
      } else {
        setError('Failed to load calendars');
      }
    } catch (error) {
      console.error('Error fetching calendars:', error);
      setError('Error connecting to backend');
    } finally {
      setLoading(false);
    }
  };

  const fetchEvents = async () => {
    try {
      const response = await fetch('http://localhost:8000/events');
      const data = await response.json();
      
      if (data.success) {
        // Remove duplicate events using utility function
        const uniqueEvents = removeDuplicateEvents(data.data);
        
        // Debug: Log all events before and after deduplication
        console.log('🔍 EVENTS DEBUG - Before deduplication:', data.data);
        console.log('🔍 EVENTS DEBUG - After deduplication:', uniqueEvents);
        console.log('🔍 EVENTS DEBUG - Duplicates found:', data.data.length - uniqueEvents.length);
        
        setEvents(uniqueEvents);
        console.log('Events loaded:', uniqueEvents);
      } else {
        setError('Failed to load events');
      }
    } catch (error) {
      console.error('Error loading events:', error);
      setError('Error loading events');
    }
  };

  // Function to manually refresh events from server
  const refreshEvents = async () => {
    try {
      console.log('Refreshing events from server...');
      // Clear notified events when refreshing to allow reminders to show again
      notifiedEvents.clear();
      console.log('Cleared notified events for fresh reminder check');
      await fetchEvents();
      alert('Events refreshed from server!');
    } catch (error) {
      console.error('Error refreshing events:', error);
      alert('Failed to refresh events');
    }
  };

  // Function to clear local storage and refresh from server
  const clearLocalStorage = async () => {
    try {
      console.log('Clearing local storage...');
      
      // Clear the events from state
      setEvents([]);
      
      // Make a request to clear local storage on backend
      const response = await fetch('http://localhost:8000/events/clear-local', {
        method: 'POST'
      });
      
      if (response.ok) {
        alert('Local storage cleared! Now refreshing events from server...');
        // Refresh events from server
        await refreshEvents();
      } else {
        alert('Failed to clear local storage');
      }
    } catch (error) {
      console.error('Error clearing local storage:', error);
      alert('Error clearing local storage');
    }
  };

  // Function to force sync from server (completely fresh)
  const forceSyncFromServer = async () => {
    try {
      console.log('Force syncing from server...');
      
      // Clear notified events for fresh reminder check
      notifiedEvents.clear();
      console.log('Cleared notified events for fresh reminder check');
      
      // Clear local storage first
      await clearLocalStorage();
      
      // Then sync with CalDAV to get fresh events
      await syncCalDAV();
      
      alert('Force sync completed! Events are now fresh from the server.');
    } catch (error) {
      console.error('Error during force sync:', error);
      alert('Error during force sync');
    }
  };

  // Function to delete an event
  const deleteEvent = async (eventId) => {
    try {
      console.log('Deleting event:', eventId);
      
      const response = await fetch(`http://localhost:8000/events/${eventId}`, {
        method: 'DELETE',
      });
      
      if (response.ok) {
        // Remove event from local state - match by either ID or UID
        setEvents(prevEvents => prevEvents.filter(event => 
          event.id !== eventId && event.uid !== eventId
        ));
        console.log('Event deleted successfully');
        alert('Event deleted successfully!');
      } else {
        const errorData = await response.json();
        console.error('Failed to delete event:', errorData);
        alert(`Failed to delete event: ${errorData.message || 'Unknown error'}`);
      }
    } catch (error) {
      console.error('Error deleting event:', error);
      alert(`Error deleting event: ${error.message}`);
    }
  };

  // Function to confirm event deletion
  const confirmDeleteEvent = (event) => {
    const message = `Are you sure you want to delete "${event.title}"?\n\nThis action cannot be undone.`;
    if (window.confirm(message)) {
      // Use UID instead of ID for CalDAV server compatibility
      deleteEvent(event.uid || event.id);
    }
  };

  const syncCalDAV = async () => {
    try {
      console.log('Syncing with CalDAV...');
      
      // First check CalDAV status
      const statusResponse = await fetch('http://localhost:8000/caldav/status');
      const statusData = await statusResponse.json();
      console.log('CalDAV Status:', statusData);
      
      // Then try to discover calendars
      const discoverResponse = await fetch('http://localhost:8000/caldav/discover', {
        method: 'POST'
      });
      const discoverData = await discoverResponse.json();
      console.log('CalDAV Discovery:', discoverData);
      
      if (discoverData.success) {
        // Update calendars with discovered CalDAV calendars
        if (discoverData.data && discoverData.data.calendars) {
          const caldavCalendars = discoverData.data.calendars.map((cal, index) => ({
            id: index + 1,
            name: cal.name || `Google Calendar ${index + 1}`,
            color: cal.color || '#4285f4',
            url: cal.url,
            userId: 1,
            isActive: true,
            syncToken: `caldav-sync-${Date.now()}`,
            createdAt: new Date().toISOString(),
            updatedAt: new Date().toISOString()
          }));
          
          setCalendars(caldavCalendars);
          console.log('Updated calendars with CalDAV data:', caldavCalendars);
          
          // Try to fetch events from the first calendar
          if (caldavCalendars.length > 0) {
            const firstCalendar = caldavCalendars[0];
            try {
                             const eventsResponse = await fetch(`http://localhost:8000/caldav/events/${firstCalendar.id}`);
               const eventsData = await eventsResponse.json();
               if (eventsData.success) {
                 // Remove duplicate events using utility function
                 const uniqueEvents = removeDuplicateEvents(eventsData.data);
                 
                 // Debug: Log CalDAV events before and after deduplication
                 console.log('🔍 CALDAV EVENTS DEBUG - Before deduplication:', eventsData.data);
                 console.log('🔍 CALDAV EVENTS DEBUG - After deduplication:', uniqueEvents);
                 console.log('🔍 CALDAV EVENTS DEBUG - Duplicates found:', eventsData.data.length - uniqueEvents.length);
                 
                 setEvents(uniqueEvents);
                 console.log('Events loaded from CalDAV:', uniqueEvents);
               }
            } catch (eventsError) {
              console.log('Could not fetch events from CalDAV, using mock data');
            }
          }
        }
      } else {
        console.error('CalDAV discovery failed:', discoverData.message);
      }
    } catch (error) {
      console.error('Error syncing with CalDAV:', error);
    }
  };

  const handleAddEvent = async () => {
    try {
             // Create event object in the format expected by backend
       const eventData = {
         title: newEvent.summary,
         description: newEvent.description,
         location: newEvent.location,
         start_time: newEvent.all_day 
           ? `${newEvent.start_date}T00:00:00` 
           : `${newEvent.start_date}T${newEvent.start_time}:00`,
         end_time: newEvent.all_day 
           ? `${newEvent.end_date}T23:59:59` 
           : `${newEvent.end_date}T${newEvent.end_time}:00`,
         all_day: newEvent.all_day,
         calendar_id: calendars.length > 0 ? calendars[0].id : 1,
         // Convert reminder to CalDAV VALARM format
         valarm: newEvent.reminder.enabled ? {
           trigger: generateVALARMTrigger(newEvent.reminder),
           action: 'DISPLAY',
           description: `Reminder: ${newEvent.summary}`
         } : null
       };

      console.log('Sending event data:', eventData);

      const response = await fetch('http://localhost:8000/events', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(eventData),
      });

      const data = await response.json();
      console.log('Backend response:', data);
      
                    if (data.success) {
         console.log('Event created successfully:', data.data);
         console.log('Event reminder data:', data.data.reminder);
         console.log('Event VALARM data:', data.data.valarm);
         
         // Add the new event to the current events list, ensuring no duplicates
         setEvents(prevEvents => {
           const newEvents = [...prevEvents, data.data];
           const finalEvents = removeDuplicateEvents(newEvents);
           console.log('Updated events list:', finalEvents);
           console.log('Events with reminders:', finalEvents.filter(e => (e.reminder && e.reminder.enabled) || e.valarm));
           
                       // Check if this new event has a reminder that should trigger immediately
            if (data.data.reminder && data.data.reminder.enabled || data.data.valarm) {
              console.log('New event has reminder, checking if it should trigger immediately...');
              // Use setTimeout to ensure the event is fully added to state before checking
              setTimeout(() => {
                // Force immediate check for this specific event
                const now = new Date();
                const eventStart = new Date(data.data.start_time);
                const eventEnd = new Date(data.data.end_time);
                
                let reminderTime;
                if (data.data.valarm) {
                  reminderTime = calculateVALARMTime(data.data.valarm, eventStart, eventEnd);
                } else if (data.data.reminder && data.data.reminder.enabled) {
                  reminderTime = calculateLegacyReminderTime(data.data.reminder, eventStart, eventEnd);
                }
                
                if (reminderTime) {
                  const timeDiff = now.getTime() - reminderTime.getTime();
                  console.log(`Immediate check for new event "${data.data.title}": reminder time ${reminderTime.toLocaleString()}, now ${now.toLocaleString()}, diff ${timeDiff}ms`);
                  
                  // Only trigger immediately if reminder is overdue (timeDiff > 0)
                  if (timeDiff > 0) {
                    console.log(`🚨 IMMEDIATE TRIGGER for new event "${data.data.title}" - OVERDUE by ${Math.floor(timeDiff / 60000)} minutes`);
                    showReminderNotification(data.data);
                    notifiedEvents.add(data.data.id);
                  } else {
                    console.log(`New event reminder not overdue, will be checked in regular interval`);
                  }
                }
              }, 100);
            }
           
           return finalEvents;
         });
         // Close modal and reset form
         setShowAddEventModal(false);
                  setNewEvent({
            summary: '',
            location: '',
            description: '',
            start_date: '',
            start_time: '',
            end_date: '',
            end_time: '',
            all_day: false,
            reminder: {
              enabled: false,
              type: 'message',
              time: 15,
              unit: 'minutes',
              relativeTo: 'start'
            }
          });
         // Show success message
         alert('Event created successfully!');
       } else {
        console.error('Failed to create event:', data.message);
        alert(`Failed to create event: ${data.message}`);
      }
    } catch (error) {
      console.error('Error creating event:', error);
      alert(`Error creating event: ${error.message}`);
    }
  };

  const handleInputChange = (field, value) => {
    setNewEvent(prev => {
      if (field === 'reminder') {
        return {
          ...prev,
          reminder: value
        };
      }
      return {
        ...prev,
        [field]: value
      };
    });
  };

  // Track which events have already been notified to prevent duplicates
  // This needs to persist across multiple checkReminders calls
  const [notifiedEvents] = useState(new Set());
  
  // Check for reminders and show notifications
  const checkReminders = () => {
    const now = new Date();
    console.log('Checking reminders at:', now.toLocaleString());
    
    console.log(`Total events to check: ${events.length}`);
    console.log(`Already notified events:`, Array.from(notifiedEvents));
    
    events.forEach(event => {
      console.log(`Checking event: "${event.title}" - Has reminder: ${!!(event.reminder && event.reminder.enabled)}, Has VALARM: ${!!event.valarm}`);
      
      // Check for both old reminder format and new VALARM format
      const hasReminder = (event.reminder && event.reminder.enabled) || event.valarm;
      
      if (hasReminder) {
        // Skip if we've already notified for this event recently
        if (notifiedEvents.has(event.id)) {
          console.log(`Skipping ${event.title} - already notified`);
          return;
        }
        
        console.log(`Checking reminder for event: ${event.title}`);
        console.log(`Event reminder data:`, event.reminder);
        console.log(`Event VALARM data:`, event.valarm);
        
        const eventStart = new Date(event.start_time);
        const eventEnd = new Date(event.end_time);
        
        // Calculate when reminder should trigger
        let reminderTime;
        
        if (event.valarm) {
          // Use CalDAV VALARM data
          console.log(`Using VALARM data for ${event.title}`);
          reminderTime = calculateVALARMTime(event.valarm, eventStart, eventEnd);
        } else if (event.reminder && event.reminder.enabled) {
          // Use old reminder format (for backward compatibility)
          console.log(`Using legacy reminder data for ${event.title}`);
          reminderTime = calculateLegacyReminderTime(event.reminder, eventStart, eventEnd);
        }
        
        if (!reminderTime) {
          console.log(`No reminder time calculated for ${event.title}`);
          return;
        }
        
        console.log(`Event: ${event.title}, Reminder time: ${reminderTime.toLocaleString()}, Now: ${now.toLocaleString()}`);
        
        // Check if reminder should trigger now
        const timeDiff = now.getTime() - reminderTime.getTime();
        const minutesDiff = Math.floor(Math.abs(timeDiff) / 60000);
        
        if (timeDiff > 0) {
          console.log(`🚨 REMINDER OVERDUE: ${event.title} - reminder was due ${minutesDiff} minutes ago!`);
        } else if (timeDiff >= -300000) {
          console.log(`⏰ Reminder due soon: ${event.title} - will trigger in ${minutesDiff} minutes`);
        } else {
          console.log(`⏳ Reminder not due yet: ${event.title} - will trigger in ${minutesDiff} minutes`);
        }
        
        // Trigger reminder if:
        // 1. Reminder is overdue (timeDiff > 0) - trigger immediately
        // 2. Reminder is due within 5 minutes (timeDiff <= 300000) - trigger now
        if (timeDiff > 0 || timeDiff >= -300000) {
          console.log(`Triggering reminder for: ${event.title} - ${timeDiff > 0 ? 'OVERDUE' : 'due soon'}`);
          showReminderNotification(event);
          // Mark this event as notified to prevent duplicate notifications
          notifiedEvents.add(event.id);
          console.log(`Marked ${event.title} as notified. Total notified: ${notifiedEvents.size}`);
        } else {
          console.log(`Reminder for ${event.title} not due yet. Will trigger in ${minutesDiff} minutes.`);
        }
      } else {
        console.log(`Event "${event.title}" has no reminder configured`);
      }
    });
  };

  // Show reminder notification
  const showReminderNotification = (event) => {
    // Check if browser supports notifications
    if (!('Notification' in window)) {
      // Fallback: show alert
      alert(`Reminder: ${event.title} is starting soon!`);
      return;
    }

    // Request permission if not granted
    if (Notification.permission === 'default') {
      Notification.requestPermission().then(permission => {
        if (permission === 'granted') {
          createNotification(event);
        }
      });
    } else if (Notification.permission === 'granted') {
      createNotification(event);
    }
  };

  // Test function to show a notification immediately
  const testNotification = () => {
    console.log('Testing notification...');
    
    // Check if browser supports notifications
    if (!('Notification' in window)) {
      alert('This browser does not support notifications');
      return;
    }

    console.log('Current notification permission:', Notification.permission);
    console.log('Notification in window:', 'Notification' in window);

    // Request permission if not granted
    if (Notification.permission === 'default') {
      console.log('Requesting notification permission...');
      Notification.requestPermission().then(permission => {
        console.log('Permission result:', permission);
        if (permission === 'granted') {
          showTestNotification();
        } else {
          alert('Notification permission denied');
        }
      });
    } else if (Notification.permission === 'granted') {
      console.log('Permission already granted, showing test notification...');
      showTestNotification();
    } else {
      console.log('Permission denied, showing alert...');
      alert('Notification permission denied. Please enable notifications in your browser settings.');
    }
  };

  const showTestNotification = () => {
    const testEvent = {
      id: 'test',
      title: 'Test Reminder',
      description: 'This is a test notification to verify the reminder system is working!',
      start_time: new Date().toISOString(),
      end_time: new Date(Date.now() + 3600000).toISOString(),
      location: 'Test Location'
    };
    
    createNotification(testEvent);
  };

  // Create a test event with reminder for testing the reminder system
  const createTestEventWithReminder = () => {
    const now = new Date();
    const startTime = new Date(now.getTime() + 2 * 60 * 1000); // 2 minutes from now
    const endTime = new Date(now.getTime() + 3 * 60 * 1000);   // 3 minutes from now
    
    const testEvent = {
      id: `test-${Date.now()}`,
      title: 'Test Event with Reminder',
      description: 'This event has a reminder set to 1 minute before start',
      start_time: startTime.toISOString(),
      end_time: endTime.toISOString(),
      location: 'Test Location',
      reminder: {
        enabled: true,
        type: 'message',
        time: 1,
        unit: 'minutes',
        relativeTo: 'start'
      }
    };
    
    // Check if test event already exists to prevent duplicates
    setEvents(prevEvents => {
      const existingTestEvent = prevEvents.find(e => e.title === 'Test Event with Reminder');
      if (existingTestEvent) {
        alert('Test event already exists! Check the console for details.');
        console.log('Existing test event:', existingTestEvent);
        return prevEvents;
      }
      
      const newEvents = [...prevEvents, testEvent];
      console.log('Test event created:', testEvent);
      console.log('Reminder will trigger at:', new Date(startTime.getTime() - 60000).toLocaleString());
      
      alert(`Test event created! Reminder will trigger in 1 minute. Check the console for details.`);
      return newEvents;
    });
  };

    // Create and show the notification
  const createNotification = (event) => {
    try {
      const eventStart = new Date(event.start_time);
      const eventEnd = new Date(event.end_time);
      
      console.log('Creating notification for event:', event.title);
      
      // Try browser notification first
      if ('Notification' in window && Notification.permission === 'granted') {
        console.log('Notification permission granted, creating browser notification...');
        
        const notification = new Notification(`Reminder: ${event.title}`, {
          body: `${event.description || 'No description'}\n\nStart: ${eventStart.toLocaleString()}\nEnd: ${eventEnd.toLocaleString()}\nLocation: ${event.location || 'No location'}`,
          icon: '/favicon.ico',
          tag: `event-${event.id}`,
          requireInteraction: true,
          silent: false // Ensure sound plays
        });

        console.log('Browser notification created successfully');

        notification.onclick = () => {
          console.log('Notification clicked, focusing window...');
          window.focus();
          notification.close();
        };

        notification.onerror = (error) => {
          console.error('Notification error:', error);
          // Fallback to alert if notification fails
          showAlertReminder(event);
        };

        // Also show alert as backup to ensure user sees it
        console.log('Showing backup alert...');
        showAlertReminder(event);

        setTimeout(() => {
          notification.close();
        }, 10000);
      } else {
        // Fallback to alert if notifications not available
        console.log('Notifications not available, showing alert...');
        showAlertReminder(event);
      }
       
     } catch (error) {
       console.error('Error creating notification:', error);
       // Fallback to alert
       showAlertReminder(event);
     }
   };

     // Fallback reminder using browser alert
  const showAlertReminder = (event) => {
    const eventStart = new Date(event.start_time);
    const eventEnd = new Date(event.end_time);
    
    const message = `🔔 REMINDER: ${event.title}\n\n` +
                   `${event.description || 'No description'}\n\n` +
                   `Start: ${eventStart.toLocaleString()}\n` +
                   `End: ${eventEnd.toLocaleString()}\n` +
                   `Location: ${event.location || 'No location'}`;
    
    alert(message);
    console.log('Alert reminder shown for:', event.title);
  };

  // Check notification status and provide troubleshooting info
  const checkNotificationStatus = () => {
    console.log('=== NOTIFICATION STATUS CHECK ===');
    console.log('Browser supports notifications:', 'Notification' in window);
    console.log('Current permission:', Notification.permission);
    console.log('User agent:', navigator.userAgent);
    console.log('Platform:', navigator.platform);
    console.log('Do not disturb:', navigator.doNotTrack);
    
    let statusMessage = '=== NOTIFICATION STATUS ===\n\n';
    statusMessage += `Browser supports notifications: ${'Notification' in window ? 'YES' : 'NO'}\n`;
    statusMessage += `Current permission: ${Notification.permission}\n`;
    statusMessage += `Platform: ${navigator.platform}\n`;
    statusMessage += `User Agent: ${navigator.userAgent}\n\n`;
    
    if (!('Notification' in window)) {
      statusMessage += '❌ This browser does not support notifications\n';
    } else if (Notification.permission === 'denied') {
      statusMessage += '❌ Notifications are blocked by browser\n';
      statusMessage += '💡 To fix: Click the lock/info icon in address bar and allow notifications\n';
    } else if (Notification.permission === 'default') {
      statusMessage += '⚠️ Notification permission not yet requested\n';
      statusMessage += '💡 Click "Test Notification" to request permission\n';
    } else if (Notification.permission === 'granted') {
      statusMessage += '✅ Notifications are enabled!\n';
      statusMessage += '💡 If you still don\'t see them, check:\n';
      statusMessage += '   - Browser focus (notifications may be hidden)\n';
      statusMessage += '   - System notification settings\n';
      statusMessage += '   - Do Not Disturb mode\n';
    }
    
    alert(statusMessage);
    console.log('Status check complete');
  };

  const changeView = (viewType) => {
    const view = views.find(v => v.type === viewType);
    if (view) {
      setCurrentView(view);
    }
  };

  const goToPrevious = () => {
    const newDate = new Date(currentDate);
    switch (currentView.type) {
      case 'day':
        newDate.setDate(newDate.getDate() - 1);
        break;
      case 'week':
        newDate.setDate(newDate.getDate() - 7);
        break;
      case 'month':
        newDate.setMonth(newDate.getMonth() - 1);
        break;
      default:
        newDate.setDate(newDate.getDate() - 1);
    }
    setCurrentDate(newDate);
  };

  const goToNext = () => {
    const newDate = new Date(currentDate);
    switch (currentView.type) {
      case 'day':
        newDate.setDate(newDate.getDate() + 1);
        break;
      case 'week':
        newDate.setDate(newDate.getDate() + 7);
        break;
      case 'month':
        newDate.setMonth(newDate.getMonth() + 1);
        break;
      default:
        newDate.setDate(newDate.getDate() + 1);
    }
    setCurrentDate(newDate);
  };

  const goToToday = () => {
    setCurrentDate(new Date());
  };

  // Helper functions for calendar views
  const getWeekDays = () => {
    const days = [];
    const startOfWeek = new Date(currentDate);
    startOfWeek.setDate(startOfWeek.getDate() - startOfWeek.getDay());
    
    for (let i = 0; i < 7; i++) {
      const day = new Date(startOfWeek);
      day.setDate(startOfWeek.getDate() + i);
      days.push(day);
    }
    return days;
  };

  const getMonthDays = () => {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const startDate = new Date(firstDay);
    startDate.setDate(startDate.getDate() - firstDay.getDay());
    
    const days = [];
    const current = new Date(startDate);
    
    while (current <= lastDay || current.getDay() !== 0) {
      days.push(new Date(current));
      current.setDate(current.getDate() + 1);
    }
    
    return days;
  };

  const getEventsForDate = (date) => {
    return events.filter(event => {
      const eventDate = new Date(event.start_time);
      return eventDate.toDateString() === date.toDateString();
    });
  };

  const getEventsForTimeSlot = (date, hour) => {
    return events.filter(event => {
      const eventDate = new Date(event.start_time);
      return eventDate.toDateString() === date.toDateString() && 
             eventDate.getHours() === hour;
    });
  };

  const formatTime = (hour) => {
    return hour === 0 ? '12 AM' : hour === 12 ? '12 PM' : hour > 12 ? `${hour - 12} PM` : `${hour} AM`;
  };

  // Generate CalDAV VALARM trigger string (e.g., -PT15M for 15 minutes before)
  const generateVALARMTrigger = (reminder) => {
    const { time, unit, relativeTo } = reminder;
    
    // Convert to ISO 8601 duration format
    let duration = '';
    
    switch (unit) {
      case 'minutes':
        duration = `PT${time}M`;
        break;
      case 'hours':
        duration = `PT${time}H`;
        break;
      case 'days':
        duration = `P${time}D`;
        break;
      case 'minutes_after':
        duration = `PT${time}M`;
        break;
      case 'hours_after':
        duration = `PT${time}H`;
        break;
      case 'days_after':
        duration = `P${time}D`;
        break;
      case 'on_time':
        duration = 'PT0M'; // At exact time
        break;
      case 'on_date':
        duration = 'PT9H'; // 9 AM on the date
        break;
      default:
        duration = `PT${time}M`;
    }
    
    // Add minus sign for "before" triggers
    if (relativeTo === 'start' && !unit.includes('after') && unit !== 'on_time' && unit !== 'on_date') {
      duration = `-${duration}`;
    }
    
    return duration;
  };

  // Calculate reminder time from CalDAV VALARM data
  const calculateVALARMTime = (valarm, eventStart, eventEnd) => {
    if (!valarm.trigger) return null;
    
    const trigger = valarm.trigger;
    let reminderTime = new Date(eventStart); // Default to start time
    
    console.log(`Calculating VALARM time for trigger: "${trigger}"`);
    console.log(`Event start: ${eventStart.toLocaleString()}`);
    
    // Parse ISO 8601 duration (e.g., -PT15M, PT1H, P1D)
    if (trigger.startsWith('-')) {
      // Negative duration means "before"
      const duration = trigger.substring(1);
      console.log(`Negative duration detected: "${duration}", calculating reminder before event start`);
      reminderTime = subtractDuration(eventStart, duration);
    } else if (trigger.startsWith('PT')) {
      // Positive duration means "after"
      const duration = trigger;
      console.log(`Positive duration detected: "${duration}", calculating reminder after event start`);
      reminderTime = addDuration(eventStart, duration);
    } else if (trigger.startsWith('P') && !trigger.includes('T')) {
      // Days only (e.g., P1D)
      const days = parseInt(trigger.substring(1));
      console.log(`Days duration detected: ${days} days before event start`);
      reminderTime.setDate(reminderTime.getDate() - days);
    }
    
    console.log(`Calculated reminder time: ${reminderTime.toLocaleString()}`);
    return reminderTime;
  };

  // Calculate reminder time from legacy reminder format
  const calculateLegacyReminderTime = (reminder, eventStart, eventEnd) => {
    let reminderTime;
    if (reminder.relativeTo === 'start') {
      reminderTime = new Date(eventStart);
    } else {
      reminderTime = new Date(eventEnd);
    }
    
    console.log(`Calculating legacy reminder time for: ${reminder.time} ${reminder.unit} ${reminder.relativeTo === 'start' ? 'before start' : 'before end'}`);
    console.log(`Base time: ${reminderTime.toLocaleString()}`);
    
    // Adjust reminder time based on unit
    switch (reminder.unit) {
      case 'minutes':
        reminderTime.setMinutes(reminderTime.getMinutes() - reminder.time);
        console.log(`Subtracted ${reminder.time} minutes`);
        break;
      case 'hours':
        reminderTime.setHours(reminderTime.getHours() - reminder.time);
        console.log(`Subtracted ${reminder.time} hours`);
        break;
      case 'days':
        reminderTime.setDate(reminderTime.getDate() - reminder.time);
        console.log(`Subtracted ${reminder.time} days`);
        break;
      case 'minutes_after':
        reminderTime.setMinutes(reminderTime.getMinutes() + reminder.time);
        console.log(`Added ${reminder.time} minutes`);
        break;
      case 'hours_after':
        reminderTime.setHours(reminderTime.getHours() + reminder.time);
        console.log(`Added ${reminder.time} hours`);
        break;
      case 'days_after':
        reminderTime.setDate(reminderTime.getDate() + reminder.time);
        console.log(`Added ${reminder.time} days`);
        break;
      case 'on_time':
        // Reminder at exact time
        console.log(`Reminder at exact time`);
        break;
      case 'on_date':
        // Reminder on the date (at 9 AM)
        reminderTime.setHours(9, 0, 0, 0);
        console.log(`Reminder at 9 AM on the date`);
        break;
    }
    
    console.log(`Final reminder time: ${reminderTime.toLocaleString()}`);
    return reminderTime;
  };

  // Helper function to subtract duration from a date
  const subtractDuration = (date, duration) => {
    const newDate = new Date(date);
    console.log(`Subtracting duration "${duration}" from date ${date.toLocaleString()}`);
    
    if (duration.includes('H')) {
      const hours = parseInt(duration.replace('PT', '').replace('H', ''));
      console.log(`Subtracting ${hours} hours`);
      newDate.setHours(newDate.getHours() - hours);
    } else if (duration.includes('M')) {
      const minutes = parseInt(duration.replace('PT', '').replace('M', ''));
      console.log(`Subtracting ${minutes} minutes`);
      newDate.setMinutes(newDate.getMinutes() - minutes);
    } else if (duration.includes('D')) {
      const days = parseInt(duration.replace('P', '').replace('D', ''));
      console.log(`Subtracting ${days} days`);
      newDate.setDate(newDate.getDate() - days);
    }
    
    console.log(`Result: ${newDate.toLocaleString()}`);
    return newDate;
  };

  // Helper function to add duration to a date
  const addDuration = (date, duration) => {
    const newDate = new Date(date);
    
    if (duration.includes('H')) {
      const hours = parseInt(duration.replace('PT', '').replace('H', ''));
      newDate.setHours(newDate.getHours() + hours);
    } else if (duration.includes('M')) {
      const minutes = parseInt(duration.replace('PT', '').replace('M', ''));
      newDate.setMinutes(newDate.getMinutes() + minutes);
    } else if (duration.includes('D')) {
      const days = parseInt(duration.replace('PT', '').replace('D', ''));
      newDate.setDate(newDate.getDate() + days);
    }
    
    return newDate;
  };

  // Utility function to remove duplicate events
  const removeDuplicateEvents = (events) => {
    const originalCount = events.length;
    
    // Remove duplicates based on multiple criteria
    const uniqueEvents = events.filter((event, index, self) => {
      // For test events, also check title to prevent duplicates
      if (event.title === 'Test Event with Reminder') {
        return index === self.findIndex(e => 
          e.title === event.title && e.start_time === event.start_time
        );
      }
      
      // For regular events, check multiple criteria to catch duplicates
      const isDuplicate = self.findIndex(e => {
        // Same ID is always a duplicate
        if (e.id === event.id) return true;
        
        // Same title + same start time + same end time is likely a duplicate
        if (e.title === event.title && 
            e.start_time === event.start_time && 
            e.end_time === event.end_time) return true;
        
        // Same title + very close start times (within 1 minute) might be duplicates
        if (e.title === event.title) {
          const time1 = new Date(e.start_time);
          const time2 = new Date(event.start_time);
          const timeDiff = Math.abs(time1.getTime() - time2.getTime());
          if (timeDiff < 60000) return true; // Within 1 minute
        }
        
        return false;
      });
      
      // Keep only the first occurrence
      return index === isDuplicate;
    });
    
    if (originalCount !== uniqueEvents.length) {
      console.warn(`Found ${originalCount - uniqueEvents.length} duplicate events, removed them`);
      console.log('Duplicate events details:', events.filter((event, index, self) => {
        const isDuplicate = self.findIndex(e => {
          if (e.id === event.id && index !== self.findIndex(ev => ev.id === event.id)) return true;
          if (e.title === event.title && 
              e.start_time === event.start_time && 
              e.end_time === event.end_time && 
              index !== self.findIndex(ev => ev.title === event.title && ev.start_time === event.start_time && ev.end_time === event.end_time)) return true;
          return false;
        });
        return isDuplicate !== index;
      }));
    }
    
    return uniqueEvents;
  };

  // Function to find overlapping events for Week View
  const findOverlappingEvents = (event, dayEvents) => {
    const eventStart = new Date(event.start_time);
    const eventEnd = new Date(event.end_time);
    
    return dayEvents.filter(otherEvent => {
      if (otherEvent.id === event.id) return false;
      
      const otherStart = new Date(otherEvent.start_time);
      const otherEnd = new Date(otherEvent.end_time);
      
      // Check if events overlap in time
      return eventStart < otherEnd && eventEnd > otherStart;
    });
  };

     // Function to calculate event position for Week View
   const getWeekEventPosition = (event, group, day) => {
     const eventStart = new Date(event.start_time);
     const eventEnd = new Date(event.end_time);
     const dayStart = new Date(day);
     dayStart.setHours(0, 0, 0, 0);
     
     // Calculate position from start of day (in minutes)
     const startMinutes = (eventStart.getTime() - dayStart.getTime()) / (1000 * 60);
     const durationMinutes = (eventEnd.getTime() - eventStart.getTime()) / (1000 * 60);
     
     // Find the index of this event within its group
     const eventIndex = group.findIndex(e => e.id === event.id);
     const totalOverlapping = group.length;
     
     // Calculate width and left position for overlapping events
     let eventWidth, leftOffset;
     
     if (totalOverlapping > 1) {
       // Multiple overlapping events - divide space equally within the day column
       const singleEventWidth = Math.max(100 / totalOverlapping, 20); // Use percentage, minimum 20%
       eventWidth = `${singleEventWidth}%`;
       leftOffset = `${eventIndex * singleEventWidth}%`;
     } else {
       // Single event - use full width with small margins
       eventWidth = 'calc(100% - 8px)';
       leftOffset = '4px';
     }
     
     return {
       top: `${startMinutes}px`,
       height: `${Math.max(durationMinutes, 20)}px`,
       width: eventWidth,
       left: leftOffset
     };
   };

  if (loading) {
    return (
      <div className="app">
        <div className="loading">Loading calendar...</div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="app">
        <div className="error">Error: {error}</div>
      </div>
    );
  }

  return (
    <div className="app">
      <div className="header">
        <h1>Mithi Calendar</h1>
                                                                                   <div className="header-controls">
              <button onClick={() => setShowAddEventModal(true)} className="add-event-btn">+ Add Event</button>
              <button onClick={syncCalDAV} className="sync-btn">Sync CalDAV</button>
              <button onClick={refreshEvents} className="sync-btn" style={{ marginLeft: '8px' }}>🔄 Refresh Events</button>
              <button onClick={forceSyncFromServer} className="sync-btn" style={{ marginLeft: '8px' }}>🔄 Force Sync</button>
              <button onClick={checkReminders} className="test-reminder-btn" style={{ marginLeft: '8px' }}>🔍 Debug Reminders</button>
              <button onClick={() => { notifiedEvents.clear(); console.log('Cleared notified events'); alert('Notified events cleared!'); }} className="test-reminder-btn" style={{ marginLeft: '8px' }}>🧹 Clear Notified</button>
            </div>
      </div>
      
      <div className="calendar-container">
        <div className="toolbar">
          <div className="view-controls">
            {views.map(view => (
              <button
                key={view.type}
                onClick={() => changeView(view.type)}
                className={`view-btn ${currentView.type === view.type ? 'active' : ''}`}
              >
                {view.icon} {view.label}
              </button>
            ))}
          </div>
          
          <div className="navigation">
            <button onClick={goToPrevious} className="nav-btn">‹</button>
            <button onClick={goToToday} className="today-btn">Today</button>
            <button onClick={goToNext} className="nav-btn">›</button>
          </div>
          
          <div className="current-date">
            {currentView.type === 'day' && currentDate.toLocaleDateString('en-US', { 
              weekday: 'long', 
              year: 'numeric', 
              month: 'long', 
              day: 'numeric' 
            })}
            {currentView.type === 'week' && `${currentDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} - ${new Date(currentDate.getTime() + 6 * 24 * 60 * 60 * 1000).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}`}
            {currentView.type === 'month' && currentDate.toLocaleDateString('en-US', { 
              year: 'numeric', 
              month: 'long' 
            })}
            {currentView.type === 'agenda' && 'Agenda View'}
          </div>
        </div>
        
        <div className="calendar-content">
          {currentView.type === 'day' && (
            <DayView 
              date={currentDate}
              events={events}
              calendars={calendars}
              onDeleteEvent={confirmDeleteEvent}
            />
          )}
          
          {currentView.type === 'week' && (
            <WeekView 
              date={currentDate}
              events={events}
              calendars={calendars}
              onDeleteEvent={confirmDeleteEvent}
            />
          )}
          
          {currentView.type === 'month' && (
            <MonthView 
              date={currentDate}
              events={events}
              calendars={calendars}
            />
          )}
          
          {currentView.type === 'agenda' && (
            <div className="agenda-view">
              <h3>Agenda View</h3>
              {events.length > 0 ? (
                <div className="events-list">
                  {events
                    .sort((a, b) => new Date(a.start_time) - new Date(b.start_time))
                    .map(event => (
                      <div key={event.id} className="event-item">
                        <div className="event-header">
                          <div className="event-info">
                            <div className="event-date">
                              {new Date(event.start_time).toLocaleDateString('en-US', { 
                                month: 'short', 
                                day: 'numeric',
                                weekday: 'short'
                              })}
                            </div>
                            <div className="event-time">
                              {new Date(event.start_time).toLocaleTimeString('en-US', { 
                                hour: '2-digit', 
                                minute: '2-digit' 
                              })}
                            </div>
                            <div className="event-title">{event.title}</div>
                            {event.description && (
                              <div className="event-description">{event.description}</div>
                            )}
                          </div>
                        </div>
                      </div>
                    ))}
                </div>
              ) : (
                <p>No events found in agenda</p>
              )}
            </div>
          )}
        </div>
      </div>

      {/* Add Event Modal */}
      {showAddEventModal && (
        <div className="modal-overlay" onClick={() => setShowAddEventModal(false)}>
          <div className="modal-content" onClick={(e) => e.stopPropagation()}>
            <div className="modal-header">
              <h2>Add Event</h2>
              <button 
                className="modal-close" 
                onClick={() => setShowAddEventModal(false)}
              >
                ×
              </button>
            </div>
            
            <div className="modal-body">
                             <div className="form-group">
                 <label htmlFor="event-summary">Summary</label>
                 <input
                   type="text"
                   id="event-summary"
                   value={newEvent.summary}
                   onChange={(e) => handleInputChange('summary', e.target.value)}
                   className="form-input"
                   placeholder="Event title"
                   autoFocus
                 />
               </div>
               
               <div className="form-group">
                 <label htmlFor="event-location">Location</label>
                 <input
                   type="text"
                   id="event-location"
                   value={newEvent.location}
                   onChange={(e) => handleInputChange('location', e.target.value)}
                   className="form-input"
                   placeholder="Event location"
                 />
               </div>
               
               <div className="form-group">
                 <label htmlFor="event-description">Description</label>
                 <textarea
                   id="event-description"
                   value={newEvent.description}
                   onChange={(e) => handleInputChange('description', e.target.value)}
                   className="form-textarea"
                   placeholder="Event description"
                   rows="3"
                 />
               </div>
               
               <div className="form-group">
                 <label htmlFor="event-start-date">Start Date</label>
                 <div className="datetime-group">
                   <input
                     type="date"
                     id="event-start-date"
                     value={newEvent.start_date}
                     onChange={(e) => handleInputChange('start_date', e.target.value)}
                     className="form-input"
                   />
                   <label htmlFor="event-start-time">Start Time</label>
                   <input
                     type="time"
                     id="event-start-time"
                     value={newEvent.start_time}
                     onChange={(e) => handleInputChange('start_time', e.target.value)}
                     className="form-input"
                     disabled={newEvent.all_day}
                   />
                   <label className="checkbox-label">
                     <input
                       type="checkbox"
                       id="event-all-day"
                       checked={newEvent.all_day}
                       onChange={(e) => handleInputChange('all_day', e.target.checked)}
                     />
                     all-day
                   </label>
                 </div>
               </div>
               
               <div className="form-group">
                 <label htmlFor="event-end-date">End Date</label>
                 <div className="datetime-group">
                   <input
                     type="date"
                     id="event-end-date"
                     value={newEvent.end_date}
                     onChange={(e) => handleInputChange('end_date', e.target.value)}
                     className="form-input"
                   />
                   <label htmlFor="event-end-time">End Time</label>
                   <input
                     type="time"
                     id="event-end-time"
                     value={newEvent.end_time}
                     onChange={(e) => handleInputChange('end_time', e.target.value)}
                     className="form-input"
                     disabled={newEvent.all_day}
                   />
                 </div>
               </div>
               
               <div className="form-group">
                 <label htmlFor="event-reminder">Reminder</label>
                 <div className="reminder-group">
                   <label className="checkbox-label">
                     <input
                       type="checkbox"
                       id="event-reminder-enabled"
                       checked={newEvent.reminder.enabled}
                       onChange={(e) => handleInputChange('reminder', { ...newEvent.reminder, enabled: e.target.checked })}
                     />
                     Enable reminder
                   </label>
                   
                   {newEvent.reminder.enabled && (
                     <div className="reminder-fields">
                       <label htmlFor="event-reminder-type">Type</label>
                       <select
                         id="event-reminder-type"
                         value={newEvent.reminder.type}
                         onChange={(e) => handleInputChange('reminder', { ...newEvent.reminder, type: e.target.value })}
                         className="form-input"
                       >
                         <option value="message">Message</option>
                         <option value="email">Email</option>
                         <option value="notification">Notification</option>
                       </select>
                       
                       <label htmlFor="event-reminder-time">Time</label>
                       <input
                         type="number"
                         id="event-reminder-time"
                         min="1"
                         max="60"
                         value={newEvent.reminder.time}
                         onChange={(e) => handleInputChange('reminder', { ...newEvent.reminder, time: parseInt(e.target.value) || 1 })}
                         className="form-input"
                         style={{ width: '80px' }}
                       />
                       
                       <label htmlFor="event-reminder-unit">Unit</label>
                       <select
                         id="event-reminder-unit"
                         value={newEvent.reminder.unit}
                         onChange={(e) => handleInputChange('reminder', { ...newEvent.reminder, unit: e.target.value })}
                         className="form-input"
                       >
                         <option value="minutes">minutes before</option>
                         <option value="hours">hours before</option>
                         <option value="days">days before</option>
                         <option value="minutes_after">minutes after</option>
                         <option value="hours_after">hours after</option>
                         <option value="days_after">days after</option>
                         <option value="on_time">on time</option>
                         <option value="on_date">on date</option>
                       </select>
                       
                       <label htmlFor="event-reminder-relative">Relative to</label>
                       <select
                         id="event-reminder-relative"
                         value={newEvent.reminder.relativeTo}
                         onChange={(e) => handleInputChange('reminder', { ...newEvent.reminder, relativeTo: e.target.value })}
                         className="form-input"
                       >
                         <option value="start">start</option>
                         <option value="end">end</option>
                       </select>
                       
                       <button
                         type="button"
                         className="btn btn-secondary"
                         style={{ padding: '4px 8px', fontSize: '12px' }}
                         onClick={() => handleInputChange('reminder', { ...newEvent.reminder, enabled: false })}
                       >
                         Remove
                       </button>
                     </div>
                   )}
                 </div>
               </div>
            </div>
            
            <div className="modal-footer">
              <button 
                className="btn btn-secondary" 
                onClick={() => setShowAddEventModal(false)}
              >
                Cancel
              </button>
              <button 
                className="btn btn-primary" 
                onClick={handleAddEvent}
                disabled={!newEvent.summary.trim()}
              >
                Save
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

export default App;