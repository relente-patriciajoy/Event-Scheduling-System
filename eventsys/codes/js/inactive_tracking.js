/**
 * Inactive Members Tracking JavaScript
 */

document.addEventListener('DOMContentLoaded', function () {
  // Initialize Lucide icons
  if (typeof lucide !== 'undefined') {
    lucide.createIcons();
  }

  // Initialize search functionality
  initializeSearch();

  // Initialize filters
  initializeFilters();
});

/**
 * Initialize search functionality
 */
function initializeSearch() {
  const searchInput = document.getElementById('searchInput');

  if (searchInput) {
    searchInput.addEventListener('keyup', function () {
      filterInactiveTable();
    });
  }
}

/**
 * Filter the inactive members table
 */
function filterInactiveTable() {
  const input = document.getElementById('searchInput');
  const filter = input.value.toLowerCase();
  const table = document.getElementById('inactiveTable');
  const rows = table.getElementsByTagName('tr');

  let visibleCount = 0;

  // Start from 1 to skip header row
  for (let i = 1; i < rows.length; i++) {
    const row = rows[i];
    const cells = row.getElementsByTagName('td');
    let found = false;

    // Search through all cells
    for (let j = 0; j < cells.length; j++) {
      const cellText = cells[j].textContent || cells[j].innerText;
      if (cellText.toLowerCase().indexOf(filter) > -1) {
        found = true;
        break;
      }
    }

    if (found) {
      row.style.display = '';
      visibleCount++;
    } else {
      row.style.display = 'none';
    }
  }

  // Update result count
  updateResultCount(visibleCount);
}

/**
 * Update the count of visible results
 */
function updateResultCount(count) {
  let countElement = document.getElementById('resultCount');

  if (!countElement) {
    countElement = document.createElement('div');
    countElement.id = 'resultCount';
    countElement.style.cssText = 'margin-top: 10px; color: #6b6b6b; font-size: 0.9rem;';

    const searchBar = document.querySelector('.search-bar');
    if (searchBar) {
      searchBar.appendChild(countElement);
    }
  }

  const input = document.getElementById('searchInput');
  if (input && input.value.trim() !== '') {
    countElement.textContent = `Found ${count} result${count !== 1 ? 's' : ''}`;
  } else {
    countElement.textContent = '';
  }
}

/**
 * Initialize filter buttons
 */
function initializeFilters() {
  const filterButtons = document.querySelectorAll('.filter-btn');

  filterButtons.forEach(button => {
    button.addEventListener('click', function () {
      // Remove active class from all buttons
      filterButtons.forEach(btn => btn.classList.remove('active'));

      // Add active class to clicked button
      this.classList.add('active');

      // Apply filter
      const filterType = this.dataset.filter;
      applyFilter(filterType);
    });
  });
}

/**
 * Apply filter to table
 */
function applyFilter(filterType) {
  const table = document.getElementById('inactiveTable');
  const rows = table.getElementsByTagName('tr');

  for (let i = 1; i < rows.length; i++) {
    const row = rows[i];
    const missedEventsCell = row.cells[4];
    const missedEvents = parseInt(missedEventsCell.textContent.trim());

    let shouldShow = true;

    switch (filterType) {
      case 'all':
        shouldShow = true;
        break;
      case 'high':
        shouldShow = missedEvents >= 3;
        break;
      case 'medium':
        shouldShow = missedEvents === 2;
        break;
      case 'low':
        shouldShow = missedEvents === 1;
        break;
    }

    row.style.display = shouldShow ? '' : 'none';
  }
}

/**
 * Send reminder email
 */
function sendReminder(email, name) {
  // Create mailto link
  const subject = encodeURIComponent('We Miss You at Our Events!');
  const body = encodeURIComponent(
    `Hi ${name},\n\n` +
    `We noticed you registered for our events but weren't able to attend. ` +
    `We'd love to see you at our upcoming events!\n\n` +
    `Check out our latest events and we hope to see you soon.\n\n` +
    `Best regards,\n` +
    `The Event Team`
  );

  window.location.href = `mailto:${email}?subject=${subject}&body=${body}`;
}

/**
 * Export inactive members to Excel
 */
function exportInactiveToExcel() {
  const table = document.getElementById('inactiveTable');

  if (!table) {
    console.error('Table not found');
    return;
  }

  // Clone table
  const tableClone = table.cloneNode(true);

  // Remove action column
  const rows = tableClone.getElementsByTagName('tr');
  for (let i = 0; i < rows.length; i++) {
    if (rows[i].cells.length > 0) {
      rows[i].deleteCell(-1); // Remove last cell (action column)
    }
  }

  // Remove icons from cloned table
  const icons = tableClone.querySelectorAll('[data-lucide]');
  icons.forEach(icon => icon.remove());

  // Convert to Excel
  let tableHTML = tableClone.outerHTML.replace(/ /g, '%20');

  const date = new Date().toISOString().split('T')[0];
  const filename = `inactive_members_${date}.xls`;

  const downloadLink = document.createElement('a');
  document.body.appendChild(downloadLink);

  downloadLink.href = 'data:application/vnd.ms-excel,' + tableHTML;
  downloadLink.download = filename;
  downloadLink.click();

  document.body.removeChild(downloadLink);

  showNotification('Export successful!', 'success');
}

/**
 * Show notification
 */
function showNotification(message, type = 'info') {
  const notification = document.createElement('div');
  notification.className = `notification notification-${type}`;
  notification.textContent = message;

  notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#6366f1'};
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10000;
        animation: slideIn 0.3s ease;
        font-weight: 600;
    `;

  document.body.appendChild(notification);

  // Remove after 3 seconds
  setTimeout(() => {
    notification.style.animation = 'slideOut 0.3s ease';
    setTimeout(() => {
      if (document.body.contains(notification)) {
        document.body.removeChild(notification);
      }
    }, 300);
  }, 3000);
}

/**
 * Sort table by column
 */
function sortTableByColumn(columnIndex) {
  const table = document.getElementById('inactiveTable');
  const tbody = table.getElementsByTagName('tbody')[0];
  const rows = Array.from(tbody.getElementsByTagName('tr'));

  // Get current sort direction
  const currentDirection = table.dataset.sortDirection || 'asc';
  const newDirection = currentDirection === 'asc' ? 'desc' : 'asc';
  table.dataset.sortDirection = newDirection;

  // Sort rows
  rows.sort((a, b) => {
    const aValue = a.getElementsByTagName('td')[columnIndex].textContent.trim();
    const bValue = b.getElementsByTagName('td')[columnIndex].textContent.trim();

    // Check if numeric
    const aNum = parseFloat(aValue);
    const bNum = parseFloat(bValue);

    if (!isNaN(aNum) && !isNaN(bNum)) {
      return newDirection === 'asc' ? aNum - bNum : bNum - aNum;
    }

    // String comparison
    if (newDirection === 'asc') {
      return aValue.localeCompare(bValue);
    } else {
      return bValue.localeCompare(aValue);
    }
  });

  // Re-append sorted rows
  rows.forEach(row => tbody.appendChild(row));
}

// Add animation styles
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);