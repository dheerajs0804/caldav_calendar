export const environment = {
  production: false,
  // Dynamic API URL - will be set by Docker environment variable
  apiUrl: (window as any).API_URL || 'http://php-backend:80',
  // Log level for Docker environment
  logLevel: 'warn'
};
