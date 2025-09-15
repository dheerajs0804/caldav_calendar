import { Injectable } from '@angular/core';

/**
 * Thunderbird-style Local Color Registry Service
 * 
 * This service manages calendar colors locally in the browser,
 * similar to Thunderbird's calendar.registry.*.color settings.
 * 
 * Key Features:
 * - Local storage (no backend dependency)
 * - Per-calendar color management
 * - Dynamic color updates
 * - Browser persistence
 */
@Injectable({
  providedIn: 'root'
})
export class ColorRegistryService {
  private readonly STORAGE_KEY = 'calendar_color_registry';
  private colorRegistry: Map<string, string> = new Map();

  constructor() {
    this.loadFromStorage();
  }

  /**
   * Load color registry from localStorage
   * Similar to Thunderbird's config editor loading
   */
  private loadFromStorage(): void {
    try {
      const stored = localStorage.getItem(this.STORAGE_KEY);
      if (stored) {
        const registry = JSON.parse(stored);
        this.colorRegistry = new Map(Object.entries(registry));
        console.log('🎨 Color Registry loaded:', this.colorRegistry);
      }
    } catch (error) {
      console.error('Failed to load color registry:', error);
      this.colorRegistry = new Map();
    }
  }

  /**
   * Save color registry to localStorage
   * Similar to Thunderbird's config persistence
   */
  private saveToStorage(): void {
    try {
      const registry = Object.fromEntries(this.colorRegistry);
      localStorage.setItem(this.STORAGE_KEY, JSON.stringify(registry));
      console.log('💾 Color Registry saved:', registry);
    } catch (error) {
      console.error('Failed to save color registry:', error);
    }
  }

  /**
   * Set color for a calendar
   * Similar to Thunderbird's calendar Properties dialog
   * 
   * @param calendarId - Calendar identifier (name, URL, or ID)
   * @param color - Hex color code (e.g., '#ff0000')
   */
  setCalendarColor(calendarId: string, color: string): void {
    console.log(`🎨 Setting color for calendar '${calendarId}': ${color}`);
    
    // Store locally for immediate use
    this.colorRegistry.set(calendarId, color);
    this.saveToStorage();
    
    console.log(`🎨 Color stored locally for '${calendarId}': ${color}`);
  }

  /**
   * Get color for a calendar
   * Returns default blue if not found
   * 
   * @param calendarId - Calendar identifier
   * @returns Hex color code
   */
  getCalendarColor(calendarId: string): string {
    const color = this.colorRegistry.get(calendarId);
    if (color) {
      console.log(`🎨 Found color for calendar '${calendarId}': ${color}`);
      return color;
    }
    
    // Default Thunderbird-style blue
    const defaultColor = '#4285f4';
    console.log(`🎨 Using default color for calendar '${calendarId}': ${defaultColor}`);
    return defaultColor;
  }

  /**
   * Get all calendar colors
   * Similar to Thunderbird's config editor view
   * 
   * @returns Map of calendar IDs to colors
   */
  getAllCalendarColors(): Map<string, string> {
    return new Map(this.colorRegistry);
  }

  /**
   * Check if calendar has a custom color
   * 
   * @param calendarId - Calendar identifier
   * @returns true if custom color exists
   */
  hasCustomColor(calendarId: string): boolean {
    return this.colorRegistry.has(calendarId);
  }

  /**
   * Remove color for a calendar (revert to default)
   * 
   * @param calendarId - Calendar identifier
   */
  removeCalendarColor(calendarId: string): void {
    console.log(`🗑️ Removing color for calendar '${calendarId}'`);
    this.colorRegistry.delete(calendarId);
    this.saveToStorage();
  }

  /**
   * Clear all calendar colors
   */
  clearAllColors(): void {
    console.log('🧹 Clearing all calendar colors');
    this.colorRegistry.clear();
    this.saveToStorage();
  }

  /**
   * Get registry statistics
   * Useful for debugging
   */
  getRegistryStats(): { totalCalendars: number; customColors: number } {
    return {
      totalCalendars: this.colorRegistry.size,
      customColors: this.colorRegistry.size
    };
  }
}
