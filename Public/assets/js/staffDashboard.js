let allSubmissions = [];

//Checking token
function checkTokenAndRedirect() {
    const token = localStorage.getItem('authToken');
    if (!token) {
        window.location.href = "./login.html"
    }
}

function getAuthHeaders() {
    const token = localStorage.getItem('authToken');
    return {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    };
  }

function logout() {
    alert('Logging out...');
    staffLogout();
}

async function staffLogout() {
    const authToken = localStorage.getItem('authToken'); 

    try {
        const response = await fetch('/hk-roadmap/staff/logout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken}` 
            }
        });

        const data = await response.json(); 

        if (response.ok) {
            alert(data.message); 
            localStorage.removeItem('authToken'); 
            window.location.href = 'login.html'; 
        } else {
            alert(data.message || 'Logout failed. Please try again.'); 
        }
    } catch (error) {
        console.error('Logout error:', error);
        alert('An error occurred while logging out. Please try again.');
    }
}

//popups

function toggleAccountPopup() {
    const popup = document.getElementById('accountPopup');
    if (popup) {
        popup.style.display = popup.style.display === 'block' ? 'none' : 'block';
    }
}


function toggleNotifPopup() {
    const popup = document.getElementById('notificationPopup');
    const isOpening = popup.style.display !== 'block';
    
    popup.style.display = isOpening ? 'block' : 'none';
    
    if (isOpening) {
        // Clear old notifications and show loading state
        document.getElementById('notificationList').innerHTML = 
            '<div class="loading">Loading notifications...</div>';
        
        fetchNotifications();
    }
}

function toggleProfileMenu() {
    const menu = document.getElementById('profileMenu');
    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
}

// Tabs for the tables and tabs
function showTab(tabId) {
    const tabContents = document.querySelectorAll('.tab-content');
    tabContents.forEach(content => content.style.display = 'none');

    document.getElementById(tabId).style.display = 'block';

    if (tabId === 'documents') {
        fetchDocuments();
    } else if (tabId === 'submissions') {
        fetchSubmissions();
    } else if (tabId === 'students') {
        fetchStudent();
    } else if (tabId === 'staff') {
        fetchStaff();
    }
}

function showSection(section) {
    const sectionToShow = document.getElementById(section + '-section');
    const sectionsToHide = ['home-section'];

    sectionsToHide.forEach(sec => {
        const element = document.getElementById(sec);
        if (element) {
            element.style.display = 'none';
        } else {
            console.error(`Section "${sec}" not found.`);
        }
    });

    if (sectionToShow) {
        sectionToShow.style.display = 'block';
    } else {
        console.error(`Section "${section}-section" not found.`);
    }
}
//table fetches

function fetchStudent() {
    fetch('/hk-roadmap/student/profile')
        .then(response => response.json())
        .then(data => {
            const tableBody = document.querySelector('#studentsTable tbody');
            tableBody.innerHTML = ''; 
            data.forEach(doc => {
                const row = `<tr>
                    <td>${doc.name}</td>
                    <td>${doc.email}</td>
                    <td>${doc.password}</td>
                </tr>`;
                tableBody.innerHTML += row;
            });
        })
        .catch(error => console.error('Error fetching documents:', error));
}

function fetchStaff() {
    fetch('/hk-roadmap/staff/profile', {
        headers: getAuthHeaders() // Add this line
    })
    .then(response => {
        if (response.status === 401) {
            localStorage.removeItem('authToken');
            window.location.href = '/login.html';
            return;
        }
        return response.json();
    })
    .then(data => {
        const tableBody = document.querySelector('#staffsTable tbody');
        if (!tableBody) {
            console.error('Staff table body not found');
            return;
        }
        
        tableBody.innerHTML = '';
        data.forEach(doc => {
            const row = `<tr>
                <td>${doc.staff_id}</td>
                <td>${doc.name}</td>
                <td>${doc.email}</td>
                <td>${doc.password}</td>
            </tr>`;
            tableBody.innerHTML += row;
        });
    })
    .catch(error => console.error('Error fetching staff:', error));
}

async function fetchDocuments() {
    const authToken = localStorage.getItem('authToken');
    try {
        const response = await fetch('/hk-roadmap/documents/staff', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken}`
            }
        });
        
        const data = await response.json();
        const tableBody = document.querySelector('#documentsTable tbody');
        tableBody.innerHTML = ''; 
        if (Array.isArray(data.documents)) {
            data.documents.forEach(doc => {
                const row = `
                    <tr>
                        <td>${doc.document_id}</td>
                        <td>${doc.event_id}</td>
                        <td>${doc.requirement_id}</td>
                        <td>${doc.student_id}</td>
                        <td>${doc.file_path}</td>
                        <td>${doc.upload_at}</td>
                        <td>${doc.status}</td>
                        <td>${doc.is_submitted}</td>
                        <td>${doc.submitted_at}</td>
                    </tr>`;
                tableBody.innerHTML += row;
            });
        } else {
            console.warn('No documents found in the response');
            tableBody.innerHTML = '<tr><td colspan="12">No documents found.</td></tr>';
        }
    } catch (error) {
        console.error('Error fetching documents:', error);
    }
}

function showError(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.textContent = message;
    
    const container = document.querySelector('.staff-container');
    if (container) {
        container.prepend(errorDiv);
        setTimeout(() => errorDiv.remove(), 5000);
    }
}

//view documents and search for submission

let currentDocIndex = 0;
let docItems = [];

function viewDocuments(submissionIds) {
    const popup = document.getElementById('documentPopup');
    const content = document.getElementById('documentDetails');
    
    content.innerHTML = `
        <div class="doc-tabs"></div>
        <div class="doc-content"></div>
    `;

    // Fetch documents
    Promise.all(submissionIds.map(fetchDocument))
        .then(docs => {
            docItems = docs.filter(doc => doc);
            currentDocIndex = 0;
            if (docItems.length === 0) {
                content.innerHTML = '<div class="loading">No documents found</div>';
                return;
            }
            renderDocuments();
            popup.style.display = 'block';
        });
}

async function fetchDocument(id) {
    return fetch(`/hk-roadmap/submission/detail?submission_id=${id}`)
        .then(res => res.json())
        .catch(() => null);
}

function renderDocuments() {
    const tabsContainer = document.querySelector('.doc-tabs');
    const contentContainer = document.querySelector('.doc-content');
    
    // Clear existing elements
    tabsContainer.innerHTML = '';
    contentContainer.innerHTML = '';

    // Create tabs and content
    docItems.forEach((doc, index) => {
        // Tab
        const tab = document.createElement('button');
        tab.className = `doc-tab ${index === currentDocIndex ? 'active' : ''}`;
        tab.textContent = `Document ${index + 1}`;
        tab.onclick = () => switchDocument(index);
        tabsContainer.appendChild(tab);

        // Content
        const docItem = document.createElement('div');
        docItem.className = `doc-item ${index === currentDocIndex ? 'active' : ''}`;
        
        if (doc.document_type === 'link') {
            docItem.innerHTML = `
                <a href="${doc.link_url}" class="doc-link" target="_blank">
                    ${doc.link_url}
                </a>
            `;
        } else {
            docItem.innerHTML = `
                <img src="http://localhost:8000/${doc.file_path}" 
                     alt="Document preview" 
                     class="doc-image">
            `;
        }
        
        contentContainer.appendChild(docItem);
    });
}

function switchDocument(index) {
    currentDocIndex = index;
    renderDocuments();
}

async function approveAllDocuments() {
    try {
        for (const doc of docItems) {
            await handleStatusUpdate(doc.submission_id, 'approved');
        }
        closeDocumentPopup();
        fetchSubmissions();
    } catch (error) {
        console.error('Error approving all documents:', error);
    }
}

async function rejectAllDocuments() {
    try {
        for (const doc of docItems) {
            await handleStatusUpdate(doc.submission_id, 'rejected');
        }
        closeDocumentPopup();
        fetchSubmissions();
    } catch (error) {
        console.error('Error rejecting all documents:', error);
    }
}

async function handleStatusUpdate(submissionId, status) {
    try {
        const response = await fetch(`/hk-roadmap/submission/update`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`
            },
            body: JSON.stringify({ 
                submission_id: submissionId, 
                status: status.toUpperCase()
            })
        });

        if (!response.ok) {
            throw new Error('Failed to update document status');
        }
        
        return await response.json();
    } catch (error) {
        console.error(`Error ${status} document:`, error);
        throw error;
    }
}

function closeDocumentPopup() {
    const popup = document.getElementById('documentPopup');
    popup.style.display = 'none';
    currentDocIndex = 0;
    docItems = [];
}
//submissions

async function fetchSubmissions() {
  try {
    const response = await fetch('/hk-roadmap/submission/update', {
      headers: getAuthHeaders()
    });
    const data = await response.json();
    allSubmissions = data; // Store fetched data <-- ADD THIS LINE
    renderTable(data);
  } catch (error) {
    console.error('Fetch error:', error);
    showError('Failed to load submissions.');
    renderTable([]);
  }
}


// Modified render function
function renderTable(data) {
    const tableBody = document.querySelector('#submissionsTable tbody');
    tableBody.innerHTML = '';

    if (!Array.isArray(data)) {
        console.error('Invalid data format:', data);
        tableBody.innerHTML = `
            <tr>
                <td colspan="6" class="error-message">
                    Failed to load data. Please refresh the page.
                </td>
            </tr>`;
        return;
    }

    data.forEach(group => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${group.event_name}</td>
            <td>${group.requirement_name}</td>
            <td>${group.student_name}</td>
            <td>${new Date(group.submission_date).toLocaleDateString()}</td>
            <td>${group.status}</td>
            <td>
                <button class="view-docs-btn" data-ids="${group.submission_ids}">Review</button>
            </td>
        `;
        tableBody.appendChild(row);
    });
}

//notification

const createRefreshControls = (fetchCallback, interval = 30000) => {
    let isRefreshing = false;
    let refreshInterval = null;

    async function refreshNotifications() {
        if (isRefreshing) return;
        isRefreshing = true;

        try {
            await fetchCallback();
        } catch (error) {
            console.error('Refresh failed:', error);
        } finally {
            isRefreshing = false;
        }
    }

    function startAutoRefresh() {
        refreshInterval = setInterval(refreshNotifications, interval);
    }

    function stopAutoRefresh() {
        clearInterval(refreshInterval);
    }

    return {
        start: startAutoRefresh,
        stop: stopAutoRefresh,
        refresh: refreshNotifications
    };
};

function formatDateTime(timestamp) {
    try {
        const date = new Date(timestamp);
        return isNaN(date) ? '--' : date.toLocaleString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
    } catch (e) {
        return '--';
    }
}

async function fetchNotifications() {
    try {
        notificationList.innerHTML = '<div class="loading">Loading...</div>';
        
        const response = await fetch('/hk-roadmap/notification/staff', {
            headers: getAuthHeaders(),
            cache: 'no-cache' // Prevent browser caching
        });

        if (!response.ok) throw new Error(`HTTP error! ${response.status}`);
        
        const notifications = await response.json();
        
        // Clear existing content
        let unreadCount = 0;
        notificationList.innerHTML = '';

        if (notifications.length === 0) {
            notificationList.innerHTML = '<div class="empty">No new notifications</div>';
            updateNotificationBadge(0);
            return;
        }

        // Sort notifications by date (newest first)
        const sortedNotifications = notifications.sort((a, b) => {
            const dateA = new Date(a.created_at);
            const dateB = new Date(b.created_at);
            return dateB - dateA; // Descending order
        });

        sortedNotifications.forEach(notification => {
            const notificationItem = document.createElement('div');
            notificationItem.className = `notification-item ${notification.read_notif ? '' : 'unread'}`;
            notificationItem.innerHTML = `
                <div class="notification-content">
                    <p>${notification.notification_body}</p>
                    <small>${formatDateTime(notification.created_at)}</small>
                    <button class="mark-read-btn" 
                            onclick="toggleReadStatus(${notification.notification_id}, this)"
                            data-read="${notification.read_notif ? 1 : 0}">
                        ${notification.read_notif ? 'Mark Unread' : 'Mark Read'}
                    </button>
                </div>
            `;
            notificationItem.ondblclick = () => navigateToSubmission(notification.submission_id);
            notificationList.appendChild(notificationItem);
            if (!notification.read_notif) unreadCount++;
        });

        updateNotificationBadge(unreadCount);
        
    } catch (error) {
        console.error('Notification error:', error);
        notificationList.innerHTML = '<div class="error">Failed to load notifications</div>';
    }
}

async function toggleReadStatus(notificationId, button) {
    const isRead = button.dataset.read === '1';

    try {
        const response = await fetch(`/hk-roadmap/notification/edit-staff`, {
            method: 'PUT',
            headers: {
                ...getAuthHeaders(),
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                notification_id: parseInt(notificationId),  // Ensure number type
                read: !isRead  // Send boolean value
            })
        });

        if (!response.ok) {
            const error = await response.text();
            throw new Error(`Server error: ${error}`);
        }

        button.dataset.read = isRead ? '0' : '1';
        button.textContent = isRead ? 'Mark Read' : 'Mark Unread';
        button.closest('.notification-item').classList.toggle('unread');
        
        const currentCount = parseInt(document.getElementById('notificationBadge').textContent);
        document.getElementById('notificationBadge').textContent = isRead ? 
            currentCount + 1 : 
            Math.max(currentCount - 1, 0);

    } catch (error) {
        console.error('Toggle read error:', error);
        alert('Failed to update notification status');
    }
}

function navigateToSubmission(submissionId) {
    showSection('home');
    showTab('submissions');
    
    setTimeout(() => {
        const submissionRow = document.querySelector(`[data-submission-id="${submissionId}"]`);
        if (submissionRow) {
            submissionRow.scrollIntoView({ behavior: 'smooth' });
            submissionRow.style.animation = 'highlight 1.5s';
        }
    }, 500);
}

async function markAsRead(notificationId) {
    try {
        const authHeader = 'Bearer ' + localStorage.getItem('authToken');
        const response = await fetch(`/hk-roadmap/notification/edit-staff?notification_id=${notificationId}`, {
            method: 'PUT',
            headers: {
                'Authorization': authHeader,
                'Content-Type': 'application/json'
            }
        });

        if (response.ok) {
            // Update UI immediately
            const card = document.querySelector(`[data-notification-id="${notificationId}"]`);
            if (card) {
                card.querySelector('.acknowledge-btn')?.remove();
                card.querySelector('.notification-meta').innerHTML += '<span class="read-badge">Read</span>';
            }
        }
    } catch (error) {
        console.error('Mark read error:', error);
        alert('Error updating notification');
    }
}

function updateNotificationBadge(count) {
    const badge = document.getElementById('notificationBadge');
    const numericCount = Number(count) || 0;
    
    badge.textContent = numericCount > 9 ? '9+' : numericCount;
    badge.style.display = numericCount > 0 ? 'block' : 'none';
    
    // Add aria-live for screen readers
    badge.setAttribute('aria-live', 'polite');
    badge.setAttribute('aria-atomic', 'true');
}

async function markAllAsRead() {
    try {
        const response = await fetch('/hk-roadmap/notification/staff/mark-all', {
            method: 'PUT',
            headers: getAuthHeaders()
        });

        if (!response.ok) throw new Error('Failed to mark all as read');
        
        // Update UI
        document.querySelectorAll('.notification-item').forEach(item => {
            item.classList.remove('unread');
            const button = item.querySelector('.mark-read-btn');
            if (button) {
                button.dataset.read = '1';
                button.textContent = 'Mark Unread';
            }
        });
        
        updateNotificationBadge(0);
        alert('All notifications marked as read!');

    } catch (error) {
        console.error('Error:', error);
        alert(error.message);
    }
}

const badgeRefresher = {
    init: () => {
      setInterval(() => {
        const badge = document.getElementById('notificationBadge');
        if (badge) badge.textContent = parseInt(badge.textContent) || 0;
      }, 30000);
    }
  };

document.addEventListener('DOMContentLoaded', () => {
    badgeRefresher.init();
    const refreshControls = createRefreshControls(fetchNotifications, 10000);
    refreshControls.start();
    showSection('home'); 
    showTab('documents');
    fetchDocuments();

    getAuthHeaders();

    document.querySelectorAll('.view-document-button').forEach(button => {
        button.addEventListener('click', () => viewDocument(button.getAttribute('data-id')));
    });
    document.querySelectorAll('.approve-button').forEach(button => {
        button.addEventListener('click', handleStatusUpdate);
    });
    document.querySelectorAll('.reject-button').forEach(button => {
        button.addEventListener('click', handleStatusUpdate);
    });

    document.querySelector('#submissionsTable tbody').addEventListener('click', (e) => {
        if (e.target.classList.contains('view-docs-btn')) {
            const submissionIds = e.target.dataset.ids.split(',');
            viewDocuments(submissionIds);
        }
    });

    document.getElementById('searchInput').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase().trim();
        
        const filtered = allSubmissions.filter(group => {
            const searchString = [
                group.event_name,
                group.requirement_name,
                group.student_name,
                group.status,
                new Date(group.submission_date).toLocaleDateString()
            ].join(' ').toLowerCase();
            
            return searchString.includes(searchTerm);
        });
        
        renderTable(filtered);
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.notifications-container')) {
            document.getElementById('notificationPopup').style.display = 'none';
        }
        if (!e.target.closest('.profile-container')) {
            document.getElementById('profileMenu').style.display = 'none';
        }
    });

    const accountButton = document.querySelector('.account-button');
    if (accountButton) {
        accountButton.addEventListener('click', toggleAccountPopup);
    }

    const logoutButton = document.querySelector('.popup-button');
    if (logoutButton) {
        logoutButton.addEventListener('click', logout);
    }
});