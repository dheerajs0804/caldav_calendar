import React from 'react';
import { Calendar } from '../../types/calendar';
import { MoreVertical, Search, Eye } from 'lucide-react';

interface CalendarSidebarProps {
  calendars: Calendar[];
  visibleCalendars: Set<number>;
  onCalendarToggle: (calendarId: number, visible: boolean) => void;
  searchQuery: string;
  onSearchChange: (query: string) => void;
}

const CalendarSidebar: React.FC<CalendarSidebarProps> = ({
  calendars,
  visibleCalendars,
  onCalendarToggle,
  searchQuery,
  onSearchChange
}) => {
  const handleCalendarToggle = (calendarId: number) => {
    const isVisible = visibleCalendars.has(calendarId);
    onCalendarToggle(calendarId, !isVisible);
  };

  return (
    <div className="w-80 bg-white border-r border-gray-200 flex flex-col">
      {/* Header */}
      <div className="p-4 border-b border-gray-200">
        <div className="flex items-center justify-between">
          <h2 className="text-lg font-semibold text-gray-900">Calendars</h2>
          <button className="p-1 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100">
            <MoreVertical className="w-4 h-4" />
          </button>
        </div>
      </div>

      {/* Search */}
      <div className="p-4 border-b border-gray-200">
        <div className="relative">
          <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
          <input
            type="text"
            placeholder="Find calendars..."
            value={searchQuery}
            onChange={(e) => onSearchChange(e.target.value)}
            className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
          />
        </div>
      </div>

      {/* Calendar List */}
      <div className="flex-1 overflow-y-auto p-4">
        <div className="space-y-2">
          {calendars.map((calendar) => {
            const isVisible = visibleCalendars.has(calendar.id);
            
            return (
              <div
                key={calendar.id}
                className={`flex items-center justify-between p-3 rounded-lg transition-colors ${
                  isVisible ? 'bg-blue-50 border border-blue-200' : 'hover:bg-gray-50'
                }`}
              >
                <div className="flex items-center space-x-3">
                  {/* Calendar color indicator */}
                  <div
                    className="w-4 h-4 rounded-full"
                    style={{ backgroundColor: calendar.color }}
                  />
                  
                  {/* Calendar name */}
                  <span className={`text-sm font-medium ${
                    isVisible ? 'text-blue-900' : 'text-gray-700'
                  }`}>
                    {calendar.name}
                  </span>
                </div>
                
                <div className="flex items-center space-x-2">
                  {/* Visibility toggle */}
                  <button
                    onClick={() => handleCalendarToggle(calendar.id)}
                    className={`p-1 rounded transition-colors ${
                      isVisible 
                        ? 'text-blue-600 hover:text-blue-700' 
                        : 'text-gray-400 hover:text-gray-600'
                    }`}
                  >
                    <Eye className={`w-4 h-4 ${isVisible ? 'fill-current' : ''}`} />
                  </button>
                  
                  {/* Toggle switch */}
                  <div className="relative">
                    <input
                      type="checkbox"
                      checked={isVisible}
                      onChange={() => handleCalendarToggle(calendar.id)}
                      className="sr-only"
                    />
                    <div
                      className={`w-10 h-6 rounded-full transition-colors ${
                        isVisible ? 'bg-yellow-400' : 'bg-gray-300'
                      }`}
                    >
                      <div
                        className={`w-4 h-4 bg-white rounded-full shadow transform transition-transform ${
                          isVisible ? 'translate-x-5' : 'translate-x-1'
                        }`}
                        style={{ marginTop: '2px' }}
                      />
                    </div>
                  </div>
                </div>
              </div>
            );
          })}
        </div>
        
        {/* Add Calendar Button */}
        <button className="w-full mt-4 p-3 border-2 border-dashed border-gray-300 rounded-lg text-gray-500 hover:text-gray-700 hover:border-gray-400 transition-colors">
          <div className="flex items-center justify-center space-x-2">
            <div className="w-4 h-4 border-2 border-gray-400 rounded-full flex items-center justify-center">
              <span className="text-xs text-gray-400">+</span>
            </div>
            <span className="text-sm font-medium">Add Calendar</span>
          </div>
        </button>
      </div>

      {/* Footer */}
      <div className="p-4 border-t border-gray-200">
        <div className="text-center">
          <button className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium">
            Chat
          </button>
        </div>
      </div>
    </div>
  );
};

export default CalendarSidebar;
