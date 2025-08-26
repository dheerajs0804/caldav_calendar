import React, { useState } from 'react';
import dayjs from 'dayjs';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { Event } from '../../types/calendar';

interface MiniCalendarProps {
  currentDate: Date;
  onDateSelect: (date: Date) => void;
  events: Event[];
}

const MiniCalendar: React.FC<MiniCalendarProps> = ({
  currentDate,
  onDateSelect,
  events
}) => {
  const [displayDate, setDisplayDate] = useState(dayjs(currentDate));
  
  const weekDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
  
  const getDaysInMonth = (date: dayjs.Dayjs) => {
    const start = date.startOf('month').startOf('week');
    const end = date.endOf('month').endOf('week');
    const days = [];
    
    let current = start;
    while (current.isBefore(end) || current.isSame(end, 'day')) {
      days.push(current);
      current = current.add(1, 'day');
    }
    
    return days;
  };

  const getEventsForDate = (date: dayjs.Dayjs) => {
    return events.filter(event => {
      const eventDate = dayjs(event.start_time);
      return eventDate.isSame(date, 'day');
    });
  };

  const isCurrentWeek = (date: dayjs.Dayjs) => {
    const currentWeekStart = dayjs(currentDate).startOf('week');
    const currentWeekEnd = dayjs(currentDate).endOf('week');
    return date.isBetween(currentWeekStart, currentWeekEnd, 'day', '[]');
  };

  const isToday = (date: dayjs.Dayjs) => {
    return date.isSame(dayjs(), 'day');
  };

  const isCurrentMonth = (date: dayjs.Dayjs) => {
    return date.isSame(displayDate, 'month');
  };

  const handleDateClick = (date: dayjs.Dayjs) => {
    onDateSelect(date.toDate());
  };

  const goToPreviousMonth = () => {
    setDisplayDate(displayDate.subtract(1, 'month'));
  };

  const goToNextMonth = () => {
    setDisplayDate(displayDate.add(1, 'month'));
  };

  const goToPreviousYear = () => {
    setDisplayDate(displayDate.subtract(1, 'year'));
  };

  const goToNextYear = () => {
    setDisplayDate(displayDate.add(1, 'year'));
  };

  const days = getDaysInMonth(displayDate);

  return (
    <div className="bg-white rounded-lg border border-gray-200 p-4">
      {/* Header */}
      <div className="flex items-center justify-between mb-4">
        <div className="flex items-center space-x-2">
          <button
            onClick={goToPreviousMonth}
            className="p-1 text-gray-400 hover:text-gray-600 rounded hover:bg-gray-100"
          >
            <ChevronLeft className="w-4 h-4" />
          </button>
          
          <div className="text-center">
            <div className="text-lg font-semibold text-gray-900">
              {displayDate.format('MMMM')}
            </div>
            <div className="text-sm text-gray-500">
              {displayDate.format('YYYY')}
            </div>
          </div>
          
          <button
            onClick={goToNextMonth}
            className="p-1 text-gray-400 hover:text-gray-600 rounded hover:bg-gray-100"
          >
            <ChevronRight className="w-4 h-4" />
          </button>
        </div>
        
        <div className="flex items-center space-x-1">
          <button
            onClick={goToPreviousYear}
            className="p-1 text-gray-400 hover:text-gray-600 rounded hover:bg-gray-100"
          >
            <ChevronLeft className="w-3 h-3" />
          </button>
          
          <button
            onClick={goToNextYear}
            className="p-1 text-gray-400 hover:text-gray-600 rounded hover:bg-gray-100"
          >
            <ChevronRight className="w-3 h-3" />
          </button>
        </div>
      </div>

      {/* Week days header */}
      <div className="grid grid-cols-7 gap-1 mb-2">
        <div className="text-center text-xs font-medium text-gray-500">Wk</div>
        {weekDays.map((day) => (
          <div key={day} className="text-center text-xs font-medium text-gray-500">
            {day}
          </div>
        ))}
      </div>

      {/* Calendar grid */}
      <div className="grid grid-cols-7 gap-1">
        {days.map((day, index) => {
          const dayEvents = getEventsForDate(day);
          const isCurrentWeekDay = isCurrentWeek(day);
          const isTodayDate = isToday(day);
          const isCurrentMonthDay = isCurrentMonth(day);
          
          return (
            <div
              key={index}
              className={`
                aspect-square p-1 text-xs font-medium rounded cursor-pointer transition-colors
                ${isCurrentWeekDay ? 'bg-blue-50' : ''}
                ${isTodayDate ? 'bg-yellow-400 text-white' : ''}
                ${!isCurrentMonthDay ? 'text-gray-300' : 'text-gray-700'}
                ${!isTodayDate && isCurrentMonthDay ? 'hover:bg-gray-100' : ''}
              `}
              onClick={() => handleDateClick(day)}
            >
              <div className="flex flex-col h-full">
                <div className="text-center">
                  {day.format('D')}
                </div>
                
                {/* Event indicators */}
                {dayEvents.length > 0 && (
                  <div className="flex-1 flex items-end justify-center">
                    <div className="flex space-x-1">
                      {dayEvents.slice(0, 2).map((event, eventIndex) => (
                        <div
                          key={eventIndex}
                          className="w-1 h-1 rounded-full"
                          style={{ backgroundColor: event.calendar_id ? '#4285f4' : '#34a853' }}
                        />
                      ))}
                      {dayEvents.length > 2 && (
                        <div className="text-xs text-gray-400">+{dayEvents.length - 2}</div>
                      )}
                    </div>
                  </div>
                )}
              </div>
            </div>
          );
        })}
      </div>

      {/* Today button */}
      <div className="mt-4 text-center">
        <button
          onClick={() => {
            const today = new Date();
            setDisplayDate(dayjs(today));
            onDateSelect(today);
          }}
          className="px-4 py-2 text-sm font-medium text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded-lg transition-colors"
        >
          Today
        </button>
      </div>
    </div>
  );
};

export default MiniCalendar;
