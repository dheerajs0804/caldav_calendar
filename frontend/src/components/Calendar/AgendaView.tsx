import React from 'react';
import dayjs from 'dayjs';
import { Calendar, Event } from '../../types/calendar';

interface AgendaViewProps {
  date: Date;
  events: Event[];
  calendars: Calendar[];
}

const AgendaView: React.FC<AgendaViewProps> = ({ date, events, calendars }) => {
  const startDate = dayjs(date).startOf('week');
  const endDate = dayjs(date).endOf('week').add(30, 'days'); // Show next 30 days
  
  const filteredEvents = events
    .filter(event => {
      const eventDate = dayjs(event.start_time);
      return eventDate.isBetween(startDate, endDate, 'day', '[]');
    })
    .sort((a, b) => dayjs(a.start_time).valueOf() - dayjs(b.start_time).valueOf());

  const getEventStyle = (event: Event) => {
    const calendar = calendars.find(c => c.id === event.calendar_id);
    return {
      borderLeft: `4px solid ${calendar?.color || '#4285f4'}`,
    };
  };

  const groupEventsByDate = (events: Event[]) => {
    const grouped: { [key: string]: Event[] } = {};
    
    events.forEach(event => {
      const dateKey = dayjs(event.start_time).format('YYYY-MM-DD');
      if (!grouped[dateKey]) {
        grouped[dateKey] = [];
      }
      grouped[dateKey].push(event);
    });
    
    return grouped;
  };

  const groupedEvents = groupEventsByDate(filteredEvents);
  const sortedDates = Object.keys(groupedEvents).sort();

  return (
    <div className="h-full overflow-auto bg-white">
      {/* Header */}
      <div className="sticky top-0 bg-white border-b border-gray-200 z-10 p-4">
        <h1 className="text-2xl font-bold text-gray-900">
          Agenda - {startDate.format('MMM D')} to {endDate.format('MMM D, YYYY')}
        </h1>
      </div>

      {/* Events list */}
      <div className="divide-y divide-gray-200">
        {sortedDates.map((dateKey) => {
          const dateEvents = groupedEvents[dateKey];
          const dateObj = dayjs(dateKey);
          const isToday = dateObj.isSame(dayjs(), 'day');
          
          return (
            <div key={dateKey} className="p-4">
              {/* Date header */}
              <div className={`mb-3 ${
                isToday ? 'text-blue-600 font-semibold' : 'text-gray-700'
              }`}>
                <div className="text-lg font-medium">
                  {dateObj.format('dddd, MMMM D')}
                  {isToday && <span className="ml-2 text-sm">(Today)</span>}
                </div>
                <div className="text-sm text-gray-500">
                  {dateObj.format('YYYY')}
                </div>
              </div>

              {/* Events for this date */}
              <div className="space-y-2">
                {dateEvents.map((event) => (
                  <div
                    key={event.id}
                    className="flex items-start space-x-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer"
                    style={getEventStyle(event)}
                  >
                    {/* Time */}
                    <div className="flex-shrink-0 w-20 text-sm text-gray-600">
                      {event.all_day ? (
                        <span className="text-gray-500">All day</span>
                      ) : (
                        <>
                          <div className="font-medium">
                            {dayjs(event.start_time).format('HH:mm')}
                          </div>
                          <div className="text-xs text-gray-500">
                            {dayjs(event.end_time).format('HH:mm')}
                          </div>
                        </>
                      )}
                    </div>

                    {/* Event details */}
                    <div className="flex-1 min-w-0">
                      <div className="text-sm font-medium text-gray-900 mb-1">
                        {event.title}
                      </div>
                      
                      {event.description && (
                        <div className="text-sm text-gray-600 mb-1 line-clamp-2">
                          {event.description}
                        </div>
                      )}
                      
                      {event.location && (
                        <div className="text-sm text-gray-500 flex items-center">
                          📍 {event.location}
                        </div>
                      )}
                    </div>

                    {/* Calendar indicator */}
                    <div className="flex-shrink-0">
                      {(() => {
                        const calendar = calendars.find(c => c.id === event.calendar_id);
                        return (
                          <div
                            className="w-3 h-3 rounded-full"
                            style={{ backgroundColor: calendar?.color || '#4285f4' }}
                            title={calendar?.name || 'Unknown Calendar'}
                          />
                        );
                      })()}
                    </div>
                  </div>
                ))}
              </div>
            </div>
          );
        })}

        {sortedDates.length === 0 && (
          <div className="p-8 text-center text-gray-500">
            <div className="text-lg font-medium mb-2">No events found</div>
            <div className="text-sm">Try selecting a different date range or add some events.</div>
          </div>
        )}
      </div>
    </div>
  );
};

export default AgendaView;
