# 🚀 Angular Frontend Installation Guide

## 📋 **Current Status**
✅ Dependencies installed (`npm install` completed)  
✅ Logging system implemented  
✅ Environment configuration updated  
⏳ Angular CLI setup needed  
⏳ Development server startup  

## 🔧 **Next Steps to Complete Installation**

### **Step 1: Install Angular CLI (if not already installed)**

#### **Option A: Global Installation (Recommended)**
```bash
npm install -g @angular/cli
```

#### **Option B: Use npx (No Global Installation)**
```bash
# Navigate to Angular project
cd frontend-angular

# Start development server using npx
npx ng serve --port 4200
```

### **Step 2: Start Development Server**

```bash
# Navigate to Angular project directory
cd frontend-angular

# Start the development server
ng serve --port 4200

# Or if ng is not globally installed:
npx ng serve --port 4200
```

### **Step 3: Access the Application**

Once the server starts, open your browser and navigate to:
```
http://localhost:4200
```

## 🧪 **Testing the Logging System**

### **Method 1: Browser Console Testing**

1. **Open Developer Tools** (F12)
2. **Go to Console tab**
3. **Interact with the application** (login, create events, etc.)
4. **Check console for structured log messages**

### **Method 2: Add Logging Test Component**

I can create a test component to verify logging functionality:

```typescript
// Add this to any component to test logging
import { LoggingService } from '../services/logging.service';

constructor(private loggingService: LoggingService) {}

// Test methods
testUserAction() {
  this.loggingService.logUserAction('test_action', {
    user_id: 123,
    action_type: 'button_click'
  }, true);
}

testSensitiveData() {
  this.loggingService.info('Test with sensitive data', {
    username: 'test_user',
    password: 'secret123',  // Will be redacted
    normal_data: 'this_shows'
  });
}
```

## 📊 **What You Should See**

### **In Browser Console (Development Mode)**
```
[2024-01-01T12:00:00.000Z] INFO: User action completed: test_action {"user_id":123,"action_type":"button_click"}
[2024-01-01T12:00:00.000Z] INFO: Test with sensitive data {"username":"test_user","password":"[REDACTED]","normal_data":"this_shows"}
```

### **Log Levels Available**
- **DEBUG**: Detailed processing information
- **INFO**: User actions and general flow
- **WARN**: Warning conditions
- **ERROR**: Error conditions

## 🔧 **Environment Configuration**

### **Development Environment** (`src/environments/environment.ts`)
```typescript
export const environment = {
  production: false,
  apiUrl: 'http://localhost:8000',
  logLevel: 'debug',  // All log levels enabled
  enableLogging: true
};
```

### **Production Environment** (`src/environments/environment.prod.ts`)
```typescript
export const environment = {
  production: true,
  apiUrl: 'https://your-production-api.com',
  logLevel: 'info',  // Only info, warn, error
  enableLogging: true
};
```

## 🚀 **Available Scripts**

```bash
# Development server
ng serve --port 4200

# Build for development
ng build --configuration=development

# Build for production
ng build --configuration=production

# Run tests
ng test

# Lint code
ng lint
```

## 🔍 **Troubleshooting**

### **Issue: 'ng' command not found**
**Solution**: Install Angular CLI globally or use npx
```bash
npm install -g @angular/cli
# OR
npx ng serve
```

### **Issue: Port 4200 already in use**
**Solution**: Use a different port
```bash
ng serve --port 4201
```

### **Issue: Dependencies not installed**
**Solution**: Run npm install
```bash
cd frontend-angular
npm install
```

### **Issue: Build errors**
**Solution**: Check for TypeScript errors
```bash
ng build --configuration=development
```

## 📁 **Project Structure**

```
frontend-angular/
├── src/
│   ├── app/
│   │   ├── components/
│   │   │   ├── calendar-selection.component.ts  # Updated with logging
│   │   │   └── calendar.component.ts
│   │   ├── services/
│   │   │   ├── logging.service.ts              # New logging service
│   │   │   ├── api.service.ts
│   │   │   └── auth.service.ts
│   │   └── app.component.ts
│   ├── environments/
│   │   ├── environment.ts                      # Development config
│   │   └── environment.prod.ts                 # Production config
│   └── main.ts
├── package.json                                # Updated with loglevel
└── angular.json
```

## 🎯 **Key Features Implemented**

### **✅ Professional Logging System**
- Structured logging with context data
- Automatic sensitive data sanitization
- Environment-based log levels
- Performance tracking capabilities

### **✅ Security Features**
- Sensitive data redaction (passwords, tokens, keys)
- Security event logging
- Production-safe logging levels

### **✅ Developer Experience**
- Rich debugging information
- Clear error messages
- Performance monitoring
- Easy-to-use logging methods

## 🚀 **Ready to Use**

Once you complete the installation steps above, your Angular application will have:

1. **Professional logging system** with proper levels and security
2. **Environment-aware configuration** for development and production
3. **Structured log messages** with rich context data
4. **Automatic data sanitization** for security
5. **Performance monitoring** capabilities

## 📞 **Next Actions**

1. **Install Angular CLI** (if not already installed)
2. **Start development server** with `ng serve --port 4200`
3. **Open browser** to `http://localhost:4200`
4. **Test logging** by interacting with the application
5. **Check browser console** for structured log messages

Your Angular application is now ready with a professional logging system! 🎉
