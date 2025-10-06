# CalDev Calendar Application - C4 Architecture Diagrams

## 1. System Context Diagram (Level 1)

```mermaid
%%{init: {'theme':'base', 'themeVariables': {'primaryColor': '#4A90E2', 'primaryTextColor': '#FFFFFF', 'primaryBorderColor': '#2B6CB0', 'lineColor': '#2B6CB0', 'backgroundColor': '#FFFFFF', 'tertiaryColor': '#F7FAFC'}}}%%
graph TB
    User["👤 Users<br/>(Calendar Users)"]
    EmailUser["👤 Email Staff<br/>(Mithi Email Users)"]
    
    CalDev["🔄 CalDev Calendar App<br/><br/><b>Features:</b><br/>• Multi-calendar support<br/>• Event management<br/>• Recurring events<br/>• Email integration<br/>• SSO Authentication"]
    
    Roundcube["📧 Roundcube Email<br/><br/>• Email client interface<br/>• Calendar integration<br/>• SSO authentication"]
    
    CalDAV["🗓️ CalDAV Server<br/><br/>• Calendar sync<br/>• Event storage<br/>• RFC 4791 compliance"]
    
    AWS["☁️ AWS ECR<br/><br/>• Container registry<br/>• Image storage"]
    
    %% Connections
    User -->|"Manage calendars<br/>& events"| CalDev
    EmailUser -->|"Email calendar<br/>integration"| Roundcube
    Roundcube -->|"SSO authentication<br/>Calendar linking"| CalDev
    CalDev -->|"Calendar sync<br/>Event management"| CalDAV
    CalDev -->|"Deploy containers"| AWS
```

## 2. Container Diagram (Level 2)

```mermaid
%%{init: {'theme':'base', 'themeVariables': {'primaryColor': '#4A90E2', 'primaryTextColor': '#FFFFFF', 'primaryBorderColor': '#2B6CB0', 'lineColor': '#2B6CB0', 'backgroundColor': '#FFFFFF', 'tertiaryColor': '#F7FAFC'}}}%%
graph TB
    subgraph "Browser"
        Browser["🌐 Web Browser<br/><br/>• Angular SPA<br/>• User interface<br/>• Local storage"]
    end
    
    subgraph "CalDev Application"
        Nginx["🔄 Nginx Proxy<br/><br/>• SSL termination<br/>• Load balancing<br/>• Static files"]
        
        Angular["📱 Angular Frontend<br/><br/><b>Technologies:</b><br/>• Angular 17<br/>• TypeScript<br/>• RxJS<br/>• Tailwind CSS<br/><br/><b>Features:</b><br/>• Component-based UI<br/>• Reactive forms<br/>• State management<br/>• Offline support"]
        
        PHApi["🔧 PHP Backend API<br/><br/><b>Technologies:</b><br/>• PHP 8.0+<br/>• Apache/Nginx<br/>• RESTful API<br/><br/><b>Features:</b><br/>• Event management<br/>• Calendar sync<br/>• Authentication<br/>• Email integration"]
        
        CalDAVClient["📡 CalDAV Client<br/><br/>• RFC 4791 compliance<br/>• Calendar discovery<br/>• Event CRUD<br/>• Recurrence handling"]
    end
    
    subgraph "External Systems"
        RDCMail["📧 Roundcube Mail<br/><br/>• Email client<br/>• SSO provider<br/>• Calendar integration"]
        
        CalDAVSrv["🗓️ CalDAV Server<br/><br/>• Calendar storage<br/>• Event persistence<br/>• Sync protocol"]
        
        SMTP["📤 SMTP Server<br/><br/>• Email delivery<br/>• Event invitations"]
    end
    
    subgraph "Storage"
        JSONData["📄 JSON Files<br/><br/>• Events storage<br/>• Calendar configs<br/>• User preferences<br/>• Color mappings"]
        
        LocalStorage["💾 Browser Storage<br/><br/>• User sessions<br/>• UI preferences<br/>• Calendar colors<br/>• Offline data"]
    end
    
    subgraph "Infrastructure"
        Docker["🐳 Docker Containers<br/><br/>• Container orchestration<br/>• Service isolation<br/>• Environment management"]
        
        AWS["☁️ AWS ECR<br/><br/>• Container registry<br/>• Image deployment<br/>• CI/CD pipeline"]
    end
    
    %% Connections
    Browser <-->|"HTTPS/API calls"| Nginx
    Nginx --> Angular
    Angular <-->|"REST API"| PHApi
    PHApi --> CalDAVClient
    CalDAVClient <-->|"CalDAV protocol"| CalDAVSrv
    
    PHApi <-->|"SSO integration"| RDCMail
    PHApi --> SMTP
    
    PHApi --> JSONData
    Angular --> LocalStorage
    
    Docker -.->|"Deploy"| AWS
    Angular -.-> Docker
    PHApi -.-> Docker
    Nginx -.-> Docker
```

## 3. Component Diagram - Angular Frontend (Level 3)

```mermaid
%%{init: {'theme':'base', 'themeVariables': {'primaryColor': '#4A90E2', 'primaryTextColor': '#FFFFFF', 'primaryBorderColor': '#2B6CB0', 'lineColor': '#2B6CB0', 'backgroundColor': '#FFFFFF', 'tertiaryColor': '#F7FAFC'}}}%%
graph TB
    subgraph "Angular Frontend Components"
        AppComponent["🎯 App Component<br/><br/>• Root component<br/>• Layout management<br/>• Route handling"]
        
        CalendarComponent["📅 Calendar Component<br/><br/><b>Features:</b><br/>• Multiple views (Day/Week/Month)<br/>• Event CRUD operations<br/>• Calendar filtering<br/>• Search functionality<br/>• Agenda view<br/><br/><b>Responsibilities:</b><br/>• Events management<br/>• View switching<br/>• Calendar state<br/>• User interactions"]
        
        DayViewComponent["📊 Day View<br/><br/>• Daily event grid<br/>• Time-based layout<br/>• Event details"]
        
        WeekViewComponent["📈 Week View<br/><br/>• 7-day grid<br/>• Event positioning<br/>• Time slots"]
        
        MonthViewComponent["🗓️ Month View<br/><br/>• Calendar grid<br/>• Multiple weeks<br/>• Event indicators"]
        
        EventDetailModal["📝 Event Detail Modal<br/><br/>• Event creation/editing<br/>• Form validation<br/>• Recurrence settings<br/>• Attendee management<br/>• Calendar selection"]
        
        RecurringDeleteModal["❌ Recurring Delete Modal<br/><br/>• Instance vs series<br/>• Confirmation dialogs<br/>• Delete actions"]
        
        LoginComponent["🔐 Login Component<br/><br/>• Authentication UI<br/>• Credential input<br/>• SSO integration"]
        
        ReminderNotification["🔔 Reminder Component<br/><br/>• Event notifications<br/>• Time-based alerts<br/>• User preferences"]
        
        ExportModal["📤 Export Modal<br/><br/>• Calendar export<br/>• Multiple formats<br/>• Date range selection"]
        
        ImportModal["📥 Import Modal<br/><br/>• Calendar import<br/>• File validation<br/>• Conflict resolution"]
        
        DateNavigation["📆 Date Navigation<br/><br/>• Date selection<br/>• Range controls<br/>• Quick navigation"]
    end
    
    subgraph "Angular Services"
        ApiService["🌐 API Service<br/><br/>• HTTP client<br/>• REST endpoints<br/>• Error handling<br/>• Response mapping"]
        
        AuthService["🔑 Auth Service<br/><br/>• User authentication<br/>• Session management<br/>• SSO integration<br/>• Route guards"]
        
        EmailService["✉️ Email Service<br/><br/>• Event invitations<br/>• SMTP integration<br/>• Template handling"]
        
        ColorRegistryService["🎨 Color Registry<br/><br/>• Calendar colors<br/>• Color mapping<br/>• Consistency<br/>• Customization"]
        
        RecurrenceExpansionService["♻️ Recurrence Service<br/><br/>• RRULE parsing<br/>• Event expansion<br/>• Instance generation<br/>• EXDATE handling"]
    end
    
    subgraph "Angular Guards & Interfaces"
        AuthGuard["🛡️ Auth Guard<br/><br/>• Route protection<br/>• Access control<br/>• Redirect logic"]
        
        CalendarEventInterface["📋 Calendar Event Interface<br/><br/>• Type definitions<br/>• Data contracts<br/>• Validation rules"]
        
        SortPipe["🔀 Sort Pipe<br/><br/>• Event sorting<br/>• Time-based ordering<br/>• Custom comparators"]
    end
    
    %% Component relationships
    AppComponent --> CalendarComponent
    AppComponent --> LoginComponent
    AppComponent --> AuthGuard
    
    CalendarComponent --> DayViewComponent
    CalendarComponent --> WeekViewComponent
    CalendarComponent --> MonthViewComponent
    CalendarComponent --> EventDetailModal
    CalendarComponent --> RecurringDeleteModal
    CalendarComponent --> ReminderNotification
    CalendarComponent --> ExportModal
    CalendarComponent --> ImportModal
    CalendarComponent --> DateNavigation
    
    %% Service dependencies
    CalendarComponent -.-> ApiService
    CalendarComponent -.-> AuthService
    CalendarComponent -.-> EmailService
    CalendarComponent -.-> ColorRegistryService
    CalendarComponent -.-> RecurrenceExpansionService
    
    EventDetailModal -.-> ApiService
    EventDetailModal -.-> EmailService
    EventDetailModal -.-> RecurrenceExpansionService
    
    LoginComponent -.-> AuthService
    
    %% Interfaces and pipes
    CalendarComponent -.-> CalendarEventInterface
    CalendarComponent -.-> SortPipe
```

## 4. Component Diagram - PHP Backend (Level 3)

```mermaid
%%{init: {'theme':'base', 'themeVariables': {'primaryColor': '#FF8D40', 'primaryTextColor': '#FFFFFF', 'primaryBorderColor': '#E67E22', 'lineColor': '#E67E22', 'backgroundColor': '#FFFFFF', 'tertiaryColor': '#FFF8F0'}}}%%
graph TB
    subgraph "PHP Backend Architecture"
        IndexPHP["🚀 index.php<br/><br/><b>API Gateway</b><br/><br/>• REST endpoint router<br/>• Request/response handling<br/>• CORS management<br/>• Authentication<br/>• Error handling<br/>• Request logging"]
        
        CalDAVClient["📡 CalDAV Client<br/><br/><b>Calendar Sync Engine</b><br/><br/>• PROPFIND requests<br/>• REPORT generation<br/>• PUT/DELETE operations<br/>• Calendar discovery<br/>• Event synchronization<br/>• Authentication handling"]
        
        CalendarController["📅 Calendar Controller<br/><br/>• Calendar CRUD<br/>• Calendar discovery<br/>• Calendar management<br/>• Settings handling"]
        
            subgraph "Configuration Layer"
                CalDAVConfig["🗓️ CalDAV Config<br/><br/>• Server URLs<br/>• Authentication<br/>• SSL settings"]
                
                DatabaseConfig["💾 Database Config<br/><br/>• Connection strings<br/>• Query configs<br/>• Migration scripts"]
                
                EmailConfig["✉️ Email Config<br/><br/>• SMTP settings<br/>• Template configs<br/>• Delivery options"]
                
                ServerConfig["⚙️ Server Config<br/><br/>• CORS settings<br/>• Static file handling<br/>• Environment configs"]
            end
        
            subgraph "Storage Layer"
                EventModel["📝 Event Model<br/><br/>• Event schema<br/>• Validation rules<br/>• Business logic<br/>• Data transformations"]
                
                CalendarModel["📋 Calendar Model<br/><br/>• Calendar schema<br/>• Metadata handling<br/>• Configuration<br/>• Relationships"]
                
                JSONStorage["📄 JSON Storage<br/><br/>• events.json<br/>• calendar_colors.json<br/>• calendar_states.json<br/>• deleted_events.json<br/>• sso_tokens.json"]
            end
        
            subgraph "Utility Components"
                AuthHandler["🔐 Auth Handler<br/><br/>• Session management<br/>• SSO integration<br/>• Token validation<br/>• User verification"]
                
                EmailHandler["📧 Email Handler<br/><br/>• Event invitations<br/>• Notification delivery<br/>• Template processing<br/>• SMTP integration"]
                
                RequestLogger["📊 Request Logger<br/><br/>• API call logging<br/>• Error tracking<br/>• Performance metrics<br/>• Debug information"]
            end
        end
    
        %% Core relationships
        IndexPHP --> CalDAVClient
        IndexPHP --> CalendarController
        IndexPHP --> AuthHandler
        IndexPHP --> EmailHandler
        IndexPHP --> RequestLogger
        
        CalDAVClient --> CalDAVConfig
        CalendarController --> EventModel
        CalendarController --> CalendarModel
        
        EventModel --> JSONStorage
        CalendarModel --> JSONStorage
        
        EmailHandler --> EmailConfig
        AuthHandler --> JsonStorage
        IndexPHP --> ServerConfig
    
    %% External connections (implied)
    IndexPHP -.->|"HTTP Responses"| Angular
    IndexPHP -.->|"CalDAV Protocol"| CalDAVSrv
    IndexPHP -.->|"SMTP"| EmailServer
    IndexPHP -.->|"SSO"| Roundcube
```

## 5. Deployment Diagram (Level 4)

```mermaid
%%{init: {'theme':'base', 'themeVariables': {'primaryColor': '#38B349', 'primaryTextColor': '#FFFFFF', 'primaryBorderColor': '#2A8A35', 'lineColor': '#2A8A35', 'backgroundColor': '#FFFFFF', 'tertiaryColor': '#F7FEF7'}}}%%
graph TB
    subgraph "Development Environment"
        DevServer["💻 Development Server<br/><br/>• Docker Desktop<br/>• Hot reload<br/>• Debug tools<br/>• Local databases"]
        
        DevAngular["🔄 Angular Dev Container<br/><br/>Port: 4200<br/>• Live reload<br/>• Source maps<br/>• Development APIs"]
        
        DevBackend["🔧 PHP Backend Container<br/><br/>Port: 8000<br/>• Debug mode<br/>• Error logging<br/>• Development configs"]
        
        DevNginx["🌐 Nginx Dev Proxy<br/><br/>Port: 9443<br/>• SSL termination<br/>• Load balancing<br/>• Development debugging"]
    end
    
    subgraph "Production Environment"
        ProdServer["☁️ Production Server<br/><br/>• AWS EC2<br/>• Ubuntu/CentOS<br/>• Docker runtime<br/>• SSL certificates"]
        
        ProdNginx["🌐 Nginx Production<br/><br/>Port: 9443<br/>• High availability<br/>• Performance tuning<br/>• Security headers<br/>• SSL termination"]
        
        ProdAngular["📱 Angular Production<br/><br/>• Minified builds<br/>• Gzipped assets<br/>• Performance optimized<br/>• Service worker"]
        
        ProdBackend["🔧 PHP Production API<br/><br/>• Caching enabled<br/>• Error suppression<br/>• Production configs<br/>• Logging"]
        
        ProdCalDAV["🗓️ CalDAV Integration<br/><br/>• High availability<br/>• SSL connections<br/>• Load balancing<br/>• Failover support"]
    end
    
    subgraph "Container Registry"
        AWSECR["☁️ AWS ECR<br/><br/>Registry: 248189916187.dkr.ecr.us-west-2<br/><br/><b>Images:</b><br/>• caldev-frontend:latest<br/>• caldev-backend:latest<br/>• nginx-proxy:latest"]
    end
    
    subgraph "CI/CD Pipeline"
        BuildPipeline["🔄 Build Pipeline<br/><br/>• Automated builds<br/>• Testing suites<br/>• Security scans<br/>• Quality gates"]
        
        DeployScripts["🚀 Deployment Scripts<br/><br/>• push-to-ecr.bat<br/>• Docker compose<br/>• Environment configs<br/>• Rollback support"]
    end
    
    subgraph "External Services"
        EmailInfrastructure["📧 Email Infrastructure<br/><br/>• SMTP servers<br/>• Email relay<br/>• Delivery tracking<br/>• Bounce handling"]
        
        CalDAVServers["🗓️ CalDAV Servers<br/><br/>• Calendar storage<br/>• Sync endpoints<br/>• Authentication<br/>• High uptime"]
        
        RoundcubeEmail["📧 Roundcube Email<br/><br/>Port: 8001<br/>• Email client<br/>• SSO integration<br/>• Calendar linking"]
    end
    
    %% Development connections
    DevServer --> DevAngular
    DevServer --> DevBackend
    DevServer --> DevNginx
    
    DevNginx --> DevAngular
    DevNginx --> DevBackend
    
    %% Production connections
    ProdServer --> ProdNginx
    ProdServer --> ProdAngular
    ProdServer --> ProdBackend
    ProdServer --> ProdCalDAV
    
    ProdNginx --> ProdAngular
    ProdNginx --> ProdBackend
    
    %% Container registry
    AWSECR -.->|"Pull images"| ProdAngular
    AWSECR -.->|"Pull images"| ProdBackend
    AWSECR -.->|"Pull images"| ProdNginx
    
    %% CI/CD
    BuildPipeline -.->|"Push images"| AWSECR
    DeployScripts -.->|"Deploy"| ProdServer
    
    %% External services
    ProdBackend -.->|"SMTP"| EmailInfrastructure
    ProdCalDAV -.->|"CalDAV Protocol"| CalDAVServers
    ProdBackend -.->|"SSO/Integration"| RoundcubeEmail
```

## 6. User Interaction Flow Diagram

```mermaid
%%{init: {'theme':'base', 'themeVariables': {'primaryColor': '#9F7AEA', 'primaryTextColor': '#FFFFFF', 'primaryBorderColor': '#805AD5', 'lineColor': '#805AD5', 'backgroundColor': '#FFFFFF', 'tertiaryColor': '#F9F7FF'}}}%%
sequenceDiagram
    participant U as 👤 User
    participant B as 🌐 Browser
    participant N as 🌐 Nginx
    participant A as 📱 Angular
    participant P as 🔧 PHP API
    participant C as 📡 CalDAV Client
    participant S as 🗓️ CalDAV Server
    participant R as 📧 Roundcube
    
    Note over U,R: User Authentication Flow
    U->>B: Access calendar app
    B->>N: HTTPS request
    N->>A: Route to Angular
    A->>B: Check authentication
    Alt Not authenticated
        A->>P: Check SSO status
        P->>R: Validate SSO token
        R->>P: Return auth status
        P->>A: Authentication response
        A->>U: Redirect to login
    End
    
    Note over U,R: Calendar Discovery Flow
    A->>P: GET /calendars/user
    P->>C: Discover calendars
    C->>S: PROPFIND request
    S->>C: Calendar list
    C->>P: Processed calendar data
    P->>A: Calendar response
    A->>U: Display calendar list
    
    Note over U,R: Event Creation Flow
    U->>A: Create new event
    A->>P: POST /events
    P->>C: Create calendar event
    C->>S: PUT event (ICS format)
    S->>C: Event created
    C->>P: Success response
    P->>A: Event confirmation
    A->>U: Event displayed
    
    Note over U,R: Event Editing Flow
    U->>A: Edit event
    A->>P: PUT /events/{id}
    Note over P: Determine edit scope<br/>(this/all occurrences)
    Alt Recurring event - this only
        P->>P: Add EXDATE + individual modification
        P->>C: Update original event
        C->>S: PUT modified event
    Else Recurring event - all
        P->>P: Update original event properties
        P->>C: Update event series
        C->>S: PUT updated series
    Else Non-recurring
        P->>C: Update single event
        C->>S: PUT updated event
    End
    S->>C: Update success
    C->>P: Confirmation
    P->>A: Update response
    A->>U: Event updated
```

## Architecture Summary

### Technology Stack

**Frontend:**
- Angular 17 with TypeScript
- RxJS for reactive programming
- Day.js for date manipulation
- Tailwind CSS for styling
- Angular standalone components
- RxJS observables for state management

**Backend:**
- PHP 8.0+ with Apache/Nginx
- RESTful API architecture
- CalDAV protocol implementation
- JSON file storage
- Session-based authentication
- SSO integration with Roundcube

**Infrastructure:**
- Docker containerization
- AWS ECR for image registry
- Nginx reverse proxy
- SSL/HTTPS termination
- Multi-environment support (dev/prod)

### Key Features

**Calendar Management:**
- Multi-calendar support
- Calendar discovery via CalDAV
- Custom calendar colors
- Calendar filtering and search

**Event Management:**
- Full CRUD operations
- Recurring event support (RRULE)
- Event exceptions (EXDATE)
- All-day event support
- Event reminders

**User Experience:**
- Multiple views (Day/Week/Month/Agenda)
- Responsive design
- Offline capabilities
- Export/import functionality
- Email invitation integration

**Integration:**
- CalDAV protocol compliance
- Roundcube email client integration
- SMTP for email notifications
- SSO authentication support

### Deployment Strategy

**Development:**
- Docker Compose with hot reload
- Local development services
- Debug-enabled configurations
- Live testing environment

**Production:**
- Multi-container deployment
- AWS ECR image management
- Automated CI/CD pipeline
- SSL termination and security
- High availability setup

### Security Features

- CORS configuration
- SSL/HTTPS encryption
- Session-based authentication
- SSO integration
- Input validation
- Error handling and logging
