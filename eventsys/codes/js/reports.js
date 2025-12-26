/**
 * Event Reports JavaScript
 * Handles report generation, export, and interactions
 */

// Initialize Lucide icons after page load
document.addEventListener('DOMContentLoaded', function () {
  lucide.createIcons();
});

/**
 * Export table data to Excel format
 */
function exportToExcel() {
  const table = document.getElementById('participantTable');
  if (!table) {
    alert('No data table found to export.');
    return;
  }

  // Clone table to avoid modifying the original
  const tableClone = table.cloneNode(true);

  // Remove any action columns or buttons
  const actionCells = tableClone.querySelectorAll('.action-column, .export-btn, button');
  actionCells.forEach(cell => cell.remove());

  // Convert table to Excel format
  let tableHTML = tableClone.outerHTML.replace(/ /g, '%20');

  // Generate filename with timestamp
  const eventId = getEventIdFromURL();
  const timestamp = new Date().toISOString().split('T')[0];
  const filename = `event_report_${eventId}_${timestamp}.xls`;

  // Create download link
  const downloadLink = document.createElement('a');
  document.body.appendChild(downloadLink);

  downloadLink.href = 'data:application/vnd.ms-excel,' + tableHTML;
  downloadLink.download = filename;
  downloadLink.click();

  document.body.removeChild(downloadLink);

  showNotification('Report exported successfully!', 'success');
}

/**
 * Export table data to CSV format
 */
function exportToCSV() {
  const table = document.getElementById('participantTable');
  if (!table) {
    alert('No data table found to export.');
    return;
  }

  let csv = [];
  const rows = table.querySelectorAll('tr');

  rows.forEach(row => {
    let rowData = [];
    const cells = row.querySelectorAll('td, th');

    cells.forEach(cell => {
      // Skip action columns
      if (!cell.classList.contains('action-column')) {
        let text = cell.innerText.replace(/"/g, '""'); // Escape quotes
        rowData.push(`"${text}"`);
      }
    });

    csv.push(rowData.join(','));
  });

  // Create CSV file
  const csvContent = csv.join('\n');
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });

  // Generate filename
  const eventId = getEventIdFromURL();
  const timestamp = new Date().toISOString().split('T')[0];
  const filename = `event_report_${eventId}_${timestamp}.csv`;

  // Download
  const link = document.createElement('a');
  if (navigator.msSaveBlob) {
    navigator.msSaveBlob(blob, filename);
  } else {
    link.href = URL.createObjectURL(blob);
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }

  showNotification('CSV exported successfully!', 'success');
}

/**
 * Print report (can also save as PDF)
 */
function printReport() {
  window.print();
}

/**
 * Filter table rows based on search input
 */
function filterTable() {
  const input = document.getElementById('searchInput');
  if (!input) return;

  const filter = input.value.toLowerCase();
  const table = document.getElementById('participantTable');
  if (!table) return;

  const rows = table.getElementsByTagName('tr');
  let visibleCount = 0;

  // Start from 1 to skip header row
  for (let i = 1; i < rows.length; i++) {
    const cells = rows[i].getElementsByTagName('td');
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
      rows[i].style.display = '';
      visibleCount++;
    } else {
      rows[i].style.display = 'none';
    }
  }

  // Update result count if element exists
  const resultCount = document.getElementById('resultCount');
  if (resultCount) {
    resultCount.textContent = `Showing ${visibleCount} result(s)`;
  }
}

/**
 * Get event ID from URL parameters
 */
function getEventIdFromURL() {
  const urlParams = new URLSearchParams(window.location.search);
  return urlParams.get('event_id') || 'unknown';
}

/**
 * Show notification message
 */
function showNotification(message, type = 'info') {
  // Remove existing notifications
  const existingNotification = document.querySelector('.notification-toast');
  if (existingNotification) {
    existingNotification.remove();
  }

  // Create notification element
  const notification = document.createElement('div');
  notification.className = `notification-toast notification-${type}`;
  notification.innerHTML = `
        <div style="display: flex; align-items: center; gap: 10px;">
            <i data-lucide="${getNotificationIcon(type)}" style="width: 20px; height: 20px;"></i>
            <span>${message}</span>
        </div>
    `;

  // Style the notification
  notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 8px;
        background: white;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 10000;
        animation: slideIn 0.3s ease;
    `;

  // Add type-specific styling
  switch (type) {
    case 'success':
      notification.style.borderLeft = '4px solid #10b981';
      notification.style.color = '#065f46';
      break;
    case 'error':
      notification.style.borderLeft = '4px solid #ef4444';
      notification.style.color = '#991b1b';
      break;
    case 'warning':
      notification.style.borderLeft = '4px solid #f59e0b';
      notification.style.color = '#92400e';
      break;
    default:
      notification.style.borderLeft = '4px solid #3b82f6';
      notification.style.color = '#1e40af';
  }

  document.body.appendChild(notification);
  lucide.createIcons();

  // Auto remove after 3 seconds
  setTimeout(() => {
    notification.style.animation = 'slideOut 0.3s ease';
    setTimeout(() => notification.remove(), 300);
  }, 3000);
}

/**
 * Get appropriate icon for notification type
 */
function getNotificationIcon(type) {
  switch (type) {
    case 'success': return 'check-circle';
    case 'error': return 'x-circle';
    case 'warning': return 'alert-triangle';
    default: return 'info';
  }
}

/**
 * Generate and download attendance summary
 */
function downloadSummary() {
  const stats = {
    totalRegistrations: document.querySelector('.stat-card:nth-child(1) .stat-value')?.textContent || '0',
    totalAttended: document.querySelector('.stat-card:nth-child(2) .stat-value')?.textContent || '0',
    attendanceRate: document.querySelector('.stat-card:nth-child(3) .stat-value')?.textContent || '0%',
    totalRevenue: document.querySelector('.stat-card:nth-child(4) .stat-value')?.textContent || '$0.00'
  };

  const eventTitle = document.querySelector('.report-section h3')?.textContent || 'Event Report';

  let summary = `EVENT SUMMARY REPORT\n`;
  summary += `=====================\n\n`;
  summary += `Event: ${eventTitle}\n`;
  summary += `Generated: ${new Date().toLocaleString()}\n\n`;
  summary += `STATISTICS:\n`;
  summary += `- Total Registrations: ${stats.totalRegistrations}\n`;
  summary += `- Total Attended: ${stats.totalAttended}\n`;
  summary += `- Attendance Rate: ${stats.attendanceRate}\n`;
  summary += `- Total Revenue: ${stats.totalRevenue}\n`;

  const blob = new Blob([summary], { type: 'text/plain' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = `summary_${getEventIdFromURL()}_${new Date().toISOString().split('T')[0]}.txt`;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);

  showNotification('Summary downloaded successfully!', 'success');
}

/**
 * Sort table by column
 */
function sortTable(columnIndex) {
  const table = document.getElementById('participantTable');
  if (!table) return;

  const tbody = table.querySelector('tbody');
  const rows = Array.from(tbody.querySelectorAll('tr'));

  // Determine sort direction
  const currentDirection = tbody.getAttribute('data-sort-direction') || 'asc';
  const newDirection = currentDirection === 'asc' ? 'desc' : 'asc';

  // Sort rows
  rows.sort((a, b) => {
    const aValue = a.cells[columnIndex].textContent.trim();
    const bValue = b.cells[columnIndex].textContent.trim();

    if (newDirection === 'asc') {
      return aValue.localeCompare(bValue, undefined, { numeric: true });
    } else {
      return bValue.localeCompare(aValue, undefined, { numeric: true });
    }
  });

  // Re-append sorted rows
  rows.forEach(row => tbody.appendChild(row));

  // Store sort direction
  tbody.setAttribute('data-sort-direction', newDirection);

  showNotification(`Sorted ${newDirection === 'asc' ? 'ascending' : 'descending'}`, 'info');
}

/**
 * Send email reminder to inactive member
 */
function sendReminder(email, name) {
  if (confirm(`Send reminder email to ${name} (${email})?`)) {
    // In a real implementation, this would make an AJAX call to a PHP backend
    window.location.href = `mailto:${email}?subject=Event Registration Reminder&body=Hello ${name},%0D%0A%0D%0AWe noticed you registered for our event but were unable to attend.`;
    showNotification('Email client opened', 'info');
  }
}

// Add CSS animation for notifications
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