import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

export interface EmailInvitation {
  to: string[];
  subject: string;
  htmlBody: string;
  textBody: string;
  eventDetails: {
    title: string;
    description?: string;
    location?: string;
    startTime: string;
    endTime: string;
    allDay: boolean;
    organizer: string;
  };
}

@Injectable({
  providedIn: 'root'
})
export class EmailService {
  private apiUrl = 'http://localhost:8000'; // Backend API URL

  constructor(private http: HttpClient) {}

  /**
   * Send calendar invitation emails to attendees
   */
  sendCalendarInvitations(
    attendees: Array<{email: string; name?: string; role: string}>,
    eventDetails: EmailInvitation['eventDetails']
  ): Observable<any> {
    const invitation: EmailInvitation = {
      to: attendees.map(a => a.email),
      subject: `Calendar Invitation: ${eventDetails.title}`,
      htmlBody: this.generateHTMLInvitation(attendees, eventDetails),
      textBody: this.generateTextInvitation(attendees, eventDetails),
      eventDetails
    };

    return this.http.post(`${this.apiUrl}/email`, invitation);
  }

  /**
   * Generate HTML version of the invitation email
   */
  private generateHTMLInvitation(
    attendees: Array<{email: string; name?: string; role: string}>,
    eventDetails: EmailInvitation['eventDetails']
  ): string {
    const startDate = new Date(eventDetails.startTime);
    const endDate = new Date(eventDetails.endTime);
    const timeFormat = eventDetails.allDay ? 'EEEE, MMMM d, y' : 'EEEE, MMMM d, y \'at\' h:mm a';
    
    return `
      <!DOCTYPE html>
      <html>
      <head>
        <meta charset="utf-8">
        <title>Calendar Invitation</title>
        <style>
          body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
          .container { max-width: 600px; margin: 0 auto; padding: 20px; }
          .header { background: #4299e1; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
          .content { background: #f8fafc; padding: 20px; border-radius: 0 0 8px 8px; }
          .event-details { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #4299e1; }
          .button { display: inline-block; padding: 12px 24px; background: #4299e1; color: white; text-decoration: none; border-radius: 6px; margin: 10px 5px; }
          .button.decline { background: #e53e3e; }
          .footer { text-align: center; margin-top: 20px; color: #666; font-size: 14px; }
        </style>
      </head>
      <body>
        <div class="container">
          <div class="header">
            <h1>📅 Calendar Invitation</h1>
          </div>
          
          <div class="content">
            <p>Hello!</p>
            
            <p>You have been invited to attend:</p>
            
            <div class="event-details">
              <h2>${eventDetails.title}</h2>
              ${eventDetails.description ? `<p><strong>Description:</strong> ${eventDetails.description}</p>` : ''}
              ${eventDetails.location ? `<p><strong>Location:</strong> ${eventDetails.location}</p>` : ''}
              <p><strong>Date:</strong> ${startDate.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</p>
              ${!eventDetails.allDay ? `<p><strong>Time:</strong> ${startDate.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })} - ${endDate.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })}</p>` : '<p><strong>All Day Event</strong></p>'}
              <p><strong>Organizer:</strong> ${eventDetails.organizer}</p>
            </div>
            
            <p>Please respond to this invitation:</p>
            
            <div style="text-align: center;">
              <a href="mailto:${eventDetails.organizer}?subject=Accept: ${eventDetails.title}&body=I will attend this event." class="button">✅ Accept</a>
              <a href="mailto:${eventDetails.organizer}?subject=Decline: ${eventDetails.title}&body=I cannot attend this event." class="button decline">❌ Decline</a>
              <a href="mailto:${eventDetails.organizer}?subject=Tentative: ${eventDetails.title}&body=I might attend this event." class="button" style="background: #d69e2e;">🤔 Tentative</a>
            </div>
            
            <div class="footer">
              <p>This invitation was sent from Mithi Calendar</p>
              <p>If you have any questions, please contact the organizer directly.</p>
            </div>
          </div>
        </div>
      </body>
      </html>
    `;
  }

  /**
   * Generate plain text version of the invitation email
   */
  private generateTextInvitation(
    attendees: Array<{email: string; name?: string; role: string}>,
    eventDetails: EmailInvitation['eventDetails']
  ): string {
    const startDate = new Date(eventDetails.startTime);
    const endDate = new Date(eventDetails.endTime);
    
    return `
Calendar Invitation

You have been invited to attend:

${eventDetails.title}
${eventDetails.description ? `Description: ${eventDetails.description}` : ''}
${eventDetails.location ? `Location: ${eventDetails.location}` : ''}
Date: ${startDate.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}
${!eventDetails.allDay ? `Time: ${startDate.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })} - ${endDate.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })}` : 'All Day Event'}
Organizer: ${eventDetails.organizer}

Please respond to this invitation by replying to the organizer.

This invitation was sent from Mithi Calendar.
    `.trim();
  }

  /**
   * Generate iCalendar (.ics) file content for calendar applications
   */
  generateICalendarContent(
    attendees: Array<{email: string; name?: string; role: string}>,
    eventDetails: EmailInvitation['eventDetails']
  ): string {
    const startDate = new Date(eventDetails.startTime);
    const endDate = new Date(eventDetails.endTime);
    const now = new Date();
    
    // Format dates for iCalendar (YYYYMMDDTHHMMSSZ)
    const formatDate = (date: Date) => {
      return date.toISOString().replace(/[-:]/g, '').split('.')[0] + 'Z';
    };

    const attendeeList = attendees.map(a => 
      `ATTENDEE;ROLE=${a.role.toUpperCase()};RSVP=TRUE;CN=${a.name || a.email}:mailto:${a.email}`
    ).join('\r\n');

    return `BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Mithi Calendar//Calendar Invitation//EN
CALSCALE:GREGORIAN
METHOD:REQUEST
BEGIN:VEVENT
UID:${Date.now()}@mithi-calendar.com
DTSTAMP:${formatDate(now)}
DTSTART:${formatDate(startDate)}
DTEND:${formatDate(endDate)}
${eventDetails.allDay ? 'X-MICROSOFT-CDO-ALLDAYEVENT:TRUE' : ''}
SUMMARY:${eventDetails.title}
${eventDetails.description ? `DESCRIPTION:${eventDetails.description.replace(/\n/g, '\\n')}` : ''}
${eventDetails.location ? `LOCATION:${eventDetails.location}` : ''}
ORGANIZER;CN=${eventDetails.organizer}:mailto:${eventDetails.organizer}
${attendeeList}
STATUS:CONFIRMED
SEQUENCE:0
BEGIN:VALARM
TRIGGER:-PT15M
ACTION:DISPLAY
DESCRIPTION:Reminder: ${eventDetails.title}
END:VALARM
END:VEVENT
END:VCALENDAR`;
  }

  /**
   * Download iCalendar file for the event
   */
  downloadICalendar(
    attendees: Array<{email: string; name?: string; role: string}>,
    eventDetails: EmailInvitation['eventDetails']
  ): void {
    const icsContent = this.generateICalendarContent(attendees, eventDetails);
    const blob = new Blob([icsContent], { type: 'text/calendar;charset=utf-8' });
    const url = window.URL.createObjectURL(blob);
    
    const link = document.createElement('a');
    link.href = url;
    link.download = `${eventDetails.title.replace(/[^a-z0-9]/gi, '_').toLowerCase()}_invitation.ics`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    window.URL.revokeObjectURL(url);
  }
}
