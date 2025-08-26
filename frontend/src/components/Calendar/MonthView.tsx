import React from 'react';
import dayjs from 'dayjs';
import { Calendar, Event } from '../../types/calendar';

interface MonthViewProps {
  date: Date;
  events: Event[];
  calendars: Calendar[];
}

const MonthView: React.FC<MonthViewProps> = ({ date, events, calendars }) => {
  const monthStart = dayjs(date).startOf('month');
  const monthEnd = dayjs(date).endOf('month');
  const startDate = monthStart.startOf('week');
  const endDate = monthEnd.endOf('week');
  
  const days = [];
  let current = startDate;
  
  while (current.isBefore(endDate) || current.isSame(endDate, 'day')) {
    days.push(current);
    current = current.add(1, 'day');
  }

  const getEventsForDate = (date: dayjs.Dayjs) => {
    return events.filter(event => {
      const eventDate = dayjs(event.start_time);
      return eventDate.isSame(date, 'day');
    });
  };

  const getEventStyle = (event: Event) => {
    const calendar = calendars.find(c => c.id === event.calendar_id);
    return {
      backgroundColor: calendar?.color || '#4285f4',
    };
  };

  const isToday = (date: dayjs.Dayjs) => {
    return date.isSame(dayjs(), 'day');
  };

  const isCurrentMonth = (date: dayjs.Dayjs) => {
    return date.isSame(monthStart, 'month');
  };

  const weekDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

  return (
    <div className="h-full overflow-auto bg-white">
      {/* Month header */}
      <div className="sticky top-0 bg-white border-b border-gray-200 z-10 p-4">
        <h1 className="text-2xl font-bold text-gray-900">
          {monthStart.format('MMMM YYYY')}
        </h1>
      </div>

      {/* Calendar grid */}
      <div className="p-4">
        {/* Week day headers */}
        <div className="grid grid-cols-7 gap-px bg-gray-200 mb-1">
          {weekDays.map((day) => (
            <div key={day} className="bg-white p-3 text-center">
              <span className="text-sm font-medium text-gray-700">{day}</span>
            </div>
          ))}
        </div>

        {/* Calendar days */}
        <div className="grid grid-cols-7 gap-px bg-gray-200">
          {days.map((day, index) => {
            const dayEvents = getEventsForDate(day);
            const isTodayDate = isToday(day);
            const isCurrentMonthDay = isCurrentMonth(day);
            
            return (
              <div
                key={index}
                className={`bg-white min-h-[120px] p-2 ${
                  !isCurrentMonthDay ? 'bg-gray-50' : ''
                }`}
              >
                {/* Date number */}
                <div className={`text-right mb-2 ${
                  isTodayDate 
                    ? 'bg-yellow-400 text-white w-6 h-6 rounded-full flex items-center justify-center text-sm font-bold'
                    : isCurrentMonthDay 
                      ? 'text-gray-900' 
                      : 'text-gray-400'
                }`}>
                  {isTodayDate ? '' : day.format('D')}
                </div>
                
                {/* Events */}
                <div className="space-y-1">
                  {dayEvents.slice(0, 3).map((event, eventIndex) => (
                    <div
                      key={event.id}
                      className="p-1 text-xs text-white rounded overflow-hidden cursor-pointer hover:opacity-90 transition-opacity"
                      style={getEventStyle(event)}
                      title={event.title}
                    >
                      <div className="truncate">
                        {event.title}
                      </div>
                    </div>
                  ))}
                  
                  {dayEvents.length > 3 && (
                    <div className="text-xs text-gray-500 text-center">
                      +{dayEvents.length - 3} more
                    </div>
                  )}
                </div>
              </div>
            );
          })}
        </div>
      </div>
    </div>
  );
};

export default MonthView;
