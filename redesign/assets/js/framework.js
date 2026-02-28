/**
 * Monastery Healthcare System v2.0
 * JavaScript Framework
 * Modern, functional JavaScript for UI interactions
 */

class MHS {
  constructor() {
    this.components = {};
    this.init();
  }

  init() {
    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => this.initializeComponents());
    } else {
      this.initializeComponents();
    }
  }

  initializeComponents() {
    this.initSidebar();
    this.initModals();
    this.initForms();
    this.initTables();
    this.initCharts();
    this.initNotifications();
    this.initTooltips();
  }

  // ============================================
  // SIDEBAR COMPONENT
  // ============================================
  
  initSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const toggleBtn = document.querySelector('.sidebar-toggle');
    const overlay = document.querySelector('.sidebar-overlay');

    if (toggleBtn) {
      toggleBtn.addEventListener('click', () => {
        sidebar?.classList.toggle('open');
        overlay?.classList.toggle('active');
      });
    }

    if (overlay) {
      overlay.addEventListener('click', () => {
        sidebar?.classList.remove('open');
        overlay.classList.remove('active');
      });
    }

    // Close sidebar on escape key
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        sidebar?.classList.remove('open');
        overlay?.classList.remove('active');
      }
    });
  }

  // ============================================
  // MODAL COMPONENT
  // ============================================

  initModals() {
    // Modal triggers
    document.addEventListener('click', (e) => {
      const trigger = e.target.closest('[data-modal-target]');
      if (trigger) {
        const modalId = trigger.dataset.modalTarget;
        this.openModal(modalId);
      }

      // Close modal triggers
      const close = e.target.closest('[data-modal-close]');
      if (close) {
        this.closeModal();
      }

      // Backdrop click
      if (e.target.classList.contains('modal-backdrop')) {
        this.closeModal();
      }
    });

    // Close modal on escape key
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        this.closeModal();
      }
    });
  }

  openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.classList.add('active');
      document.body.style.overflow = 'hidden';
      
      // Focus trap
      const focusableElements = modal.querySelectorAll(
        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
      );
      if (focusableElements.length > 0) {
        focusableElements[0].focus();
      }
    }
  }

  closeModal() {
    const activeModal = document.querySelector('.modal.active');
    if (activeModal) {
      activeModal.classList.remove('active');
      document.body.style.overflow = '';
    }
  }

  // ============================================
  // FORM ENHANCEMENTS
  // ============================================

  initForms() {
    // Form validation
    document.addEventListener('submit', (e) => {
      const form = e.target;
      if (form.classList.contains('needs-validation')) {
        if (!form.checkValidity()) {
          e.preventDefault();
          e.stopPropagation();
        }
        form.classList.add('was-validated');
      }
    });

    // Auto-resize textareas
    document.addEventListener('input', (e) => {
      if (e.target.tagName === 'TEXTAREA' && e.target.classList.contains('auto-resize')) {
        e.target.style.height = 'auto';
        e.target.style.height = e.target.scrollHeight + 'px';
      }
    });

    // File upload preview
    document.addEventListener('change', (e) => {
      if (e.target.type === 'file' && e.target.dataset.preview) {
        this.handleFilePreview(e.target);
      }
    });
  }

  handleFilePreview(input) {
    const file = input.files[0];
    const previewId = input.dataset.preview;
    const preview = document.getElementById(previewId);

    if (file && preview) {
      if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = (e) => {
          preview.innerHTML = `<img src="${e.target.result}" alt="Preview" class="max-w-full h-32 object-cover rounded">`;
        };
        reader.readAsDataURL(file);
      } else {
        preview.innerHTML = `<div class="file-info p-4 border border-gray-200 rounded">
          <div class="font-medium">${file.name}</div>
          <div class="text-sm text-gray-500">${this.formatFileSize(file.size)}</div>
        </div>`;
      }
    }
  }

  formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  }

  // ============================================
  // TABLE ENHANCEMENTS
  // ============================================

  initTables() {
    // Sortable tables
    document.addEventListener('click', (e) => {
      const header = e.target.closest('th[data-sortable]');
      if (header) {
        this.sortTable(header);
      }
    });

    // Row selection
    document.addEventListener('change', (e) => {
      if (e.target.type === 'checkbox' && e.target.classList.contains('row-select')) {
        this.handleRowSelection(e.target);
      }
    });
  }

  sortTable(header) {
    const table = header.closest('table');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const column = Array.from(header.parentNode.children).indexOf(header);
    const currentDirection = header.dataset.sortDirection || 'asc';
    const newDirection = currentDirection === 'asc' ? 'desc' : 'asc';

    // Remove existing sort indicators
    header.parentNode.querySelectorAll('th').forEach(th => {
      th.classList.remove('sort-asc', 'sort-desc');
      delete th.dataset.sortDirection;
    });

    // Add new sort indicator
    header.classList.add(`sort-${newDirection}`);
    header.dataset.sortDirection = newDirection;

    // Sort rows
    rows.sort((a, b) => {
      const aValue = a.cells[column].textContent.trim();
      const bValue = b.cells[column].textContent.trim();
      
      // Check if values are numeric
      const aNum = parseFloat(aValue);
      const bNum = parseFloat(bValue);
      
      if (!isNaN(aNum) && !isNaN(bNum)) {
        return newDirection === 'asc' ? aNum - bNum : bNum - aNum;
      } else {
        return newDirection === 'asc' 
          ? aValue.localeCompare(bValue)
          : bValue.localeCompare(aValue);
      }
    });

    // Reorder rows
    rows.forEach(row => tbody.appendChild(row));
  }

  handleRowSelection(checkbox) {
    const row = checkbox.closest('tr');
    const selectAllCheckbox = document.querySelector('.select-all-rows');
    
    if (checkbox.checked) {
      row.classList.add('selected');
    } else {
      row.classList.remove('selected');
    }

    // Update select all checkbox
    if (selectAllCheckbox) {
      const allRowCheckboxes = document.querySelectorAll('.row-select');
      const checkedCount = document.querySelectorAll('.row-select:checked').length;
      
      selectAllCheckbox.checked = checkedCount === allRowCheckboxes.length;
      selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < allRowCheckboxes.length;
    }
  }

  // ============================================
  // CHART INTEGRATION
  // ============================================

  initCharts() {
    // Initialize charts if Chart.js is available
    if (typeof Chart !== 'undefined') {
      document.querySelectorAll('[data-chart]').forEach(canvas => {
        this.createChart(canvas);
      });
    }
  }

  createChart(canvas) {
    const chartType = canvas.dataset.chart;
    const chartData = JSON.parse(canvas.dataset.chartData || '{}');
    const chartOptions = JSON.parse(canvas.dataset.chartOptions || '{}');

    const defaultOptions = {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom',
        }
      }
    };

    const options = { ...defaultOptions, ...chartOptions };

    new Chart(canvas, {
      type: chartType,
      data: chartData,
      options: options
    });
  }

  // ============================================
  // NOTIFICATIONS
  // ============================================

  initNotifications() {
    // Auto-hide alerts
    document.querySelectorAll('.alert[data-auto-hide]').forEach(alert => {
      const timeout = parseInt(alert.dataset.autoHide) || 5000;
      setTimeout(() => {
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 300);
      }, timeout);
    });
  }

  showNotification(message, type = 'info', duration = 5000) {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} notification-toast`;
    notification.innerHTML = `
      <div class="flex items-center justify-between">
        <span>${message}</span>
        <button type="button" class="notification-close ml-4 text-lg">&times;</button>
      </div>
    `;

    // Add to container
    let container = document.querySelector('.notification-container');
    if (!container) {
      container = document.createElement('div');
      container.className = 'notification-container';
      document.body.appendChild(container);
    }

    container.appendChild(notification);

    // Auto-hide
    setTimeout(() => {
      notification.style.opacity = '0';
      setTimeout(() => notification.remove(), 300);
    }, duration);

    // Manual close
    notification.querySelector('.notification-close')?.addEventListener('click', () => {
      notification.remove();
    });
  }

  // ============================================
  // TOOLTIPS
  // ============================================

  initTooltips() {
    document.addEventListener('mouseenter', (e) => {
      const element = e.target.closest('[data-tooltip]');
      if (element) {
        this.showTooltip(element);
      }
    });

    document.addEventListener('mouseleave', (e) => {
      const element = e.target.closest('[data-tooltip]');
      if (element) {
        this.hideTooltip();
      }
    });
  }

  showTooltip(element) {
    const text = element.dataset.tooltip;
    const position = element.dataset.tooltipPosition || 'top';
    
    const tooltip = document.createElement('div');
    tooltip.className = `tooltip tooltip-${position}`;
    tooltip.textContent = text;
    
    document.body.appendChild(tooltip);
    
    const rect = element.getBoundingClientRect();
    const tooltipRect = tooltip.getBoundingClientRect();
    
    let top, left;
    
    switch (position) {
      case 'bottom':
        top = rect.bottom + 8;
        left = rect.left + (rect.width - tooltipRect.width) / 2;
        break;
      case 'left':
        top = rect.top + (rect.height - tooltipRect.height) / 2;
        left = rect.left - tooltipRect.width - 8;
        break;
      case 'right':
        top = rect.top + (rect.height - tooltipRect.height) / 2;
        left = rect.right + 8;
        break;
      default: // top
        top = rect.top - tooltipRect.height - 8;
        left = rect.left + (rect.width - tooltipRect.width) / 2;
    }
    
    tooltip.style.top = `${top}px`;
    tooltip.style.left = `${left}px`;
  }

  hideTooltip() {
    const tooltip = document.querySelector('.tooltip');
    if (tooltip) {
      tooltip.remove();
    }
  }

  // ============================================
  // UTILITY METHODS
  // ============================================

  debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  }

  throttle(func, limit) {
    let inThrottle;
    return function() {
      const args = arguments;
      const context = this;
      if (!inThrottle) {
        func.apply(context, args);
        inThrottle = true;
        setTimeout(() => inThrottle = false, limit);
      }
    };
  }

  // API helper methods
  async api(endpoint, options = {}) {
    const defaultOptions = {
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    };

    const config = { ...defaultOptions, ...options };

    try {
      const response = await fetch(endpoint, config);
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const contentType = response.headers.get('content-type');
      if (contentType && contentType.includes('application/json')) {
        return await response.json();
      } else {
        return await response.text();
      }
    } catch (error) {
      console.error('API request failed:', error);
      throw error;
    }
  }
}

// Initialize framework when page loads
const mhs = new MHS();

// Global helper functions
window.showNotification = (message, type, duration) => mhs.showNotification(message, type, duration);
window.openModal = (modalId) => mhs.openModal(modalId);
window.closeModal = () => mhs.closeModal();

// Export for module use
if (typeof module !== 'undefined' && module.exports) {
  module.exports = MHS;
}