/// <reference types="cypress" />

declare global {
  namespace Cypress {
    interface Chainable {
      /**
       * Custom command to login with username and password
       * @example cy.login('username', 'password')
       */
      login(username: string, password: string): Chainable<void>;

      /**
       * Custom command to create an event
       * @example cy.createEvent({ title: 'Test Event', startDate: '2024-01-01' })
       */
      createEvent(eventData: {
        title: string;
        startDate: string;
        startTime?: string;
        endDate?: string;
        endTime?: string;
        description?: string;
        allDay?: boolean;
      }): Chainable<void>;

      /**
       * Custom command to wait for API response
       * @example cy.waitForApi('GET', '/api/calendars/user')
       */
      waitForApi(method: string, url: string): Chainable<void>;

      /**
       * Custom command to check if element is visible in viewport
       * @example cy.isInViewport('[data-cy=calendar-main]')
       */
      isInViewport(selector: string): Chainable<void>;

      /**
       * Custom command to scroll to element
       * @example cy.scrollToElement('[data-cy=event-item]')
       */
      scrollToElement(selector: string): Chainable<void>;
    }
  }
}

Cypress.Commands.add('login', (username: string, password: string) => {
  cy.visit('/login');
  cy.get('[data-cy=username-input]').type(username);
  cy.get('[data-cy=password-input]').type(password);
  cy.get('[data-cy=login-button]').click();
  cy.url().should('include', '/calendar');
});

Cypress.Commands.add('createEvent', (eventData) => {
  cy.get('[data-cy=add-event-button]').click();
  cy.get('[data-cy=event-modal]').should('be.visible');
  
  cy.get('[data-cy=event-title]').type(eventData.title);
  cy.get('[data-cy=event-start-date]').type(eventData.startDate);
  
  if (eventData.startTime) {
    cy.get('[data-cy=event-start-time]').type(eventData.startTime);
  }
  
  if (eventData.endDate) {
    cy.get('[data-cy=event-end-date]').type(eventData.endDate);
  } else {
    cy.get('[data-cy=event-end-date]').type(eventData.startDate);
  }
  
  if (eventData.endTime) {
    cy.get('[data-cy=event-end-time]').type(eventData.endTime);
  } else if (eventData.startTime) {
    // Default end time to 1 hour after start time
    const startTime = eventData.startTime;
    const [hours, minutes] = startTime.split(':');
    const endHours = String(parseInt(hours) + 1).padStart(2, '0');
    cy.get('[data-cy=event-end-time]').type(`${endHours}:${minutes}`);
  }
  
  if (eventData.description) {
    cy.get('[data-cy=event-description]').type(eventData.description);
  }
  
  if (eventData.allDay) {
    cy.get('[data-cy=event-all-day]').check();
  }
  
  cy.get('[data-cy=save-event-button]').click();
  cy.get('[data-cy=event-modal]').should('not.be.visible');
});

Cypress.Commands.add('waitForApi', (method: string, url: string) => {
  cy.intercept(method, url).as('apiCall');
  cy.wait('@apiCall');
});

Cypress.Commands.add('isInViewport', (selector: string) => {
  cy.get(selector).then(($el) => {
    const bottom = Cypress.$(cy.state('window')).height();
    const rect = $el[0].getBoundingClientRect();
    
    expect(rect.top).to.be.lessThan(bottom);
    expect(rect.bottom).to.be.greaterThan(0);
  });
});

Cypress.Commands.add('scrollToElement', (selector: string) => {
  cy.get(selector).scrollIntoView();
  cy.get(selector).should('be.visible');
});

// Custom error handling
Cypress.on('uncaught:exception', (err, runnable) => {
  // Returning false here prevents Cypress from failing the test
  // on uncaught exceptions that are expected in the application
  if (err.message.includes('ResizeObserver loop limit exceeded')) {
    return false;
  }
  if (err.message.includes('Non-Error promise rejection captured')) {
    return false;
  }
  return true;
});

// Global test configuration
beforeEach(() => {
  // Set default viewport
  cy.viewport(1280, 720);
  
  // Clear localStorage and sessionStorage
  cy.clearLocalStorage();
  cy.clearCookies();
  
  // Set up default intercepts
  cy.intercept('GET', '/api/health', { fixture: 'health.json' }).as('healthCheck');
});

// Custom assertions
chai.use((chai, utils) => {
  const assert = chai.assert;
  
  assert.isInViewport = function(element: JQuery<HTMLElement>) {
    const windowHeight = Cypress.$(cy.state('window')).height();
    const rect = element[0].getBoundingClientRect();
    
    assert.isTrue(
      rect.top < windowHeight && rect.bottom > 0,
      'Element should be in viewport'
    );
  };
  
  assert.hasClass = function(element: JQuery<HTMLElement>, className: string) {
    assert.isTrue(
      element.hasClass(className),
      `Element should have class "${className}"`
    );
  };
  
  assert.isVisible = function(element: JQuery<HTMLElement>) {
    assert.isTrue(
      element.is(':visible'),
      'Element should be visible'
    );
  };
});

export {};

