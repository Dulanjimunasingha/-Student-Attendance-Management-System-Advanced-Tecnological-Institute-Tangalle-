/**
 * Custom JavaScript
 * Advanced Technological Institute of Tangalle - Attendance Management System
 */

// =====================================================
// SIDEBAR TOGGLE 
// =====================================================

document.addEventListener('DOMContentLoaded', function () {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.toggle('show');
        });

        // Close sidebar on outside click (mobile)
        document.addEventListener('click', function (event) {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !sidebarToggle.contains(event.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });
    }
});

// =====================================================
// TOAST NOTIFICATIONS
// =====================================================

function showToast(type, message) {
    const container = document.querySelector('.toast-container') || createToastContainer();
    const toast = document.createElement('div');
    toast.className = `toast show align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    container.appendChild(toast);

    const bsToast = new bootstrap.Toast(toast, { delay: 5000 });
    bsToast.show();

    toast.addEventListener('hidden.bs.toast', function () {
        toast.remove();
    });
}

function createToastContainer() {
    const container = document.createElement('div');
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    document.body.appendChild(container);
    return container;
}

// =====================================================
// SWEETALERT CONFIRMATIONS
// =====================================================

function confirmDelete(message = 'This action cannot be undone.') {
    return Swal.fire({
        title: 'Are you sure?',
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    });
}

function confirmAction(title, message, confirmText = 'Confirm') {
    return Swal.fire({
        title: title,
        text: message,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0d6efd',
        cancelButtonColor: '#6c757d',
        confirmButtonText: confirmText,
        cancelButtonText: 'Cancel'
    });
}

function showSuccess(message) {
    Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: message,
        timer: 2000,
        showConfirmButton: false,
        toast: true,
        position: 'top-end'
    });
}

function showError(message) {
    Swal.fire({
        icon: 'error',
        title: 'Error!',
        text: message,
        confirmButtonColor: '#dc3545'
    });
}

function showInfo(message) {
    Swal.fire({
        icon: 'info',
        title: 'Information',
        text: message
    });
}

function showWarning(message) {
    Swal.fire({
        icon: 'warning',
        title: 'Warning',
        text: message
    });
}

function showLoading(message = 'Loading...') {
    Swal.fire({
        title: message,
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
}

function hideLoading() {
    Swal.close();
}

// =====================================================
// AJAX HELPER
// =====================================================

async function ajaxPost(url, data, successCallback, errorCallback) {
    try {
        showLoading();

        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(data)
        });

        hideLoading();

        const result = await response.json();

        if (response.ok) {
            if (successCallback) successCallback(result);
        } else {
            if (errorCallback) errorCallback(result);
            else showError(result.message || 'An error occurred');
        }
    } catch (error) {
        hideLoading();
        if (errorCallback) errorCallback({ message: error.message });
        else showError('Network error. Please try again.');
    }
}

async function ajaxGet(url, successCallback, errorCallback) {
    try {
        const response = await fetch(url);

        if (response.ok) {
            const result = await response.text();
            if (successCallback) successCallback(result);
        } else {
            if (errorCallback) errorCallback({ status: response.status });
            else showError('An error occurred');
        }
    } catch (error) {
        if (errorCallback) errorCallback({ message: error.message });
        else showError('Network error. Please try again.');
    }
}

// =====================================================
// FORM VALIDATION
// =====================================================

function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;

    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return false;
    }
    return true;
}

function clearFormValidation(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.classList.remove('was-validated');
    }
}

// =====================================================
// ATTENDANCE HELPERS
// =====================================================

function getPercentageColor(percentage, minRequired = 75) {
    if (percentage >= minRequired) return 'success';
    if (percentage >= 50) return 'warning';
    return 'danger';
}

function updateProgressBar(elementId, percentage, minRequired = 75) {
    const progressBar = document.getElementById(elementId);
    if (progressBar) {
        progressBar.style.width = percentage + '%';
        progressBar.setAttribute('aria-valuenow', percentage);

        const colorClass = 'bg-' + getPercentageColor(percentage, minRequired);
        progressBar.className = 'progress-bar ' + colorClass;
    }
}

// =====================================================
// TABLE HELPERS
// =====================================================

function searchTable(tableId, searchInputId) {
    const input = document.getElementById(searchInputId);
    const filter = input.value.toUpperCase();
    const table = document.getElementById(tableId);
    const rows = table.getElementsByTagName('tr');

    for (let i = 1; i < rows.length; i++) {
        const cells = rows[i].getElementsByTagName('td');
        let found = false;

        for (let j = 0; j < cells.length; j++) {
            if (cells[j]) {
                const textValue = cells[j].textContent || cells[j].innerText;
                if (textValue.toUpperCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
        }

        rows[i].style.display = found ? '' : 'none';
    }
}

function sortTable(tableId, column, direction = 'asc') {
    const table = document.getElementById(tableId);
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));

    rows.sort((a, b) => {
        const aValue = a.cells[column].textContent.trim();
        const bValue = b.cells[column].textContent.trim();

        if (direction === 'asc') {
            return aValue.localeCompare(bValue);
        } else {
            return bValue.localeCompare(aValue);
        }
    });

    rows.forEach(row => tbody.appendChild(row));
}

// =====================================================
// DATE HELPERS
// =====================================================

function formatDate(dateString, format = 'YYYY-MM-DD') {
    const date = new Date(dateString);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    if (format === 'DD/MM/YYYY') {
        return `${day}/${month}/${year}`;
    } else if (format === 'MM/DD/YYYY') {
        return `${month}/${day}/${year}`;
    }

    return `${year}-${month}-${day}`;
}

function getToday() {
    return formatDate(new Date());
}

function getDateRange(startDate, endDate) {
    const dates = [];
    const current = new Date(startDate);
    const end = new Date(endDate);

    while (current <= end) {
        dates.push(formatDate(current));
        current.setDate(current.getDate() + 1);
    }

    return dates;
}

// =====================================================
// NUMBER FORMATTING
// =====================================================

function formatNumber(num, decimals = 2) {
    return parseFloat(num).toFixed(decimals);
}

function formatPercentage(value) {
    return formatNumber(value, 1) + '%';
}

// =====================================================
// EXPORT HELPERS
// =====================================================

function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    const rows = table.querySelectorAll('tr');
    const csv = [];

    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const rowData = Array.from(cols).map(col => {
            let text = col.innerText.replace(/"/g, '""');
            return '"' + text + '"';
        });
        csv.push(rowData.join(','));
    });

    const csvContent = csv.join('\n');
    const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);

    link.setAttribute('href', url);
    link.setAttribute('download', filename + '.csv');
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// =====================================================
// DELETE HANDLER
// =====================================================

document.addEventListener('click', async function (e) {
    if (e.target.classList.contains('delete-btn') || e.target.closest('.delete-btn')) {
        e.preventDefault();
        const btn = e.target.classList.contains('delete-btn') ? e.target : e.target.closest('.delete-btn');
        const url = btn.getAttribute('href') || btn.dataset.url;
        const message = btn.dataset.message || 'This action cannot be undone.';

        const result = await confirmDelete(message);
        if (result.isConfirmed) {
            window.location.href = url;
        }
    }
});

// =====================================================
// FORM SUBMISSION HANDLER
// =====================================================

document.addEventListener('submit', function (e) {
    if (e.target.classList.contains('ajax-form')) {
        e.preventDefault();

        const form = e.target;
        const url = form.action;
        const formData = new FormData(form);
        const data = {};

        formData.forEach((value, key) => {
            data[key] = value;
        });

        ajaxPost(url, data,
            function (result) {
                showSuccess(result.message || 'Operation successful!');
                if (result.redirect) {
                    setTimeout(() => window.location.href = result.redirect, 1500);
                }
                if (result.modal) {
                    bootstrap.Modal.getInstance(document.querySelector(result.modal)).hide();
                }
            },
            function (result) {
                showError(result.message || 'An error occurred');
            }
        );
    }
});

// =====================================================
// AUTO DISMISS ALERTS
// =====================================================

document.addEventListener('DOMContentLoaded', function () {
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});

// =====================================================
// ATTENDANCE CHECKBOXES
// =====================================================

function checkAllPresent(selectAllCheckbox) {
    const checked = selectAllCheckbox.checked;
    const form = selectAllCheckbox.closest('form');
    const checkboxes = form.querySelectorAll('input[name="attendance[]"]');

    checkboxes.forEach(function (checkbox) {
        if (checked) {
            checkbox.checked = true;
            updateAttendanceRow(checkbox);
        }
    });
}

function updateAttendanceRow(checkbox) {
    const row = checkbox.closest('tr');
    const badge = row.querySelector('.attendance-badge');

    if (checkbox.checked) {
        badge.textContent = 'Present';
        badge.className = 'badge bg-success attendance-badge';
    } else {
        badge.textContent = 'Absent';
        badge.className = 'badge bg-danger attendance-badge';
    }
}

// =====================================================
// STUDENT SEARCH
// =====================================================

async function searchStudent(query) {
    if (query.length < 3) return;

    const results = document.getElementById('searchResults');
    if (!results) return;

    results.innerHTML = '<div class="text-center p-3"><div class="spinner-border spinner-border-sm" role="status"></div> Searching...</div>';

    try {
        const response = await fetch(`../api/search_student.php?q=${encodeURIComponent(query)}`);
        const data = await response.json();

        if (data.length > 0) {
            let html = '<ul class="list-group">';
            data.forEach(student => {
                html += `
                    <li class="list-group-item">
                        <a href="../student/view.php?id=${student.id}" class="text-decoration-none">
                            <strong>${student.student_id}</strong> - ${student.full_name}
                        </a>
                    </li>
                `;
            });
            html += '</ul>';
            results.innerHTML = html;
        } else {
            results.innerHTML = '<div class="p-3 text-muted">No students found</div>';
        }
    } catch (error) {
        results.innerHTML = '<div class="p-3 text-danger">Search error</div>';
    }
}

// =====================================================
// PRINT FUNCTION
// =====================================================

function printPage() {
    window.print();
}

// =====================================================
// PASSWORD TOGGLE
// =====================================================

document.addEventListener('DOMContentLoaded', function () {
    const togglePassword = document.querySelectorAll('.toggle-password');

    togglePassword.forEach(function (btn) {
        btn.addEventListener('click', function () {
            const input = document.querySelector(this.dataset.target);
            const type = input.type === 'password' ? 'text' : 'password';
            input.type = type;

            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
    });
});
