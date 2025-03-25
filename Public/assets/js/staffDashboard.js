//global variables
let currentDocIndex = 0;
let docItems = [];
let allComments = [];
let allStudents = [];
let allSubmissions = [];
let currentSortOrder = 'desc';
let currentCollapseStates = new Map();
let requirementMap = new Map();
let eventMap = new Map();
let studentMap = new Map();
let userProfile = null;
let departmentMapping = {};
let reverseDepartmentMapping = {};


//Checking token and logouts
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
function closeCommentPopup() { 
    const popup = document.getElementById('commentPopup'); 
        if (popup) { popup.style.display = 'none'; } 
}

function toggleNotifPopup() {
    const popup = document.getElementById('notificationPopup');
    const isOpening = popup.style.display !== 'block';
    
    // Toggle the display of the popup
    popup.style.display = isOpening ? 'block' : 'none';
    
    if (isOpening) {
        // Clear old notifications and show loading state
        document.getElementById('notificationList').innerHTML = 
            '<div class="loading">Loading notifications...</div>';
        
        // Fetch notifications
        fetchNotifications();
        
        // Position the popup below the notification bell
        const bell = document.querySelector('.notification-bell');
        const rect = bell.getBoundingClientRect();
        const int = 50; // Adjust this value as needed

        // Set the calculated styles
        popup.style.left = `${rect.right - popup.offsetWidth + int}px`; // Align right edge of popup with right edge of bell

        popup.classList.add('visible');
    } else {
        popup.classList.remove('visible');
    }
}

function positionNotificationPopup() {
    const popup = document.getElementById('notificationPopup');
    const bell = document.querySelector('.notification-bell');
    const rect = bell.getBoundingClientRect();
    const int = 50;
    
    // Set the position of the popup
    popup.style.left = `${rect.right - popup.offsetWidth + int}px`; // Align right edge of popup with right edge of bell
}

function toggleProfileMenu() {
    const menu = document.getElementById('profileMenu');
    const isOpening = menu.style.display !== 'block';

    // Toggle the display of the menu
    menu.style.display = isOpening ? 'block' : 'none';

    if (isOpening) {
        // Add visible class for animation
        menu.classList.add('visible');
    } else {
        // Remove visible class when closing
        menu.classList.remove('visible');
    }
}

function closeEditPopup() {
    const popup = document.getElementById('editCommentPopup');
    if (popup) {
        popup.style.display = 'none';
    }
}

// Tabs and sections
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
    showLoadingSpinner();
    const sectionToShow = document.getElementById(section + '-section');
    const sectionsToHide = ['home-section', 'profile-section', 'comments-section'];

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

//table fetches in tabs, from api
async function fetchProfiles(type) {
    try {
        const response = await fetch(`/hk-roadmap/profile/all?type=${type}`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        allStudents = data; // Store in global variable
        renderStudents(data);
        return data;
    } catch (error) {
        console.error(`Error fetching ${type} profiles:`, error);
        alert(`Failed to load ${type} data`);
        return [];
    }
}

async function fetchAllEvents() {
    try {
        const response = await fetch('/hk-roadmap/event/get', {
            headers: getAuthHeaders()
        });
        
        if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
        
        const data = await response.json();
        
        // Handle different response formats
        const events = Array.isArray(data) ? data : data.events || [];
        
        if (events.length === 0) {
            console.warn('No events found in response');
            return;
        }
        
        eventMap = new Map(
            events.map(event => [
                Number(event.event_id), 
                event.event_name || `Unnamed Event (${event.event_id})`
            ])
        );
    } catch (error) {
        console.error('Error fetching events:', error);
        showError('Failed to load event names');
        // Initialize empty map to prevent errors
        eventMap = new Map();
    }
}

async function fetchStudent() {
    const data = await fetchProfiles('student');
    const tableBody = document.querySelector('#studentsTable tbody');
    tableBody.innerHTML = '';

    studentMap = new Map(data.map(student => [
        Number(student.student_id),  // key: student_id as number
        student.name || `Student ${student.student_id}`  // value: student name
    ]));
    
    data.forEach(student => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${student.student_number || '-'}</td>
            <td>${student.name || '-'}</td>
            <td>${student.department || '-'}</td>
            <td>${student.year_level || '-'}</td>
            <td>
                <button class="view-details-btn" onclick="showProfileDetails('student', ${JSON.stringify(student).replace(/"/g, '&quot;')})">
                    View Details
                </button>
            </td>
        `;
        tableBody.appendChild(row);
    });
}

function showProfileDetails(type, profile) {
    const popup = document.getElementById('profilePopup');
    const popupContent = document.getElementById('popupContent');
    const popupTitle = document.getElementById('popupTitle');
    const closeBtn = popup.querySelector('.close-popup');

    popupTitle.textContent = `${type.charAt(0).toUpperCase() + type.slice(1)} Profile: ${profile.name}`;

    let content = `
        <div class="profile-details">
            <img src="${profile.profile_picture_url || '/assets/jpg/default-profile.png'}" 
                 alt="Profile Picture" 
                 class="profile-pict">
            <div class="profileDetails">
                <p><strong>Name:</strong> ${profile.name || '-'}</p>
                <p><strong>Email:</strong> ${profile.email || '-'}</p>
                <p><strong>Department:</strong> ${profile.department || '-'}</p>
                <p><strong>Contact Number:</strong> ${profile.contact_number || '-'}</p>
                <p><strong>Student Number:</strong> ${profile.student_number || '-'}</p>
                <p><strong>Year Level:</strong> ${profile.year_level || '-'}</p>
                <p><strong>College Program:</strong> ${profile.college_program || '-'}</p>
                <p><strong>Scholarship Type:</strong> ${profile.scholarship_type || '-'}</p>
    `;

    // Add department_others if exists
    if (profile.department_others) {
        content += `
                <p><strong>Other Department:</strong> ${profile.department_others}</p>
        `;
    }

    content += `
            </div>
        </div>
    `;

    popupContent.innerHTML = content;
    popup.style.display = "block";

    // Close popup handlers
    closeBtn.onclick = () => popup.style.display = "none";
    
    window.onclick = (event) => {
        if (event.target === popup) {
            popup.style.display = "none";
        }
    };
}

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

//view documents and search for submission ans students, locally got after get api
async function fetchDocument(id) {
    return fetch(`/hk-roadmap/submission/detail?submission_id=${id}`)
        .then(res => res.json())
        .catch(() => null);
}


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

function renderDocuments() {
    const tabsContainer = document.querySelector('.doc-tabs');
    const contentContainer = document.querySelector('.doc-content');
    
    // Clear existing elements
    tabsContainer.innerHTML = '';
    contentContainer.innerHTML = '';

    // Create tabs and content
    docItems.forEach((doc, index) => {
        let fileName = '';
        let title = '';

        // Determine the type of document and set the title accordingly
        if (doc.document_type === 'link') {
            // If it's a link, use the full URL as the title
            title = doc.link_url;
            fileName = title; // You can also use a custom name if needed
        } else {
            // For documents and images, extract the filename
            const url = new URL(doc.link_url || `http://localhost:8000/${doc.file_path}`);
            fileName = url.pathname.split('/').pop(); // Get the last part of the URL path
            title = fileName; // Use the filename as the title
        }

        // Tab
        const tab = document.createElement('button');
        tab.className = `doc-tab ${index === currentDocIndex ? 'active' : ''}`;
        tab.textContent = fileName; // Use the filename
        tab.title = title; // Set title for full name on hover
        tab.onclick = () => switchDocument(index);
        tabsContainer.appendChild(tab);

        // Content
        const docItem = document.createElement('div');
        docItem.className = `doc-item ${index === currentDocIndex ? 'active' : ''}`;
        
        if (doc.document_type === 'link') {
            docItem.innerHTML = `
                <a href="${doc.link_url}" class="doc-link" target="_blank">
                    ${title}
                </a>
            `;
        } else {
            docItem.innerHTML = `
                <img src="http://localhost:8000/${doc.file_path}" 
                     alt="${fileName}" 
                     class="doc-image">
            `;
        }
        
        contentContainer.appendChild(docItem);
    });
}

function renderStudents(students) {
    const tableBody = document.querySelector('#studentsTable tbody');
    tableBody.innerHTML = '';
    
    students.forEach(student => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${student.student_number || '-'}</td>
            <td>${student.name || '-'}</td>
            <td>${student.department || '-'}</td>
            <td>${student.year_level || '-'}</td>
            <td>
                <button class="view-details-btn" onclick="showProfileDetails('student', ${JSON.stringify(student).replace(/"/g, '&quot;')})">
                    View Details
                </button>
            </td>
        `;
        tableBody.appendChild(row);
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
async function fetchNotifications() {
    try {
        notificationList.innerHTML = '<div class="loading">Loading...</div>';
        
        const response = await fetch('/hk-roadmap/notification/staff', {
            headers: getAuthHeaders(),
            cache: 'no-cache'
        });

        if (!response.ok) throw new Error(`HTTP error! ${response.status}`);
        
        const notifications = await response.json();
        
        let unreadCount = 0;
        notificationList.innerHTML = '';

        if (notifications.length === 0) {
            notificationList.innerHTML = '<div class="empty">No new notifications</div>';
            updateNotificationBadge(0);
            return;
        }

        const sortedNotifications = notifications.sort((a, b) => {
            const dateA = new Date(a.created_at);
            const dateB = new Date(b.created_at);
            return dateB - dateA;
        });

        sortedNotifications.forEach(notification => {
            const notificationItem = document.createElement('div');
            notificationItem.className = `notification-item ${notification.read_notif ? '' : 'unread'}`;
            notificationItem.innerHTML = `
                <div class="notification-content">
                    <div class="notif-text>
                        <div class="notification-body">
                            ${notification.notification_body}
                        </div>
                        <small class="notification-date">
                            ${formatDateTime(notification.created_at)}
                        </small>
                    </div>
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

//profile
async function fetchStaffProfile() {
    showLoadingSpinner();
    try {
        const response = await fetch('/hk-roadmap/profile/get', {
            headers: getAuthHeaders()
        });

        if (response.ok) {
            const userProfile = await response.json();
            // Staff fields
            document.getElementById('staffName').value = userProfile.name || '';
            document.getElementById('staffDepartment').value = userProfile.department || '';
            document.getElementById('staffPosition').value = userProfile.position || '';
            document.getElementById('staffContact').value = userProfile.contact_number || '';
            document.getElementById('staffProfilePicture').src = userProfile.profile_picture_url || '';
                
            // Student-specific fields
            document.getElementById('studentNumber').value = userProfile.student_number || '';
            document.getElementById('collegeProgram').value = userProfile.college_program || '';
            document.getElementById('yearLevel').value = userProfile.year_level || '';
            document.getElementById('scholarshipType').value = userProfile.scholarship_type || '';
            
            updateProfileUI();
            updateNavProfile(userProfile);
            disableProfileEditing()
        } else {
            throw new Error('Failed to fetch profile');
        }
    } catch (error) {
        console.error('Error fetching profile:', error);
    }
}

async function saveProfile(inputs, editButton, saveButton) {
    const formData = new FormData();
    const fileInput = document.querySelector('input[type="file"]');

    const selectedDepartmentName = document.getElementById('staffDepartment').value;

    // Department handling
    const departmentAbbr = reverseDepartmentMapping[selectedDepartmentName] || 'OTH';

    // Common fields
    formData.append('name', document.getElementById('staffName').value);
    formData.append('department', departmentAbbr);
    formData.append('position', document.getElementById('staffPosition').value);
    formData.append('contact_number', document.getElementById('staffContact').value);
    formData.append('student_number', document.getElementById('studentNumber').value);
    formData.append('college_program', document.getElementById('collegeProgram').value);
    formData.append('year_level', document.getElementById('yearLevel').value);
    formData.append('scholarship_type', document.getElementById('scholarshipType').value);

    if (selectedDepartmentName === 'Others') {
        formData.append('department_others', document.getElementById('departmentOthers').value);
    }

    if (fileInput.files[0]) {
        formData.append('profile_picture', fileInput.files[0]);
    }

    try {
        const response = await fetch('/hk-roadmap/profile/update', {
            method: 'POST',
            headers: getAuthHeaders(),
            body: formData
        });

        const data = await response.json();
        if (response.ok) {
            alert('Profile updated successfully!');
            disableProfileEditing(inputs, editButton, saveButton);
            await fetchStaffProfile();
        } else {
            throw new Error(data.message || 'Failed to update profile');
        }
    } catch (error) {
        console.error('Error updating profile:', error);
        alert(error.message);
    }
}

function enableProfileEditing() {
    const inputs = document.querySelectorAll('#profileForm input, #profileForm select');
    inputs.forEach(input => 
        input.disabled = false
    );
    
    document.getElementById('editProfileButton').style.display = 'none';
    document.getElementById('saveProfileButton').style.display = 'block';
    document.getElementById('cancelEditButton').style.display = 'block';
    document.getElementById('changeProfilePictureButton').style.display = 'block';
}

function disableProfileEditing() {
    const inputs = document.querySelectorAll('#profileForm input, #profileForm select');
    inputs.forEach(input => {
        input.disabled = true
    });
    
    document.getElementById('editProfileButton').style.display = 'block';
    document.getElementById('saveProfileButton').style.display = 'none';
    document.getElementById('cancelEditButton').style.display = 'none';
    document.getElementById('changeProfilePictureButton').style.display = 'none';
    
    // Reset file input
    const fileInput = document.querySelector('input[type="file"]');
    if (fileInput) fileInput.value = '';
}

function populateDepartmentSelect() {
    const departmentSelect = document.getElementById('staffDepartment');
    departmentSelect.innerHTML = '<option value="">Select Department</option>';
    
    if (departments) {
        Object.entries(departments).forEach(([abbr, name]) => {
            const option = document.createElement('option');
            option.value = name;
            option.textContent = name;
            departmentSelect.appendChild(option);
        });
    }
}

function handleDepartmentChange() {
    const departmentSelect = document.getElementById('staffDepartment');
    const departmentOthersGroup = document.getElementById('departmentOthersGroup');
    const departmentOthers = document.getElementById('departmentOthers');

    if (departmentSelect.value === 'Others') {
        departmentOthersGroup.style.display = 'block';
        departmentOthers.disabled = departmentSelect.disabled;
        departmentOthers.required = true;
    } else {
        departmentOthersGroup.style.display = 'none';
        departmentOthers.required = false;
    }
}

async function fetchDepartments() {
    try {
        const response = await fetch('/hk-roadmap/profile/departments', {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`
            }
        });

        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`Failed to fetch departments: ${errorText}`);
        }

        const data = await response.json();
        departments = data.departments;

        // Create mappings
        departmentMapping = departments; // abbreviation -> full name
        reverseDepartmentMapping = Object.entries(departments).reduce((acc, [abbr, name]) => {
            acc[name] = abbr;
            return acc;
        }, {});

        return departments;
    } catch (error) {
        console.error('Error fetching departments:', error);
        return null;
    }
}

function updateNavProfile(userProfile) {
    const staffNameElement = document.getElementById('navProfileName');
    const staffPicElement = document.querySelector('.navprofile-container .profile-pic');
    
    if (staffNameElement && userProfile.name) {
        staffNameElement.textContent = userProfile.name;
    }

    if (staffPicElement && userProfile.profile_picture_url) {
        staffPicElement.src = userProfile.profile_picture_url;
    }
}

function setupProfilePictureUpload() {
    const profilePicture = document.getElementById('staffProfilePicture');
    const editButton = document.getElementById('editProfileButton');
    const saveButton = document.getElementById('saveProfileButton');
    const cancelButton = document.getElementById('cancelEditButton');
    const departmentSelect = document.getElementById('staffDepartment');
    const changeProfilePictureButton = document.getElementById('changeProfilePictureButton');

    // Create file input element
    const fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.accept = 'image/*';
    fileInput.style.display = 'none';
    document.body.appendChild(fileInput);

    changeProfilePictureButton.addEventListener('click', () => {
        fileInput.click();
    });

    let isEditMode = false;

    // Edit button click handler
    editButton.addEventListener('click', () => {
        isEditMode = true;
        profilePicture.style.cursor = 'pointer';
        document.querySelectorAll('#profileForm input, #profileForm select').forEach(input => {
            input.disabled = false;
        });
        editButton.style.display = 'none';
        saveButton.style.display = 'block';
        cancelButton.style.display = 'block';
    });

    // Cancel button click handler
    cancelButton.addEventListener('click', () => {
        isEditMode = false;
        profilePicture.style.cursor = 'default';
        document.querySelectorAll('#profileForm input, #profileForm select').forEach(input => {
            input.disabled = true;
        });
        editButton.style.display = 'block';
        saveButton.style.display = 'none';
        cancelButton.style.display = 'none';
        document.getElementById('changeProfilePictureButton').style.display = 'none';
        updateProfileUI(); 
        fetchStaffProfile();
    });

    profilePicture.addEventListener('click', () => {
        if (isEditMode) {
            fileInput.click();
        }
    });

    // Department change handler
    departmentSelect.addEventListener('change', handleDepartmentChange);

    // File selection handler
    fileInput.addEventListener('change', async (e) => {
        if (e.target.files && e.target.files?.[0]) {
            const file = e.target.files[0];
            
            if (!file.type.startsWith('image/')) {
                alert('Please select an image file');
                return;
            }

            const maxSize = 5 * 1024 * 1024; // 5MB
            if (file.size > maxSize) {
                alert('File size should be less than 5MB');
                return;
            }
            
            // Show preview
            const reader = new FileReader();
            reader.onload = function(e) {
                profilePicture.src = e.target.result;
            };
            reader.readAsDataURL(file);

            // Store for upload
            saveButton._fileToUpload = file;
        }
    });

    // Save button click handler
    saveButton.addEventListener('click', async () => {
        try {
            const formData = new FormData();
            
            if (saveButton._fileToUpload) {
                formData.append('profile_picture', saveButton._fileToUpload);
            }

            // Add form data
            formData.append('name', document.getElementById('staffName').value);
            formData.append('department', document.getElementById('staffDepartment').value);
            formData.append('position', document.getElementById('staffPosition').value);
            formData.append('contact_number', document.getElementById('staffContact').value);
            formData.append('student_number', document.getElementById('studentNumber').value);
            formData.append('college_program', document.getElementById('collegeProgram').value);
            formData.append('year_level', document.getElementById('yearLevel').value);
            formData.append('scholarship_type', document.getElementById('scholarshipType').value);
            
            if (document.getElementById('staffDepartment').value === 'Others') {
                formData.append('department_others', document.getElementById('departmentOthers').value);
            }

            const response = await fetch('/hk-roadmap/profile/update', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('authToken')}`
                },
                body: formData
            });

            if (!response.ok) {
                throw new Error('Failed to update profile');
            }

            // Reset edit mode
            isEditMode = false;
            profilePicture.style.cursor = 'default';
            document.querySelectorAll('#profileForm input, #profileForm select').forEach(input => {
                input.disabled = true;
            });
            editButton.style.display = 'block';
            saveButton.style.display = 'none';
            cancelButton.style.display = 'none';
            saveButton._fileToUpload = null;

            // Refresh profile data
            await fetchStaffProfile();
        } catch (error) {
            console.error('Error updating profile:', error);
            alert('Failed to update profile. Please try again.');
        }
    });
}

function getProfilePictureUrl(profilePicturePath) {
    if (!profilePicturePath) {
        return '/assets/jpg/default-profile.png';
    }
    return '/' + profilePicturePath;
}

async function updateProfileUI() {
    if (!departments) {
        await fetchDepartments();
        populateDepartmentSelect();
    }

    if (userProfile) {
        // Update form fields
        document.getElementById('staffName').value = userProfile.name || '';
        document.getElementById('staffPosition').value = userProfile.position || '';
        document.getElementById('staffContact').value = userProfile.contact_number || '';
        document.getElementById('studentNumber').value = userProfile.student_number || '';
        document.getElementById('collegeProgram').value = userProfile.college_program || '';
        document.getElementById('yearLevel').value = userProfile.year_level || '';
        document.getElementById('scholarshipType').value = userProfile.scholarship_type || '';

        // Department handling
        const departmentSelect = document.getElementById('staffDepartment');
        const departmentFullName = departmentMapping[userProfile.department] || 'Others';
        departmentSelect.value = departmentFullName;

        const departmentOthersGroup = document.getElementById('departmentOthersGroup');
        const departmentOthers = document.getElementById('departmentOthers');
        
        if (departmentFullName === 'Others') {
            departmentOthersGroup.style.display = 'block';
            departmentOthers.value = userProfile.department_others || '';
        }

        // Update profile pictures
        const profilePictureUrl = getProfilePictureUrl(userProfile.profile_picture_url);
        document.getElementById('staffProfilePicture').src = profilePictureUrl;
    }
}

async function fetchPrograms() {
    try {
        const response = await fetch('/hk-roadmap/profile/programs', {
            headers: getAuthHeaders()
        });

        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const data = await response.json();
        renderPrograms(data.programs);
    } catch (error) {
        console.error('Error fetching programs:', error);
        showError('Failed to load programs');
    }
}

function renderPrograms(programs) {
    const programSelect = document.getElementById('collegeProgram'); // Assuming you have a select element for programs
    programSelect.innerHTML = '<option value="">Select Program</option>'; // Clear existing options

    programs.forEach(program => {
        const option = document.createElement('option');
        option.value = program; // Assuming program is a string
        option.textContent = program; // Display program name
        programSelect.appendChild(option);
    });
}

function populateDepartmentSelect() {
    const staffSelect = document.getElementById('staffDepartment');
    
    [staffSelect].forEach(select => {
        if (select) {
            select.innerHTML = Object.values(departmentMapping)
                .map(name => `<option value="${name}">${name}</option>`)
                .join('');
        }
    });
}

//comments
async function fetchAllRequirements() {
    try {
        const response = await fetch('/hk-roadmap/requirements/get', {
            headers: getAuthHeaders()
        });
        
        if (!response.ok) throw new Error('Failed to fetch requirements');
        
        const requirements = await response.json();
        requirementMap = new Map(
            requirements.map(req => [
                req.requirement_id,
                {
                    name: req.requirement_name,
                    event_id: req.event_id // Store event ID with requirement
                }
            ])
        );
    } catch (error) {
        console.error('Error fetching requirements:', error);
        showError('Failed to load requirement names');
    }
}

function filterComments(searchTerm) {
    return allComments.filter(comment => {
        // Get student name
        const studentName = studentMap.get(comment.student_id)?.toLowerCase() || '';
        
        // Get requirement details
        const requirement = requirementMap.get(comment.requirement_id) || {};
        const requirementName = requirement.name?.toLowerCase() || '';
        
        // Get event name
        const eventId = requirement.event_id;
        const eventName = eventMap.get(eventId)?.toLowerCase() || '';

        // Create search string
        const searchString = [
            eventName,
            requirementName,
            studentName,
            comment.body.toLowerCase(),
            comment.user_name.toLowerCase()
        ].join(' ');

        return searchString.includes(searchTerm.toLowerCase());
    });
}

async function renderCommentDashboard(comments = allComments) {
    const container = document.querySelector('.comment-groups-container');
    if (!container) return;

    container.innerHTML = '';
    
    const grouped = groupComments(comments);
    
    for (const [reqId, groupData] of Object.entries(grouped)) {
        const { event_id, students } = groupData;
        const numericReqId = Number(reqId); // Define numericReqId here
        const numericEventId = Number(event_id);
        
        // Get requirement and event names
        const requirement = requirementMap.get(numericReqId);
        const eventName = eventMap.get(numericEventId);
        
        // Check if requirement and event names are valid
        if (!requirement || !eventName) {
            console.warn(`Skipping requirement ID ${numericReqId} or event ID ${numericEventId} due to missing names.`);
            continue; // Skip this iteration if names are not valid
        }

        const requirementGroup = document.createElement('div');
        requirementGroup.className = 'requirement-group';
        requirementGroup.innerHTML = `
            <h3>${eventName} - ${requirement.name}</h3>
            <div class="student-groups"></div>
        `;

        const studentGroups = requirementGroup.querySelector('.student-groups');
        renderStudentGroups(studentGroups, students, numericReqId);
        container.appendChild(requirementGroup);
    }
}


function renderStudentGroups(container, students, requirementId) {
    container.innerHTML = '';

    Object.entries(students).forEach(([studentId, comments]) => {
        
        const studentGroup = document.createElement('div');
        studentGroup.className = 'student-group collapsed';

        const sortedComments = [...comments].sort((a, b) => 
            new Date(a.created_at) - new Date(b.created_at)
        );

        // Get the oldest comment's user_name
        const oldestComment = sortedComments[0];
        const studentName = oldestComment?.user_name || `Student ${studentId}`;

        const header = document.createElement('div');
        header.className = 'student-header';
        header.innerHTML = `
            <div class="header-content">
                <span class="chevron">▶</span>
                <h4>${studentName}</h4>
                <span class="comment-count">(${comments.length} comments)</span>
            </div>
        `;

        // Create comments list container
        const commentsList = document.createElement('div');
        commentsList.className = 'comments-list';
        commentsList.style.display = 'none';

        // Populate existing comments with action menu
        comments.forEach(comment => {
            const commentCard = document.createElement('div');
            commentCard.className = 'comment-card';
            commentCard.dataset.commentId = comment.comment_id;
            commentCard.innerHTML = `
                <div class="comment-header">
                    <div class="comment-info">
                        <span class="commenter-name">${comment.user_name}</span>
                        <span class="comment-date">${new Date(comment.created_at).toLocaleDateString()}</span>
                    </div>
                    <div class="comment-actions">
                        <button class="action-menu-btn" onclick="toggleActionMenu(event, this)">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <div class="action-menu">
                            <button onclick="editComment(${comment.comment_id}, '${comment.body}')">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button onclick="deleteComment(${comment.comment_id})">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                </div>
                <p class="comment-body">${comment.body}</p>
            `;
            commentsList.appendChild(commentCard);
        });


        header.addEventListener('click', function(e) {
            const isCollapsed = studentGroup.classList.contains('collapsed');
            studentGroup.classList.toggle('collapsed');
            const chevron = this.querySelector('.chevron');
            chevron.textContent = isCollapsed ? '▼' : '▶';
            commentsList.style.display = isCollapsed ? 'block' : 'none';
            currentCollapseStates.set(studentId, !isCollapsed);
            e.stopPropagation();
        });

        const addCommentBtn = document.createElement('button');
        addCommentBtn.className = 'add-comment-btn';
        addCommentBtn.innerHTML = '<i class="fas fa-plus"></i> Add Comment';
        addCommentBtn.onclick = () => openCommentDialog(studentId, requirementId);
        
        commentsList.appendChild(addCommentBtn);
        studentGroup.appendChild(header);
        studentGroup.appendChild(commentsList);
        container.appendChild(studentGroup);
    });
}

function toggleActionMenu(event, button) {
    event.stopPropagation();
    
    // Close all other menus first
    document.querySelectorAll('.action-menu').forEach(menu => {
        if (menu !== button.nextElementSibling) {
            menu.style.display = 'none';
        }
    });
    
    // Toggle this menu
    const menu = button.nextElementSibling;
    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
}

async function initCommentManagement() {
    try {
        if (allComments.length === 0) await fetchAllComments();
        await Promise.all([
            fetchAllRequirements(),
            fetchAllEvents()
        ]);
        renderCommentDashboard(allComments);
    } catch (error) {
        console.error('Comment init error:', error);
    }
}

async function fetchAllComments() {
    try {
        const response = await fetch('/hk-roadmap/comments/all', {
            headers: getAuthHeaders()
        });
        
        if (!response.ok) throw new Error('Failed to fetch comments');
        
        allComments = await response.json();
        sortComments(currentSortOrder);
    } catch (error) {
        console.error('Error fetching comments:', error);
        showError('Failed to load comments');
    }
}

function sortComments(order) {
    allComments.sort((a, b) => {
        const dateA = new Date(a.created_at);
        const dateB = new Date(b.created_at);
        return order === 'asc' ? dateA - dateB : dateB - dateA;
    });
}

function toggleSortOrder() {
    currentSortOrder = currentSortOrder === 'asc' ? 'desc' : 'asc';
    sortComments(currentSortOrder);
    renderCommentDashboard();
}

function groupComments(comments) {
    return comments.reduce((acc, comment) => {
        try {
            const reqId = Number(comment.requirement_id);
            const studentId = Number(comment.student_id);
            
            if (!reqId || !studentId) {
                console.warn('Skipping comment with invalid IDs:', comment);
                return acc;
            }
            
            // Get event ID from requirement map
            const requirement = requirementMap.get(reqId);
            const eventId = requirement?.event_id || 0;
            
            acc[reqId] = acc[reqId] || {
                event_id: eventId,
                students: {}
            };
            
            acc[reqId].students[studentId] = acc[reqId].students[studentId] || [];
            acc[reqId].students[studentId].push(comment);
            
            return acc;
        } catch (error) {
            console.error('Error processing comment:', error, comment);
            return acc;
        }
    }, {});
}

async function fetchAllComments() {
    try {
        const response = await fetch('/hk-roadmap/comments/all', {
            headers: getAuthHeaders(),
            cache: 'no-cache'
        });

        if (!response.ok) throw new Error('Failed to fetch comments');
        
        const data = await response.json();
        
        // Validate response format
        if (!Array.isArray(data)) {
            throw new Error('Invalid comments response format');
        }

        // Filter valid comments
        allComments = data.filter(comment => 
            comment?.requirement_id &&
            comment?.student_id &&
            comment?.body
        );
        
        sortComments(currentSortOrder);
        return true;
    } catch (error) {
        console.error('Error fetching comments:', error);
        showError('Failed to load comments');
        return false;
    }
}

async function submitComment() {
    const popup = document.getElementById('commentPopup');
    const commentInput = document.getElementById('commentInput');
    const submitButton = document.getElementById('submitCommentButton');
    
    if (!popup || !commentInput || !submitButton) {
        console.error('Required elements missing');
        return;
    }

    const originalText = submitButton.innerHTML;
    submitButton.innerHTML = '<div class="spinner"></div> Submitting...';
    submitButton.disabled = true;

    try {
        const commentText = commentInput.value.trim();
        if (!commentText) {
            throw new Error('Please enter a comment');
        }

        const response = await fetch('/hk-roadmap/comments/add', {
            method: 'POST',
            headers: {
                ...getAuthHeaders(),
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                requirement_id: parseInt(popup.dataset.requirementId),
                student_id: parseInt(popup.dataset.studentId),
                body: commentText
            })
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || 'Failed to add comment');
        }

        // Refresh comments from server
        await fetchAllComments();
        renderCommentDashboard();
        closeCommentPopup();

    } catch (error) {
        console.error('Submit error:', error);
        alert(`Error: ${error.message}`);
    } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = originalText;
    }
}

function openCommentDialog(studentId, requirementId) {
    const popup = document.getElementById('commentPopup');
    if (!popup) return;

    // Clear previous input
    const commentInput = document.getElementById('commentInput');
    if (commentInput) commentInput.value = '';

    // Set current IDs
    popup.dataset.studentId = studentId;
    popup.dataset.requirementId = requirementId;
    
    // Show popup
    popup.style.display = 'block';
}

function editComment(commentId, commentBody) {
    // Create edit popup if it doesn't exist
    let editPopup = document.getElementById('editCommentPopup');
    if (!editPopup) {
        editPopup = document.createElement('div');
        editPopup.id = 'editCommentPopup';
        editPopup.className = 'popup';
        editPopup.innerHTML = `
            <div class="popup-content">
                <span class="close">&times;</span>
                <h3>Edit Comment</h3>
                <textarea id="editCommentText" 
                    placeholder="Enter your comment here..." 
                    rows="4" 
                    maxlength="500"></textarea>
                <div class="popup-buttons">
                    <button onclick="updateComment(${commentId})" class="submit-btn">Save</button>
                    <button onclick="closeEditPopup()" class="cancel-btn">Cancel</button>
                </div>
            </div>
        `;
        document.body.appendChild(editPopup);
        
        // Add close button functionality
        editPopup.querySelector('close-popup').onclick = closeEditPopup;
    }

    // Set the comment text in the textarea
    document.getElementById('editCommentText').value = decodeURIComponent(commentBody);
    editPopup.style.display = 'block';
}

async function updateComment(commentId) {
    const newText = document.getElementById('editCommentText').value.trim();
    if (!newText) {
        alert('Comment cannot be empty');
        return;
    }

    try {
        const response = await fetch('/hk-roadmap/comments/update', {
            method: 'PUT',
            headers: {
                ...getAuthHeaders(),
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                comment_id: commentId,
                body: newText
            })
        });

        if (response.ok) {
            closeEditPopup();
            await fetchAllComments();
            renderCommentDashboard();
        } else {
            throw new Error('Failed to update comment');
        }
    } catch (error) {
        console.error('Error updating comment:', error);
        alert('You can only edit your own comment');
    }
}

async function deleteComment(commentId) {
    if (!confirm('Are you sure you want to delete this comment?')) {
        return;
    }

    try {
        const response = await fetch('/hk-roadmap/comments/delete', {
            method: 'DELETE',
            headers: {
                ...getAuthHeaders(),
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                comment_id: commentId
            })
        });

        if (response.ok) {
            await fetchAllComments();
            renderCommentDashboard();
        } else {
            throw new Error('Failed to delete comment');
        }
    } catch (error) {
        console.error('Error deleting comment:', error);
        alert('You can only delete your own comments');
    }
}

//frontend system


let loadingSpinnerTimeout; // Variable to hold the timeout reference

function showLoadingSpinner(duration = 700) { // Default duration is 5000ms (5 seconds)
    const spinner = document.getElementById('loadingSpinner');
    if (spinner) {
        spinner.style.display = 'flex'; // Show the spinner
    }

    // Clear any existing timeout to prevent multiple timers
    clearTimeout(loadingSpinnerTimeout);

    // Set a timeout to hide the spinner after the specified duration
    loadingSpinnerTimeout = setTimeout(() => {
        hideLoadingSpinner();
    }, duration);
}

function hideLoadingSpinner() {
    const spinner = document.getElementById('loadingSpinner');
    if (spinner) {
        spinner.style.display = 'none'; // Hide the spinner
    }

    // Clear the timeout when hiding the spinner
    clearTimeout(loadingSpinnerTimeout);
}

function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    
    // Toggle the visible class on the sidebar
    sidebar.classList.toggle('visible');
}

function closeSidebar() {
    const sidebar = document.querySelector('.sidebar');
    sidebar.classList.remove('visible');
}


document.addEventListener('DOMContentLoaded', () => {
    try {
        badgeRefresher.init();
        const refreshControls = createRefreshControls(fetchNotifications, 10000);
        refreshControls.start();
        
        showSection('home'); 
        showTab('submissions');
        fetchSubmissions();
        

        // Search Input
        document.querySelector('#searchInput')?.addEventListener('input', function(e) {
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

        document.querySelector('.account-button')?.addEventListener('click', toggleAccountPopup);
        document.querySelector('.popup-button')?.addEventListener('click', logout);

        document.addEventListener('click', (e) => {
            const notificationPopup = document.getElementById('notificationPopup');
            const profileMenu = document.getElementById('profileMenu');
            if (!e.target.closest('.notifications-container') && notificationPopup) notificationPopup.style.display = 'none';
            if (!e.target.closest('.navprofile-container') && profileMenu) profileMenu.style.display = 'none';
        });

        const commentSearch = document.getElementById('commentSearch');
        if (commentSearch) {
            commentSearch.addEventListener('input', function(e) {
                const currentStates = new Map();
                
                // Store current collapse states using student names
                document.querySelectorAll('.student-group').forEach(group => {
                    const studentName = group.querySelector('h4')?.textContent;
                    if (studentName) currentStates.set(studentName, group.classList.contains('collapsed'));
                });

                const searchTerm = e.target.value.trim().toLowerCase();
                const filtered = searchTerm ? filterComments(searchTerm) : allComments;
                renderCommentDashboard(filtered);

                // Restore collapse states using student names
                setTimeout(() => {
                    document.querySelectorAll('.student-group').forEach(group => {
                        const studentName = group.querySelector('h4')?.textContent;
                        if (studentName && currentStates.has(studentName)) {
                            group.classList.toggle('collapsed', currentStates.get(studentName));
                        }
                    });
                }, 0);
            });
        }

        const studentSearch = document.getElementById('studentSearch');
        if (studentSearch) {
            studentSearch.addEventListener('input', function(e) {
                const searchTerm = e.target.value.toLowerCase().trim();
                const filtered = searchTerm ? 
                    allStudents.filter(student => {
                        const searchString = [
                            student.student_number || '',
                            student.name || '',
                            student.department || '',
                            student.year_level || ''
                        ].join(' ').toLowerCase();
                        return searchString.includes(searchTerm);
                    }) : 
                    allStudents;
                    
                renderStudents(filtered);
            });
        }

        const editButton = document.getElementById('editProfileButton');
        const saveButton = document.getElementById('saveProfileButton');
        const inputs = document.querySelectorAll('#profile-section input');
        
        if (editButton && saveButton) {
            editButton.addEventListener('click', function() {
                enableProfileEditing(inputs, editButton, saveButton);
            });
            
            saveButton.addEventListener('click', function() {
                saveProfile(inputs, editButton, saveButton);
            });
        }

        const submissionsTable = document.querySelector('#submissionsTable');
        if (submissionsTable) {
            submissionsTable.addEventListener('click', (e) => {
                const reviewButton = e.target.closest('.view-docs-btn');
                if (reviewButton) {
                    const submissionIds = reviewButton.dataset.ids.split(',').map(id => parseInt(id.trim()));
                    viewDocuments(submissionIds);
                }
            });
        }

        const menuButton = document.getElementById('toggleMenuButton');
     menuButton.addEventListener('click', function() {
         toggleSidebar();
     });
 
    document.addEventListener('click', function(event) {
        const sidebar = document.querySelector('.sidebar');
        const toggleButton = document.getElementById('toggleMenuButton');
        const content = document.querySelector('.content');
     
         // Check if the click was outside the sidebar and the toggle button
        if (!sidebar.contains(event.target) && !toggleButton.contains(event.target)) {
            closeSidebar();
        }
 
        toggleMenuButton.addEventListener('click', function() {
            sidebar.classList.toggle('visible');
        });
         
        document.addEventListener('click', function(event) {
            if (!sidebar.contains(event.target) && !toggleMenuButton.contains(event.target)) {
                sidebar.classList.remove('visible');
            }
        });
    });
 
     window.addEventListener('resize', () => {
         const popup = document.getElementById('notificationPopup');
         if (popup.style.display === 'block') {
             positionNotificationPopup();
         }
     });

        initCommentManagement();
        fetchDepartments().then(populateDepartmentSelect);
        fetchStaffProfile();
        setupProfilePictureUpload();
        fetchPrograms();

    } catch (error) {
        console.error('Error initializing dashboard:', error);
        const errorMessage = document.createElement('div');
        errorMessage.className = 'error-message';
        errorMessage.textContent = 'Failed to initialize dashboard. Please refresh the page.';
        document.body.prepend(errorMessage);
    }
});