# CalDev Calendar - Angular Frontend

This is the Angular version of the CalDev Calendar application, converted from the original React implementation.

## Features

- **Day View**: Detailed daily calendar with time slots and event positioning
- **Week View**: Weekly calendar with overlapping event handling
- **Month View**: Monthly overview with event indicators
- **Agenda View**: List view of all events sorted by time
- **Event Management**: Create, edit, and delete events
- **CalDAV Integration**: Sync with external calendar servers
- **Reminder System**: Browser notifications for event reminders
- **Responsive Design**: Works on desktop and mobile devices

## Technology Stack

- **Angular 17**: Modern Angular framework with standalone components
- **TypeScript**: Type-safe JavaScript
- **Tailwind CSS**: Utility-first CSS framework
- **Day.js**: Lightweight date manipulation library
- **RxJS**: Reactive programming for async operations

## Project Structure

```
src/
├── app/
│   ├── components/
│   │   ├── day-view/          # Day view component
│   │   ├── week-view/         # Week view component
│   │   └── month-view/        # Month view component
│   ├── pipes/
│   │   └── sort-by-start-time.pipe.ts
│   ├── app.component.ts       # Main application component
│   ├── app.component.html     # Main application template
│   ├── app.component.scss     # Main application styles
│   └── app.routes.ts          # Application routes
├── styles.scss                # Global styles
└── main.ts                    # Application bootstrap
```

## Getting Started

### Prerequisites

- Node.js (v18 or higher)
- npm or yarn
- Angular CLI

### Installation

1. Install dependencies:
```bash
npm install
```

2. Start the development server:
```bash
npm start
```

3. Open your browser and navigate to `http://localhost:4200`

### Building for Production

```bash
npm run build
```

The build artifacts will be stored in the `dist/` directory.

## Key Features Implemented

### Event Positioning
- **Day View**: Events are positioned absolutely based on their start time and duration
- **Week View**: Events are positioned within day columns with proper overlap handling
- **Overlapping Logic**: Events that overlap in time are displayed side-by-side with calculated widths

### Event Styling
- **Gradient Backgrounds**: Beautiful blue gradients for events
- **Calendar Colors**: Events inherit colors from their associated calendars
- **Hover Effects**: Smooth transitions and scaling on hover
- **Delete Buttons**: Contextual delete buttons that appear on hover

### Responsive Design
- **Mobile-First**: Optimized for mobile devices
- **Flexible Layouts**: Grid-based layouts that adapt to screen size
- **Touch-Friendly**: Large touch targets for mobile interaction

## API Integration

The application connects to the same backend API as the React version:

- **Events**: `GET/POST/DELETE /events`
- **Calendars**: `GET /calendars`
- **CalDAV**: `GET/POST /caldav/*`

## Development Notes

### Component Architecture
- **Standalone Components**: All components are standalone for better tree-shaking
- **Type Safety**: Full TypeScript support with proper interfaces
- **Reactive Programming**: Uses RxJS for async operations and state management

### Styling Approach
- **Tailwind CSS**: Utility-first CSS framework
- **SCSS**: Component-specific styles in SCSS files
- **CSS Custom Properties**: Dynamic styling for event colors

### Performance Optimizations
- **TrackBy Functions**: Optimized change detection for lists
- **OnPush Strategy**: Can be implemented for better performance
- **Lazy Loading**: Components can be lazy-loaded if needed

## Migration from React

This Angular version maintains feature parity with the React version:

- ✅ All calendar views (Day, Week, Month, Agenda)
- ✅ Event creation and management
- ✅ CalDAV integration
- ✅ Reminder system
- ✅ Responsive design
- ✅ Event positioning and overlapping logic
- ✅ Delete functionality
- ✅ Gradient styling

## Future Enhancements

- [ ] Event editing functionality
- [ ] Drag and drop event rescheduling
- [ ] Multiple calendar support
- [ ] Event categories and tags
- [ ] Export functionality
- [ ] Offline support with service workers
- [ ] Progressive Web App features

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## License

This project is part of the CalDev Calendar application.
