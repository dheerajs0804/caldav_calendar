# 🚀 Installation Guide - CalDAV Calendar Application

## 📋 Prerequisites

### **System Requirements**
- **PHP**: 8.0 or higher
- **Node.js**: 18.0 or higher
- **Composer**: Latest version
- **MySQL/PostgreSQL**: For database (optional, uses JSON files by default)

### **Required PHP Extensions**
- `ext-curl`
- `ext-json`
- `ext-pdo`
- `ext-sodium`

## 🔧 Backend Installation

### **1. Install PHP Dependencies**
```bash
cd backend
composer install
```

### **2. Configure Environment**
```bash
# Copy environment template
cp .env.example .env

# Edit configuration
nano .env
```

### **3. Set Up Logging**
```bash
# Create logs directory (if not exists)
mkdir -p logs

# Set permissions
chmod 755 logs
```

### **4. Configure CalDAV Server**
Edit `backend/config/caldav.php`:
```php
return [
    'server_url' => 'https://your-caldav-server.com',
    'username' => 'your-username',
    'password' => 'your-password',
    'environment' => 'development', // or 'production'
];
```

## 🎨 Frontend Installation

### **React Frontend**
```bash
cd frontend
npm install
npm start
```

### **Angular Frontend**
```bash
cd frontend-angular
npm install
ng serve
```

## 🐳 Docker Installation (Alternative)

### **Using Docker Compose**
```bash
# Build and start all services
docker-compose up --build

# Or run in background
docker-compose up -d --build
```

### **Individual Docker Services**
```bash
# Backend only
cd backend
docker build -t caldav-backend .
docker run -p 8000:8000 caldav-backend

# React Frontend
cd frontend
docker build -t caldav-react .
docker run -p 3000:3000 caldav-react

# Angular Frontend
cd frontend-angular
docker build -t caldav-angular .
docker run -p 4200:4200 caldav-angular
```

## 🔐 Environment Configuration

### **Backend Environment Variables**
```bash
# .env file
APP_ENV=development
LOG_LEVEL=debug
CALDAV_SERVER_URL=https://your-caldav-server.com
CALDAV_USERNAME=your-username
CALDAV_PASSWORD=your-password
CALDAV_CALENDAR_PATH=/calendars/

# Google OAuth (optional)
GOOGLE_CLIENT_ID=your-client-id
GOOGLE_CLIENT_SECRET=your-client-secret

# Database (optional)
DB_HOST=localhost
DB_NAME=caldav_calendar
DB_USER=root
DB_PASS=password
```

### **Frontend Environment Variables**
```bash
# React (.env)
REACT_APP_API_URL=http://localhost:8000
REACT_APP_ENVIRONMENT=development

# Angular (src/environments/environment.ts)
export const environment = {
  production: false,
  apiUrl: 'http://localhost:8000'
};
```

## 🚀 Running the Application

### **Development Mode**

#### **1. Start Backend**
```bash
cd backend
php -S localhost:8000 -t .
```

#### **2. Start React Frontend**
```bash
cd frontend
npm start
# Opens http://localhost:3000
```

#### **3. Start Angular Frontend**
```bash
cd frontend-angular
ng serve
# Opens http://localhost:4200
```

### **Production Mode**

#### **1. Build Frontend Applications**
```bash
# React
cd frontend
npm run build

# Angular
cd frontend-angular
ng build --configuration=production
```

#### **2. Configure Web Server**
- **Apache**: Configure virtual host pointing to `backend/` directory
- **Nginx**: Set up reverse proxy to PHP-FPM
- **PHP Built-in**: Use for development only

## 📊 Logging Configuration

### **Backend Logging**
The application uses Monolog for structured logging:

```php
// Log levels by environment
Development: DEBUG, INFO, WARN, ERROR
Production: INFO, WARN, ERROR (no DEBUG)
```

### **Log Files Location**
```
backend/logs/
├── app.log          # General application logs
├── error.log        # Error-level logs only
├── caldav.log       # CalDAV-specific operations
└── security.log     # Security events
```

### **Frontend Logging**
- **Development**: All log levels visible in browser console
- **Production**: Only INFO, WARN, ERROR levels

## 🔍 Troubleshooting

### **Common Issues**

#### **1. Composer Not Found**
```bash
# Install Composer globally
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
```

#### **2. PHP Extensions Missing**
```bash
# Ubuntu/Debian
sudo apt-get install php-curl php-json php-pdo php-sodium

# CentOS/RHEL
sudo yum install php-curl php-json php-pdo php-sodium
```

#### **3. Node.js Version Issues**
```bash
# Install Node Version Manager
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash
nvm install 18
nvm use 18
```

#### **4. CORS Issues**
Check `backend/config/server.php`:
```php
'cors' => [
    'allowed_origins' => [
        'http://localhost:3000',  // React
        'http://localhost:4200',  // Angular
    ],
    'allow_credentials' => true,
],
```

#### **5. CalDAV Connection Issues**
1. Verify server URL and credentials
2. Check network connectivity
3. Review CalDAV server logs
4. Test with CalDAV client tools

### **Debug Mode**
Enable debug logging:
```bash
# Backend
export LOG_LEVEL=debug

# Frontend
export NODE_ENV=development
```

## 📈 Performance Optimization

### **Backend**
- Enable OPcache in PHP
- Use Redis for session storage
- Configure proper log rotation
- Set up database connection pooling

### **Frontend**
- Enable production builds
- Use CDN for static assets
- Implement lazy loading
- Optimize bundle size

## 🔒 Security Considerations

### **Production Checklist**
- [ ] Set `APP_ENV=production`
- [ ] Use HTTPS for all connections
- [ ] Configure proper CORS origins
- [ ] Enable log rotation
- [ ] Set secure session cookies
- [ ] Use environment variables for secrets
- [ ] Enable PHP security headers
- [ ] Configure firewall rules

### **Logging Security**
- Sensitive data is automatically redacted
- Log files have restricted permissions
- Security events are logged separately
- Log rotation prevents disk space issues

## 📚 Additional Resources

- [CalDAV Protocol Specification](https://tools.ietf.org/html/rfc4791)
- [iCalendar Specification](https://tools.ietf.org/html/rfc5545)
- [Monolog Documentation](https://github.com/Seldaek/monolog)
- [Angular Documentation](https://angular.io/docs)
- [React Documentation](https://reactjs.org/docs)

## 🆘 Support

For issues and questions:
1. Check the troubleshooting section
2. Review log files for error details
3. Check GitHub issues
4. Contact the development team

---

## 🎉 Success!

Once installed, you should have:
- ✅ Backend API running on `http://localhost:8000`
- ✅ React frontend on `http://localhost:3000`
- ✅ Angular frontend on `http://localhost:4200`
- ✅ Structured logging system active
- ✅ CalDAV integration configured
- ✅ Professional error handling
