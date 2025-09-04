import { bootstrapApplication } from '@angular/platform-browser';
import { AppComponent } from './app/app.component';
import { provideHttpClient, withInterceptors } from '@angular/common/http';
import { provideRouter } from '@angular/router';
import { routes } from './app/app.routes';
import { provideAnimations } from '@angular/platform-browser/animations';

bootstrapApplication(AppComponent, {
  providers: [
    provideHttpClient(withInterceptors([
      (req, next) => {
        // Add withCredentials to all requests for session support
        const modifiedReq = req.clone({ withCredentials: true });
        return next(modifiedReq);
      }
    ])),
    provideRouter(routes),
    provideAnimations()
  ]
}).catch(err => console.error(err));
