import React, { useState, useEffect } from 'react';
import { Calendar, CalendarView as CalendarViewType } from '../../types/calendar';
import { ChevronLeft, ChevronRight, Plus, Printer, Upload, Download, RotateCcw, Eye, Search } from 'lucide-react';
import dayjs from 'dayjs';
import WeekView from './WeekView';
import MonthView from './MonthView';
import DayView from './DayView.js';
import AgendaView from './AgendaView';
import MiniCalendar from './MiniCalendar';
import CalendarSidebar from './CalendarSidebar';

interface CalendarViewProps {
  calendars: Calendar[];
  events: any[];
  currentView: CalendarViewType;
  currentDate: Date;
  onViewChange: (view: CalendarViewType) => void;
  onDateChange: (date: Date) => void;
  onEventCreate: () => void;
  onCalendarToggle: (calendarId: number, visible: boolean) => void;
}

const CalendarView: React.FC<CalendarViewProps> = ({
  calendars,
  events,
  currentView,
  currentDate,
  onViewChange,
  onDateChange,
  onEventCreate,
  onCalendarToggle
}) => {
  const [searchQuery, setSearchQuery] = useState('');
  const [visibleCalendars, setVisibleCalendars] = useState<Set<number>>(new Set(calendars.map(c => c.id)));

  const views: CalendarViewType[] = [
    { type: 'day', label: 'Day', icon: '📅' },
    { type: 'week', label: 'Week', icon: '📅' },
    { type: 'month', label: 'Month', icon: '📅' },
    { type: 'agenda', label: 'Agenda', icon: '📋' }
  ];

  const handleCalendarToggle = (calendarId: number, visible: boolean) => {
    const newVisible = new Set(visibleCalendars);
    if (visible) {
      newVisible.add(calendarId);
    } else {
      newVisible.delete(calendarId);
    }
    setVisibleCalendars(newVisible);
    onCalendarToggle(calendarId, visible);
  };

  const goToToday = () => {
    onDateChange(new Date());
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
    }
    onDateChange(newDate);
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
    }
    onDateChange(newDate);
  };

  const getDateRangeText = () => {
    switch (currentView.type) {
      case 'day':
        return dayjs(currentDate).format('MMMM D, YYYY');
      case 'week':
        const weekStart = dayjs(currentDate).startOf('week');
        const weekEnd = dayjs(currentDate).endOf('week');
        return `${weekStart.format('MMM D')} - ${weekEnd.format('MMM D YYYY')}`;
      case 'month':
        return dayjs(currentDate).format('MMMM YYYY');
      default:
        return dayjs(currentDate).format('MMMM D, YYYY');
    }
  };

  const renderCalendarContent = () => {
    switch (currentView.type) {
      case 'day':
        return <DayView date={currentDate} events={events} calendars={calendars} />;
      case 'week':
        return <WeekView date={currentDate} events={events} calendars={calendars} />;
      case 'month':
        return <MonthView date={currentDate} events={events} calendars={calendars} />;
      case 'agenda':
        return <AgendaView date={currentDate} events={events} calendars={calendars} />;
      default:
        return <WeekView date={currentDate} events={events} calendars={calendars} />;
    }
  };

  return (
    <div className="flex h-screen bg-gray-50">
      {/* Left Sidebar */}
      <CalendarSidebar
        calendars={calendars}
        visibleCalendars={visibleCalendars}
        onCalendarToggle={handleCalendarToggle}
        searchQuery={searchQuery}
        onSearchChange={setSearchQuery}
      />

      {/* Main Content */}
      <div className="flex-1 flex flex-col">
        {/* Top Header */}
        <div className="bg-white border-b border-gray-200 px-6 py-4">
          {/* Global Actions */}
          <div className="flex justify-between items-center mb-4">
            <div className="flex items-center space-x-4">
              <button
                onClick={onEventCreate}
                className="flex items-center space-x-2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors"
              >
                <Plus className="w-4 h-4" />
                <span>Create</span>
              </button>
              
              <button className="flex items-center space-x-2 text-gray-600 hover:text-gray-800 transition-colors">
                <Printer className="w-4 h-4" />
                <span>Print</span>
              </button>
              
              <button className="flex items-center space-x-2 text-gray-600 hover:text-gray-800 transition-colors">
                <Upload className="w-4 h-4" />
                <span>Import</span>
              </button>
              
              <button className="flex items-center space-x-2 text-gray-600 hover:text-gray-800 transition-colors">
                <Download className="w-4 h-4" />
                <span>Export</span>
              </button>
              
              <button className="flex items-center space-x-2 text-gray-600 hover:text-gray-800 transition-colors">
                <RotateCcw className="w-4 h-4" />
                <span>Reload</span>
              </button>
            </div>
          </div>

          {/* View Toggles and Date Navigation */}
          <div className="flex justify-between items-center">
            {/* View Toggles */}
            <div className="flex space-x-1">
              {views.map((view) => (
                <button
                  key={view.type}
                  onClick={() => onViewChange(view)}
                  className={`px-4 py-2 rounded-lg font-medium transition-colors ${
                    currentView.type === view.type
                      ? 'bg-blue-100 text-blue-700 border border-blue-200'
                      : 'text-gray-600 hover:text-gray-800 hover:bg-gray-100'
                  }`}
                >
                  {view.label}
                </button>
              ))}
            </div>

            {/* Date Navigation */}
            <div className="flex items-center space-x-4">
              <div className="text-center">
                <div className="text-2xl font-semibold text-gray-900">
                  {getDateRangeText()}
                </div>
                <div className="text-sm text-gray-500">
                  Asia/Kolkata
                </div>
              </div>
              
              <div className="flex items-center space-x-2">
                <button
                  onClick={goToPrevious}
                  className="p-2 text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-lg transition-colors"
                >
                  <ChevronLeft className="w-5 h-5" />
                </button>
                
                <button
                  onClick={goToToday}
                  className="px-4 py-2 text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded-lg font-medium transition-colors"
                >
                  Today
                </button>
                
                <button
                  onClick={goToNext}
                  className="p-2 text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-lg transition-colors"
                >
                  <ChevronRight className="w-5 h-5" />
                </button>
              </div>
            </div>
          </div>
        </div>

        {/* Calendar Content */}
        <div className="flex-1 overflow-hidden">
          {renderCalendarContent()}
        </div>
      </div>

      {/* Mini Calendar */}
      <div className="w-80 bg-white border-l border-gray-200 p-4">
        <MiniCalendar
          currentDate={currentDate}
          onDateSelect={onDateChange}
          events={events}
        />
      </div>
    </div>
  );
};

export default CalendarView;
