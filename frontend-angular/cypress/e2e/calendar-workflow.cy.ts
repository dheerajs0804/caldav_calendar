describe('Calendar Application E2E Tests', () => {
  beforeEach(() => {
    // Visit the application
    cy.visit('/');
  });

  describe('Authentication Flow', () => {
    it('should redirect to login page initially', () => {
      cy.url().should('include', '/login');
      cy.contains('Login').should('be.visible');
    });

    it('should login successfully with valid credentials', () => {
      cy.get('[data-cy=username-input]').type(Cypress.env('testUser').username);
      cy.get('[data-cy=password-input]').type(Cypress.env('testUser').password);
      cy.get('[data-cy=login-button]').click();

      // Should redirect to calendar after successful login
      cy.url().should('include', '/calendar');
      cy.contains('Mithi Calendar').should('be.visible');
    });

    it('should show error message for invalid credentials', () => {
      cy.get('[data-cy=username-input]').type('invaliduser');
      cy.get('[data-cy=password-input]').type('invalidpassword');
      cy.get('[data-cy=login-button]').click();

      cy.contains('Invalid credentials').should('be.visible');
    });

    it('should logout successfully', () => {
      // Login first
      cy.login(Cypress.env('testUser').username, Cypress.env('testUser').password);
      
      // Logout
      cy.get('[data-cy=logout-button]').click();
      
      // Should redirect to login page
      cy.url().should('include', '/login');
    });
  });

  describe('Calendar Management', () => {
    beforeEach(() => {
      cy.login(Cypress.env('testUser').username, Cypress.env('testUser').password);
    });

    it('should display calendar interface', () => {
      cy.contains('Mithi Calendar').should('be.visible');
      cy.get('[data-cy=calendar-header]').should('be.visible');
      cy.get('[data-cy=calendar-sidebar]').should('be.visible');
      cy.get('[data-cy=calendar-main]').should('be.visible');
    });

    it('should toggle sidebar', () => {
      cy.get('[data-cy=sidebar-toggle]').click();
      cy.get('[data-cy=calendar-sidebar]').should('have.class', 'hidden');
      
      cy.get('[data-cy=sidebar-toggle]').click();
      cy.get('[data-cy=calendar-sidebar]').should('not.have.class', 'hidden');
    });

    it('should change calendar view', () => {
      cy.get('[data-cy=view-day]').click();
      cy.get('[data-cy=calendar-main]').should('contain', 'Day View');
      
      cy.get('[data-cy=view-week]').click();
      cy.get('[data-cy=calendar-main]').should('contain', 'Week View');
      
      cy.get('[data-cy=view-month]').click();
      cy.get('[data-cy=calendar-main]').should('contain', 'Month View');
    });

    it('should navigate between dates', () => {
      cy.get('[data-cy=nav-previous]').click();
      cy.get('[data-cy=date-display]').should('not.contain', 'Today');
      
      cy.get('[data-cy=nav-today]').click();
      cy.get('[data-cy=date-display]').should('contain', 'Today');
      
      cy.get('[data-cy=nav-next]').click();
      cy.get('[data-cy=date-display]').should('not.contain', 'Today');
    });
  });

  describe('Event Management', () => {
    beforeEach(() => {
      cy.login(Cypress.env('testUser').username, Cypress.env('testUser').password);
    });

    it('should create a new event', () => {
      cy.get('[data-cy=add-event-button]').click();
      cy.get('[data-cy=event-modal]').should('be.visible');
      
      cy.get('[data-cy=event-title]').type('Test Event');
      cy.get('[data-cy=event-start-date]').type('2024-01-01');
      cy.get('[data-cy=event-start-time]').type('10:00');
      cy.get('[data-cy=event-end-date]').type('2024-01-01');
      cy.get('[data-cy=event-end-time]').type('11:00');
      cy.get('[data-cy=event-description]').type('Test event description');
      
      cy.get('[data-cy=save-event-button]').click();
      
      cy.get('[data-cy=event-modal]').should('not.be.visible');
      cy.contains('Test Event').should('be.visible');
    });

    it('should edit an existing event', () => {
      // Create event first
      cy.createEvent({
        title: 'Event to Edit',
        startDate: '2024-01-01',
        startTime: '10:00',
        endDate: '2024-01-01',
        endTime: '11:00'
      });
      
      // Click on event to edit
      cy.contains('Event to Edit').click();
      cy.get('[data-cy=event-detail-modal]').should('be.visible');
      
      cy.get('[data-cy=edit-event-button]').click();
      cy.get('[data-cy=event-title]').clear().type('Updated Event');
      
      cy.get('[data-cy=save-event-button]').click();
      
      cy.contains('Updated Event').should('be.visible');
      cy.contains('Event to Edit').should('not.exist');
    });

    it('should delete an event', () => {
      // Create event first
      cy.createEvent({
        title: 'Event to Delete',
        startDate: '2024-01-01',
        startTime: '10:00',
        endDate: '2024-01-01',
        endTime: '11:00'
      });
      
      // Click on event to delete
      cy.contains('Event to Delete').click();
      cy.get('[data-cy=event-detail-modal]').should('be.visible');
      
      cy.get('[data-cy=delete-event-button]').click();
      cy.get('[data-cy=confirm-delete-button]').click();
      
      cy.contains('Event to Delete').should('not.exist');
    });

    it('should create a recurring event', () => {
      cy.get('[data-cy=add-event-button]').click();
      
      cy.get('[data-cy=event-title]').type('Recurring Event');
      cy.get('[data-cy=event-start-date]').type('2024-01-01');
      cy.get('[data-cy=event-start-time]').type('10:00');
      cy.get('[data-cy=event-end-date]').type('2024-01-01');
      cy.get('[data-cy=event-end-time]').type('11:00');
      
      // Enable recurrence
      cy.get('[data-cy=recurrence-toggle]').check();
      cy.get('[data-cy=recurrence-frequency]').select('weekly');
      cy.get('[data-cy=recurrence-interval]').type('1');
      cy.get('[data-cy=recurrence-count]').type('5');
      
      cy.get('[data-cy=save-event-button]').click();
      
      cy.contains('Recurring Event').should('be.visible');
    });

    it('should handle event validation errors', () => {
      cy.get('[data-cy=add-event-button]').click();
      
      // Try to save without required fields
      cy.get('[data-cy=save-event-button]').click();
      
      cy.get('[data-cy=error-message]').should('be.visible');
      cy.get('[data-cy=error-message]').should('contain', 'Title is required');
    });
  });

  describe('Calendar Features', () => {
    beforeEach(() => {
      cy.login(Cypress.env('testUser').username, Cypress.env('testUser').password);
    });

    it('should search calendars', () => {
      cy.get('[data-cy=calendar-search]').type('Test');
      cy.get('[data-cy=calendar-list]').should('contain', 'Test');
    });

    it('should toggle calendar visibility', () => {
      cy.get('[data-cy=calendar-item]').first().within(() => {
        cy.get('[data-cy=calendar-toggle]').click();
      });
      
      // Calendar should be disabled
      cy.get('[data-cy=calendar-item]').first().should('not.have.class', 'enabled');
    });

    it('should export calendar', () => {
      cy.get('[data-cy=export-button]').click();
      cy.get('[data-cy=export-modal]').should('be.visible');
      
      cy.get('[data-cy=export-format]').select('ics');
      cy.get('[data-cy=export-date-range]').select('current-month');
      cy.get('[data-cy=confirm-export-button]').click();
      
      // Should trigger download
      cy.window().then((win) => {
        cy.stub(win, 'open').as('windowOpen');
      });
    });

    it('should import calendar', () => {
      cy.get('[data-cy=import-button]').click();
      cy.get('[data-cy=import-modal]').should('be.visible');
      
      // Test file upload
      cy.get('[data-cy=import-file-input]').selectFile('cypress/fixtures/test-calendar.ics');
      cy.get('[data-cy=confirm-import-button]').click();
      
      cy.get('[data-cy=import-modal]').should('not.be.visible');
      cy.contains('Import completed').should('be.visible');
    });
  });

  describe('Responsive Design', () => {
    beforeEach(() => {
      cy.login(Cypress.env('testUser').username, Cypress.env('testUser').password);
    });

    it('should work on mobile viewport', () => {
      cy.viewport(375, 667); // iPhone SE
      
      cy.get('[data-cy=calendar-header]').should('be.visible');
      cy.get('[data-cy=calendar-sidebar]').should('have.class', 'hidden'); // Should be hidden on mobile
      
      cy.get('[data-cy=sidebar-toggle]').click();
      cy.get('[data-cy=calendar-sidebar]').should('not.have.class', 'hidden');
    });

    it('should work on tablet viewport', () => {
      cy.viewport(768, 1024); // iPad
      
      cy.get('[data-cy=calendar-main]').should('be.visible');
      cy.get('[data-cy=calendar-sidebar]').should('be.visible');
    });
  });

  describe('Error Handling', () => {
    beforeEach(() => {
      cy.login(Cypress.env('testUser').username, Cypress.env('testUser').password);
    });

    it('should handle network errors gracefully', () => {
      // Intercept API calls and return error
      cy.intercept('GET', '/api/calendars/user', { statusCode: 500 }).as('getCalendarsError');
      
      cy.reload();
      cy.wait('@getCalendarsError');
      
      cy.get('[data-cy=error-message]').should('be.visible');
      cy.get('[data-cy=error-message]').should('contain', 'Failed to load calendars');
    });

    it('should handle offline mode', () => {
      cy.intercept('GET', '/api/**', { forceNetworkError: true }).as('offline');
      
      cy.get('[data-cy=add-event-button]').click();
      cy.get('[data-cy=event-title]').type('Offline Event');
      cy.get('[data-cy=save-event-button]').click();
      
      cy.get('[data-cy=error-message]').should('be.visible');
      cy.get('[data-cy=error-message]').should('contain', 'Network error');
    });
  });

  describe('Performance', () => {
    beforeEach(() => {
      cy.login(Cypress.env('testUser').username, Cypress.env('testUser').password);
    });

    it('should load calendar within acceptable time', () => {
      const startTime = Date.now();
      
      cy.get('[data-cy=calendar-main]').should('be.visible');
      
      cy.then(() => {
        const loadTime = Date.now() - startTime;
        expect(loadTime).to.be.lessThan(3000); // Should load within 3 seconds
      });
    });

    it('should handle large number of events', () => {
      // Create multiple events
      for (let i = 0; i < 10; i++) {
        cy.createEvent({
          title: `Event ${i}`,
          startDate: `2024-01-${String(i + 1).padStart(2, '0')}`,
          startTime: '10:00',
          endDate: `2024-01-${String(i + 1).padStart(2, '0')}`,
          endTime: '11:00'
        });
      }
      
      cy.get('[data-cy=calendar-main]').should('be.visible');
      cy.get('[data-cy=event-item]').should('have.length', 10);
    });
  });
});

