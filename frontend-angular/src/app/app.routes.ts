import { Routes } from '@angular/router';
import { LoginComponent } from './components/login.component';
import { CalendarComponent } from './components/calendar.component';
import { CalendarSelectionComponent } from './components/calendar-selection.component';
import { AuthGuard } from './guards/auth.guard';

export const routes: Routes = [
  {
    path: '',
    redirectTo: '/login',
    pathMatch: 'full'
  },
  {
    path: 'login',
    component: LoginComponent
  },
  {
    path: 'calendar-selection',
    component: CalendarSelectionComponent,
    canActivate: [AuthGuard]
  },
  {
    path: 'calendar',
    component: CalendarComponent,
    canActivate: [AuthGuard]
  },
  {
    path: '**',
    redirectTo: '/login'
  }
];
