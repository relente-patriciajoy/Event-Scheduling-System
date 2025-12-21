/**
 * Event Calendar JavaScript
 * Handles calendar rendering, event display, and interactions
 */

class EventCalendar {
  constructor() {
    this.currentDate = new Date();
    this.currentView = 'month'; // month, week, day
    this.events = [];
    this.init();
  }

  init() {
    this.fetchEvents();
    this.renderCalendar();
    this.attachEventListeners();
  }

  async fetchEvents() {
    try {
      const response = await fetch('get_events.php');
      const data = await response.json();

      if (data.success) {
        this.events = data.events.map(event => ({
          ...event,
          start_time: new Date(event.start_time),
          end_time: new Date(event.end_time)
        }));
        this.renderCalendar();
      } else {
        console.error('Failed to fetch events:', data.message);
      }
    } catch (error) {
      console.error('Error fetching events:', error);
    }
  }

  renderCalendar() {
    const year = this.currentDate.getFullYear();
    const month = this.currentDate.getMonth();

    // Update title
    document.getElementById('calendar-month-year').textContent =
      this.currentDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

    // Get first and last day of month
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const firstDayOfWeek = firstDay.getDay();
    const daysInMonth = lastDay.getDate();

    // Get previous month days
    const prevMonthLastDay = new Date(year, month, 0).getDate();

    // Build calendar grid
    const calendarGrid = document.getElementById('calendar-grid');
    calendarGrid.innerHTML = '';

    // Add day headers
    const dayHeaders = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    dayHeaders.forEach(day => {
      const header = document.createElement('div');
      header.className = 'calendar-day-header';
      header.textContent = day;
      calendarGrid.appendChild(header);
    });

    // Add previous month days
    for (let i = firstDayOfWeek - 1; i >= 0; i--) {
      const day = prevMonthLastDay - i;
      this.createDayCell(calendarGrid, day, true, new Date(year, month - 1, day));
    }

    // Add current month days
    for (let day = 1; day <= daysInMonth; day++) {
      const date = new Date(year, month, day);
      this.createDayCell(calendarGrid, day, false, date);
    }

    // Add next month days to fill grid
    const remainingCells = 42 - (firstDayOfWeek + daysInMonth);
    for (let day = 1; day <= remainingCells; day++) {
      this.createDayCell(calendarGrid, day, true, new Date(year, month + 1, day));
    }
  }

  createDayCell(container, dayNumber, isOtherMonth, date) {
    const dayCell = document.createElement('div');
    dayCell.className = 'calendar-day';

    if (isOtherMonth) {
      dayCell.classList.add('other-month');
    }

    // Check if today
    const today = new Date();
    if (date.toDateString() === today.toDateString()) {
      dayCell.classList.add('today');
    }

    // Day number
    const dayNumberDiv = document.createElement('div');
    dayNumberDiv.className = 'calendar-day-number';
    dayNumberDiv.textContent = dayNumber;
    dayCell.appendChild(dayNumberDiv);

    // Get events for this day
    const dayEvents = this.getEventsForDate(date);
    const maxVisible = 3;

    dayEvents.slice(0, maxVisible).forEach(event => {
      const eventDiv = document.createElement('div');
      eventDiv.className = 'calendar-event';
      eventDiv.textContent = event.title;
      eventDiv.onclick = (e) => {
        e.stopPropagation();
        this.showEventModal(event);
      };
      dayCell.appendChild(eventDiv);
    });

    // Show "more" if there are additional events
    if (dayEvents.length > maxVisible) {
      const moreDiv = document.createElement('div');
      moreDiv.className = 'calendar-event-more';
      moreDiv.textContent = `+${dayEvents.length - maxVisible} more`;
      moreDiv.onclick = (e) => {
        e.stopPropagation();
        this.showDayEvents(date, dayEvents);
      };
      dayCell.appendChild(moreDiv);
    }

    // Click on empty day
    dayCell.onclick = () => {
      if (dayEvents.length > 0) {
        this.showDayEvents(date, dayEvents);
      }
    };

    container.appendChild(dayCell);
  }

  getEventsForDate(date) {
    return this.events.filter(event => {
      const eventDate = new Date(event.start_time);
      return eventDate.toDateString() === date.toDateString();
    });
  }

  showEventModal(event) {
    const modal = document.getElementById('event-modal');
    const overlay = document.getElementById('event-modal-overlay');

    // Populate modal
    document.getElementById('modal-event-title').textContent = event.title;
    document.getElementById('modal-event-date').textContent =
      event.start_time.toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      });

    document.getElementById('modal-event-time').textContent =
      `${event.start_time.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })} - ${event.end_time.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })}`;

    document.getElementById('modal-event-venue').textContent = event.venue || 'TBA';
    document.getElementById('modal-event-capacity').textContent = `${event.capacity} people`;
    document.getElementById('modal-event-price').textContent = event.price > 0 ? `$${parseFloat(event.price).toFixed(2)}` : 'Free';
    document.getElementById('modal-event-description').textContent = event.description || 'No description available.';

    // Set registration button
    const registerBtn = document.getElementById('modal-register-btn');
    registerBtn.onclick = () => {
      window.location.href = `../event/event_register.php?event_id=${event.event_id}`;
    };

    overlay.classList.add('active');
  }

  showDayEvents(date, events) {
    // Show all events for a specific day
    if (events.length === 1) {
      this.showEventModal(events[0]);
    } else {
      // Could implement a day view here
      this.showEventModal(events[0]);
    }
  }

  closeModal() {
    document.getElementById('event-modal-overlay').classList.remove('active');
  }

  previousMonth() {
    this.currentDate.setMonth(this.currentDate.getMonth() - 1);
    this.renderCalendar();
  }

  nextMonth() {
    this.currentDate.setMonth(this.currentDate.getMonth() + 1);
    this.renderCalendar();
  }

  goToToday() {
    this.currentDate = new Date();
    this.renderCalendar();
  }

  attachEventListeners() {
    // Navigation buttons
    document.getElementById('prev-month').onclick = () => this.previousMonth();
    document.getElementById('next-month').onclick = () => this.nextMonth();
    document.getElementById('today-btn').onclick = () => this.goToToday();

    // Close modal
    document.getElementById('modal-close-btn').onclick = () => this.closeModal();
    document.getElementById('event-modal-overlay').onclick = (e) => {
      if (e.target.id === 'event-modal-overlay') {
        this.closeModal();
      }
    };

    // Escape key to close modal
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        this.closeModal();
      }
    });
  }
}

// Initialize calendar when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
  new EventCalendar();
  lucide.createIcons();
});
