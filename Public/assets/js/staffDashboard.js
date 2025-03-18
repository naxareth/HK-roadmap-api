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

function closeCommentPopup() { 
    const popup = document.getElementById('commentPopup'); 
        if (popup) { popup.style.display = 'none'; } 
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

//table fetches

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
        return data;
    } catch (error) {
        console.error(`Error fetching ${type} profiles:`, error);
        alert(`Failed to load ${type} data`);
        return [];
    }
}

async function fetchStudent() {
    const data = await fetchProfiles('student');
    const tableBody = document.querySelector('#studentsTable tbody');
    tableBody.innerHTML = '';
    
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
    const closeBtn = popup.querySelector('.close');

    popupTitle.textContent = `${type.charAt(0).toUpperCase() + type.slice(1)} Profile: ${profile.name}`;

    let content = `
        <div class="profile-details">
            <img src="${profile.profile_picture_url || '/assets/jpg/default-profile.png'}" 
                 alt="Profile Picture" 
                 class="profile-picture">
            <div class="details-grid">
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

//profile
let userProfile = null;
let departmentMapping = {};
let reverseDepartmentMapping = {};

function updateNavProfile(profileData) {
    // Update navigation bar profile elements
    const navProfileName = document.getElementById('navProfileName');
    const navProfileImage = document.querySelector('.profile-container .profile-pic');
    
    if (navProfileName && profileData.name) {
        navProfileName.textContent = profileData.name;
    }
    
    if (navProfileImage && profileData.profile_picture_url) {
        navProfileImage.src = '/' + profileData.profile_picture_url;
    }
}

async function fetchStaffProfile() {
    try {
        const response = await fetch('/hk-roadmap/profile/get', {
            headers: getAuthHeaders()
        });

        if (response.ok) {
            const profileData = await response.json();
            // Staff fields
            document.getElementById('staffName').value = profileData.name || '';
            document.getElementById('staffEmail').value = profileData.email || '';
            document.getElementById('staffDepartment').value = profileData.department || '';
            document.getElementById('staffPosition').value = profileData.position || '';
            document.getElementById('staffContact').value = profileData.contact_number || '';
            document.getElementById('staffProfilePicture').src = profileData.profile_picture_url || '';
                
            // Student-specific fields
            document.getElementById('studentNumber').value = profileData.student_number || '';
            document.getElementById('collegeProgram').value = profileData.college_program || '';
            document.getElementById('yearLevel').value = profileData.year_level || '';
            document.getElementById('scholarshipType').value = profileData.scholarship_type || '';
            
            updateProfileUI();
            updateNavProfile(profileData);
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
    formData.append('email', document.getElementById('staffEmail').value);
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
    inputs.forEach(input => input.disabled = false);
    
    document.getElementById('editProfileButton').style.display = 'none';
    document.getElementById('saveProfileButton').style.display = 'block';
    document.getElementById('cancelEditButton').style.display = 'block';
}

function disableProfileEditing() {
    const inputs = document.querySelectorAll('#profileForm input, #profileForm select');
    inputs.forEach(input => input.disabled = true);
    
    document.getElementById('editProfileButton').style.display = 'block';
    document.getElementById('saveProfileButton').style.display = 'none';
    document.getElementById('cancelEditButton').style.display = 'none';
}

function populateDepartmentSelect() {
    const departmentSelect = document.getElementById('staffDepartment');
    departmentSelect.innerHTML = '<option value="">Select Department</option>';
    
    if (departments) {
        // Use full names in the dropdown
        Object.entries(departments).forEach(([abbr, name]) => {
            const option = document.createElement('option');
            option.value = name; // Use full name as value
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

function updateNavProfile(profileData) {
    const staffNameElement = document.getElementById('navProfileName');
    const staffPicElement = document.querySelector('.profile-container .profile-pic');
    
    if (staffNameElement && profileData.name) {
        staffNameElement.textContent = profileData.name;
    }

    if (staffPicElement && profileData.profile_picture_url) {
        staffPicElement.src = '/' + profileData.profile_picture_url;
    }
}

function setupProfilePictureUpload() {
    const profilePicture = document.getElementById('staffProfilePicture');
    const editButton = document.getElementById('editProfileButton');
    const saveButton = document.getElementById('saveProfileButton');
    const cancelButton = document.getElementById('cancelEditButton');
    const departmentSelect = document.getElementById('staffDepartment');

    // Create file input element
    const fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.accept = 'image/*';
    fileInput.style.display = 'none';
    document.body.appendChild(fileInput);

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
        updateProfileUI(); 
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
            formData.append('email', document.getElementById('staffEmail').value);
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
        document.getElementById('staffEmail').value = userProfile.email || '';
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
        document.getElementById('headerProfilePic').src = profilePictureUrl;
        
        // Update navigation profile
        updateNavProfile(userProfile);
    }
}

//comments
let allComments = [];
let currentSortOrder = 'desc';

function filterComments(searchTerm) {
    return allComments.filter(comment => {
        const searchString = [
            comment.requirement_id.toString(),
            comment.student_id.toString(),
            comment.body.toLowerCase(),
            comment.user_name.toLowerCase()
        ].join(' ');
        
        return searchString.includes(searchTerm.toLowerCase());
    });
}

function renderCommentDashboard(comments = allComments) {
    const container = document.querySelector('.comment-groups-container');
    if (!container) return;

    container.innerHTML = '';
    
    const grouped = groupComments(comments);
    Object.entries(grouped).forEach(([reqId, students]) => {
        const requirementGroup = document.createElement('div');
        requirementGroup.className = 'requirement-group';
        requirementGroup.innerHTML = `
            <h3>Requirement ID: ${reqId}</h3>
            <div class="student-groups"></div>
        `;
        
        const studentGroups = requirementGroup.querySelector('.student-groups');
        renderStudentGroups(studentGroups, students);
        container.appendChild(requirementGroup);
    });
}

function renderStudentGroups(container, students) {
    container.innerHTML = '';

    Object.entries(students).forEach(([studentId, comments]) => {
        const studentGroup = document.createElement('div');
        studentGroup.className = 'student-group collapsed';

        // Create collapsible header
        const header = document.createElement('div');
        header.className = 'student-header';
        header.innerHTML = `
            <div class="header-content">
                <span class="chevron">▶</span>
                <h4>Student ID: ${studentId}</h4>
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

        // Toggle collapse functionality
        header.addEventListener('click', function(e) {
            const isCollapsed = studentGroup.classList.contains('collapsed');
            studentGroup.classList.toggle('collapsed');
            const chevron = this.querySelector('.chevron');
            chevron.textContent = isCollapsed ? '▼' : '▶';
            commentsList.style.display = isCollapsed ? 'block' : 'none';
            currentCollapseStates.set(studentId, !isCollapsed);
            e.stopPropagation();
        });

        studentGroup.appendChild(header);
        studentGroup.appendChild(commentsList);
        container.appendChild(studentGroup);
    });
}

// Toggle action menu
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
        if (allComments.length === 0) {
            await fetchAllComments();
        }
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
    if (!Array.isArray(comments)) {
        console.error('Invalid comments data:', comments);
        return {};
    }

    return comments.reduce((acc, comment) => {
        try {
            // Validate comment structure
            if (!comment || typeof comment !== 'object') {
                console.warn('Skipping invalid comment format');
                return acc;
            }

            // Validate required fields
            const reqId = Number(comment.requirement_id);
            const studentId = Number(comment.student_id);
            const body = comment.body?.toString().trim();
            
            if (!reqId || !studentId || !body) {
                console.warn('Skipping incomplete comment:', comment);
                return acc;
            }

            // Initialize structure
            acc[reqId] = acc[reqId] || {};
            acc[reqId][studentId] = acc[reqId][studentId] || [];
            
            // Add valid comment
            acc[reqId][studentId].push({
                ...comment,
                requirement_id: reqId,
                student_id: studentId,
                body: body
            });
        } catch (error) {
            console.error('Error processing comment:', error);
        }
        return acc;
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

function openCommentDialog(studentId, requirementId) {
    const popup = document.getElementById('commentPopup') || createCommentPopup();
    
    // Set data attributes for reference
    popup.dataset.studentId = studentId;
    popup.dataset.requirementId = requirementId;
    
    // Clear previous input
    const commentInput = document.getElementById('commentInput');
    if (commentInput) {
        commentInput.value = '';
    }
    
    popup.style.display = 'block';

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
        editPopup.querySelector('.close').onclick = closeEditPopup;
    }

    // Set the comment text in the textarea
    document.getElementById('editCommentText').value = decodeURIComponent(commentBody);
    editPopup.style.display = 'block';
}

// Close edit popup
function closeEditPopup() {
    const popup = document.getElementById('editCommentPopup');
    if (popup) {
        popup.style.display = 'none';
    }
}

// Update comment
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
        alert('Failed to update comment');
    }
}

// Delete comment
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
        alert('Failed to delete comment');
    }
}

let currentCollapseStates = new Map();

//safe add event listener

function safeAddEventListener(selector, event, handler) {
    const element = document.querySelector(selector);
    if (element) {
        element.addEventListener(event, handler);
    } else {
        console.warn(`Element with selector "${selector}" not found`);
    }
}

// Utility function to safely add multiple event listeners
function safeAddEventListeners(selector, event, handler) {
    const elements = document.querySelectorAll(selector);
    if (elements.length > 0) {
        elements.forEach(element => element.addEventListener(event, handler));
    } else {
        console.warn(`No elements found with selector "${selector}"`);
    }
}

// Add missing populateDepartmentSelect function
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

document.addEventListener('DOMContentLoaded', () => {
    try {
        // Initialize core functionality
        badgeRefresher.init();
        const refreshControls = createRefreshControls(fetchNotifications, 10000);
        refreshControls.start();
        showSection('home'); 
        showTab('submissions');
        fetchSubmissions();
        populateDepartmentSelect();

        // Safe event listener attachments
        safeAddEventListener('#searchInput', 'input', function(e) {
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

        // Document click handler for popups
        document.addEventListener('click', (e) => {
            const notificationPopup = document.getElementById('notificationPopup');
            const profileMenu = document.getElementById('profileMenu');

            if (!e.target.closest('.notifications-container') && notificationPopup) {
                notificationPopup.style.display = 'none';
            }
            if (!e.target.closest('.profile-container') && profileMenu) {
                profileMenu.style.display = 'none';
            }
        });

        // Comments section initialization
        const commentSearch = document.getElementById('commentSearch');
        if (commentSearch) {
            commentSearch.addEventListener('input', function(e) {
                const currentStates = new Map();
                document.querySelectorAll('.student-group').forEach(group => {
                    const studentId = group.querySelector('h4')?.textContent.split(': ')[1];
                    if (studentId) {
                        currentStates.set(studentId, group.classList.contains('collapsed'));
                    }
                });

                const searchTerm = e.target.value.trim().toLowerCase();
                const filtered = searchTerm ? filterComments(searchTerm) : allComments;
                renderCommentDashboard(filtered);

                // Re-apply collapse states
                setTimeout(() => {
                    document.querySelectorAll('.student-group').forEach(group => {
                        const studentId = group.querySelector('h4')?.textContent.split(': ')[1];
                        if (studentId && currentStates.has(studentId)) {
                            group.classList.toggle('collapsed', currentStates.get(studentId));
                        }
                    });
                }, 0);
            });
        }

        // Initialize profiles and comments
        initCommentManagement();

        const editButton = document.getElementById('editProfileButton');
        const saveButton = document.getElementById('saveProfileButton');
        const inputs = document.querySelectorAll('#profile-section input');

        editButton.addEventListener('click', function() {
            enableProfileEditing(inputs, editButton, saveButton);
        });

        saveButton.addEventListener('click', function() {
            saveProfile(inputs, editButton, saveButton);
        });

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

        // Safe button event listeners
        safeAddEventListener('.account-button', 'click', toggleAccountPopup);
        safeAddEventListener('.popup-button', 'click', logout);


    } catch (error) {
        console.error('Error initializing dashboard:', error);
        // Show user-friendly error message
        const errorMessage = document.createElement('div');
        errorMessage.className = 'error-message';
        errorMessage.textContent = 'Failed to initialize dashboard. Please refresh the page.';
        document.body.prepend(errorMessage);
    }

    
    fetchDepartments().then(populateDepartmentSelect);
    fetchStaffProfile();
    setupProfilePictureUpload();
});