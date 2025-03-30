//store global variables
let allAdmins = [];
let allStudents = [];
let allStaff = [];

//Checking token
function checkTokenAndRedirect() {
    const token = localStorage.getItem('authToken');
    if (!token) {
        window.location.href = "./login.html"
    }
}

function getAuthHeaders() {
    checkTokenAndRedirect();
    const token = localStorage.getItem('authToken');
    return {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${token}`
    };
}

// All Popups and Toggles
function toggleAccountPopup() {
    const popup = document.getElementById('accountPopup');
    if (popup) {
        popup.style.display = popup.style.display === 'block' ? 'none' : 'block';
    }
}

function toggleEventPopup() {
    const popup = document.getElementById('eventPopup');
    if (popup) {
        popup.style.display = popup.style.display === 'block' ? 'none' : 'block';
    }
}

function toggleRequirementPopup() {
    const popup = document.getElementById('requirementPopup');
    if (popup) {
        popup.style.display = popup.style.display === 'block' ? 'none' : 'block';
    }
}

function toggleNotifPopup() {
    const popup = document.getElementById('notificationPopup');
    const isOpening = popup.style.display !== 'block';
    
    popup.style.display = isOpening ? 'block' : 'none';
    
    if (isOpening) {
        document.getElementById('notificationList').innerHTML = 
            '<div class="loading">Loading notifications...</div>';
        
        fetchNotifications();
        
        const bell = document.querySelector('.notification-bell');
        const rect = bell.getBoundingClientRect();
        const int = 50;

        popup.style.left = `${rect.right - popup.offsetWidth + int}px`; 

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

    menu.style.display = isOpening ? 'block' : 'none';

    if (isOpening) {
        menu.classList.add('visible');
    } else {
        menu.classList.remove('visible');
    }
}

function toggleEditAnnouncementPopup() {
    const popup = document.getElementById('editAnnouncementPopup');
    if (popup) {
        popup.style.display = popup.style.display === 'block' ? 'none' : 'block';
    }
}

function toggleMenu(event) { 
    const menu = event.target.closest('.card-menu').querySelector('.menu-options'); 
    menu.style.display = menu.style.display === 'block' ? 'none' : 'block'; 
}

function closeMenuOptions() {
    const menus = document.querySelectorAll('.menu-options');
    menus.forEach(menu => {
        menu.style.display = 'none';
    });
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

// Tabs for the tables and tabs
function showTab(tabId) {
    const tabContents = document.querySelectorAll('.tab-content');
    tabContents.forEach(content => content.style.display = 'none');

    document.getElementById(tabId).style.display = 'block';

    if (tabId === 'submissions') {
        fetchSubmissions();
    } else if (tabId === 'students') {
        fetchStudents();
    } else if (tabId === 'admins') {
        fetchAdmins();
    } else if (tabId === 'staffs') {
        fetchStaff();
    }
}

function showSection(section) {
    showLoadingSpinner();
    const sectionToShow = document.getElementById(section + '-section');
    const sectionsToHide = ['home-section', 'admin-section', 'announce-section', 'profile-section'];

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
    closeSidebar();
}

function showOnBoardingScreens() {
    const welcomeScreen = document.getElementById("welcome-screen");
    const mainContent = document.getElementById("main-content");
    const continueButton = document.getElementById("continue-button");
    const nextButtons = document.querySelectorAll(".next-button");
    const prevButtons = document.querySelectorAll(".prev-button");
    const onboardingSteps = document.querySelectorAll(".onboarding-step");

    if (localStorage.getItem("hasSeenWelcomeScreen")) {
        welcomeScreen.style.display = "none";
        mainContent.style.display = "block";
    } else {
        welcomeScreen.style.display = "flex";
    }

    function showStep(stepId) {
        onboardingSteps.forEach(step => {
            step.classList.remove('active');
            step.style.display = 'none';
        });
        const activeStep = document.getElementById(stepId);
        activeStep.classList.add('active');
        activeStep.style.display = 'block';
    }

    nextButtons.forEach(button => {
        button.addEventListener("click", function() {
            const nextStepId = this.getAttribute("data-next");
            showStep(nextStepId);
        });
    });

    prevButtons.forEach(button => {
        button.addEventListener("click", function() {
            const prevStepId = this.getAttribute("data-prev");
            showStep(prevStepId);
        });
    });

    continueButton.addEventListener("click", function() {
        localStorage.setItem("hasSeenWelcomeScreen", "true");
        welcomeScreen.style.display = "none";
        mainContent.style.display = "block";
    });
}

// admin logout, start of fetching functions for database interaction and modifications
async function adminLogout() {
    const authToken = localStorage.getItem('authToken'); 

    try {
        const response = await fetch('/hk-roadmap/admin/logout', {
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

async function fetchStudents() {
    const data = await fetchProfiles('student');
    allStudents = data;
    renderStudents(data);
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

async function fetchStaff() {
    const data = await fetchProfiles('staff');
    allStaff = data;
    renderStaff(data);
}

function renderStaff(staff) {
    const tableBody = document.querySelector('#staffsTable tbody');
    tableBody.innerHTML = '';
    
    staff.forEach(staff => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${staff.name || '-'}</td>
            <td>${staff.position || '-'}</td>
            <td>${staff.department || '-'}</td>
            <td>
                <button class="view-details-btn" onclick="showProfileDetails('staff', ${JSON.stringify(staff).replace(/"/g, '&quot;')})">
                    View Details
                </button>
            </td>
        `;
        tableBody.appendChild(row);
    });
}

async function fetchAdmins() {
    const data = await fetchProfiles('admin');
    allAdmins = data;
    renderAdmins(data);
}

function renderAdmins(admins) {
    const tableBody = document.querySelector('#adminsTable tbody');
    tableBody.innerHTML = '';
    
    admins.forEach(admin => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${admin.name || '-'}</td>
            <td>${admin.position || '-'}</td>
            <td>${admin.department || '-'}</td>
            <td>
                <button class="view-details-btn" onclick="showProfileDetails('admin', ${JSON.stringify(admin).replace(/"/g, '&quot;')})">
                    View Details
                </button>
            </td>
        `;
        tableBody.appendChild(row);
    });
}

// Unified profile popup display function
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
            <div class="details-grid">
                <div class="details-section">
                    <h3>Personal Information</h3>
                    <p><strong>Name:</strong> ${profile.name || '-'}</p>
                    <p><strong>Email:</strong> ${profile.email || '-'}</p>
                    <p><strong>Contact:</strong> ${profile.contact_number || '-'}</p>
    `;

    if (type === 'admin') {
        content += `
                    <p><strong>Position:</strong> ${profile.position || '-'}</p>
                    <p><strong>Department:</strong> ${profile.department || '-'}</p>
        `;
    }

    if (type === 'student') {
        content += `
                    <p><strong>Department:</strong> ${profile.department || '-'}</p>
                </div>
                <div class="details-section">
                    <h3>Academic Information</h3>
                    <p><strong>Student Number:</strong> ${profile.student_number || '-'}</p>
                    <p><strong>Year Level:</strong> ${profile.year_level || '-'}</p>
                    <p><strong>Program:</strong> ${profile.college_program || '-'}</p>
                    <p><strong>Scholarship:</strong> ${profile.scholarship_type || 'None'}</p>
        `;
    }

    if (type === 'staff') {
        content += `
                    <p><strong>Position:</strong> ${profile.position || '-'}</p>
                    <p><strong>Department:</strong> ${profile.department || '-'}</p>
                </div>
                <div class="details-section">
                    <h3>Additional Information</h3>
                    <p><strong>Student Number:</strong> ${profile.student_number || '-'}</p>
                    <p><strong>Year Level:</strong> ${profile.year_level || '-'}</p>
                    <p><strong>Program:</strong> ${profile.college_program || '-'}</p>
                    <p><strong>Scholarship:</strong> ${profile.scholarship_type || 'N/A'}</p>
                    <div class="scholarship-note">
                        Note: Scholarship information is optional for staff members
                    </div>
        `;
    }

    if (profile.department_others) {
        content += `
                <p><strong>Other Department:</strong> ${profile.department_others}</p>
        `;
    }

    content += `
                </div>
            </div>
        </div>
    `;

    popupContent.innerHTML = content;
    popup.style.display = "flex";

    // Close popup handlers
    closeBtn.onclick = () => popup.style.display = "none";
}

function fetchSubmissions() {
    fetch('/hk-roadmap/submission/update')
        .then(response => response.json())
        .then(data => {
            const tableBody = document.querySelector('#submissionsTable tbody');
            tableBody.innerHTML = '';
            data.forEach(group => {
                const row = `
                    <tr>
                        <td>${group.event_name}</td>
                        <td>${group.requirement_name}</td>
                        <td>${group.student_name}</td>
                        <td>${new Date(group.submission_date).toLocaleDateString()}</td>
                        <td>${group.status}</td>
                        <td>
                            <button class="view-docs-btn" data-ids="${group.submission_ids}">Review</button>
                        </td>
                    </tr>
                `;
                tableBody.innerHTML += row;
            });

            document.querySelectorAll('.view-docs-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const submissionIds = btn.dataset.ids.split(',');
                    viewDocuments(submissionIds); 
                });
            });
        });
}

//view documents and functions for submissions

let currentDocIndex = 0;
let docItems = [];

function viewDocuments(submissionIds) {
    const popup = document.getElementById('documentPopup');
    const content = document.getElementById('documentDetails');
    
    content.innerHTML = `
        <div class="doc-tabs"></div>
        <div class="doc-content"></div>
    `;

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
    
    tabsContainer.innerHTML = '';
    contentContainer.innerHTML = '';

    docItems.forEach((doc, index) => {
        let fileName = '';
        let title = '';

        if (doc.document_type === 'link') {
            title = doc.link_url;
            fileName = title; 
        } else {
            const url = new URL(doc.link_url || `http://localhost:8000/${doc.file_path}`);
            fileName = url.pathname.split('/').pop(); 
            title = fileName;
        }

        // Tab
        const tab = document.createElement('button');
        tab.className = `doc-tab ${index === currentDocIndex ? 'active' : ''}`;
        tab.textContent = fileName; 
        tab.title = title; 
        tab.onclick = () => switchDocument(index);
        tabsContainer.appendChild(tab);

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

function switchDocument(index) {
    currentDocIndex = index;
    renderDocuments();
}

// Function to show loading spinner with forced reflow
function showLoadingSpinnerForced() {
    // Create the loading overlay if it doesn't exist
    let overlay = document.getElementById('loadingSpinner');
    
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'loadingSpinner';
        overlay.className = 'loading-overlay';
        
        const spinner = document.createElement('div');
        spinner.className = 'spinner';
        
        overlay.appendChild(spinner);
        document.body.appendChild(overlay);
    } else {
        overlay.style.display = 'flex'; // Use flex to center the spinner
    }
    
    // Force a reflow to ensure the spinner appears immediately
    void overlay.offsetWidth;
}

// Function to close popup immediately
function closeDocumentPopupImmediately() {
    const popup = document.getElementById('documentPopup');
    popup.style.display = 'none';
    currentDocIndex = 0;
    docItems = [];
}

// Improved approve all documents function
function approveAllDocuments() {
    // Store documents locally before closing popup
    const documentsToProcess = [...docItems];
    
    // Show loading spinner immediately with forced reflow
    showLoadingSpinnerForced();
    
    // Close popup immediately for better UX
    closeDocumentPopupImmediately();
    
    // Process documents in background
    (async () => {
        try {
            for (const doc of documentsToProcess) {
                await handleStatusUpdate(doc.submission_id, 'approved');
            }
            // Fetch updated submissions after processing
            fetchSubmissions();
        } catch (error) {
            console.error('Error approving documents:', error);
            hideLoadingSpinner();
            alert('Error approving documents. Please try again.');
        }
    })();
}

// Improved reject all documents function
function rejectAllDocuments() {
    // Store documents locally before closing popup
    const documentsToProcess = [...docItems];
    
    // Show loading spinner immediately with forced reflow
    showLoadingSpinnerForced();
    
    // Close popup immediately for better UX
    closeDocumentPopupImmediately();
    
    // Process documents in background
    (async () => {
        try {
            for (const doc of documentsToProcess) {
                await handleStatusUpdate(doc.submission_id, 'rejected');
            }
            // Fetch updated submissions after processing
            fetchSubmissions();
        } catch (error) {
            console.error('Error rejecting documents:', error);
            hideLoadingSpinner();
            alert('Error rejecting documents. Please try again.');
        }
    })();
}

// Keep the original handleStatusUpdate function
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

// Original close function (kept for compatibility)
function closeDocumentPopup() {
    const popup = document.getElementById('documentPopup');
    popup.style.display = 'none';
    currentDocIndex = 0;
    docItems = [];
}

// Improved fetchSubmissions function
function fetchSubmissions() {
    fetch('/hk-roadmap/submission/update')
        .then(response => response.json())
        .then(data => {
            allSubmissions = data; 
            renderTable(data);
            hideLoadingSpinner();
        })
        .catch(error => {
            console.error('Error fetching submissions:', error);
            hideLoadingSpinner();
            alert('Failed to fetch submissions. Please try again.');
        });
}

// Modified render function
function renderTable(data) {
    const tableBody = document.querySelector('#submissionsTable tbody');
    tableBody.innerHTML = '';
    
    data.forEach(group => {
        const row = `
            <tr>
                <td>${group.event_name}</td>
                <td>${group.requirement_name}</td>
                <td>${group.student_name}</td>
                <td>${new Date(group.submission_date).toLocaleDateString()}</td>
                <td>${group.status}</td>
                <td>
                    <button class="view-docs-btn" data-ids="${group.submission_ids}">Review</button>
                </td>
            </tr>
        `;
        tableBody.innerHTML += row;
    });

    document.querySelectorAll('.view-docs-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const submissionIds = btn.dataset.ids.split(',');
            viewDocuments(submissionIds);
        });
    });
}


// others, misc, etc
async function initializeYearDropdown() {
    const authToken = localStorage.getItem('authToken');
    const yearSelect = document.getElementById('yearSelect');

    try {
        const response = await fetch('/hk-roadmap/event/get', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken}`
            }
        });

        if (!response.ok) throw new Error('Failed to fetch events');
        const data = await response.json();

        yearSelect.innerHTML = `
            <option value="most_recent" selected>Most Recent</option>
            <option value="oldest">Oldest</option>
        `;

        fetchCardEvents('most_recent');

    } catch (error) {
        console.error('Error initializing dropdown:', error);
    }
}

//cards
async function fetchCardEvents(selectedFilter) {
    showLoadingSpinner();
    const authToken = localStorage.getItem('authToken');
    const eventSection = document.getElementById('event-section');
    const createEvent = document.getElementById('event-create');
    const yearSelector = document.getElementById('yearSelectorContainer');

    try {
        const response = await fetch('/hk-roadmap/event/get', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken}`
            }
        });

        if (!response.ok) throw new Error(`Error: ${response.status} ${response.statusText}`);
        let data = await response.json();

        if (selectedFilter === 'most_recent') {
            data = data.sort((a, b) => b.event_id - a.event_id);
        } else if (selectedFilter === 'oldest') {
            data = data.sort((a, b) => a.event_id - b.event_id);
        }

        const existingCards = eventSection.querySelectorAll('.card');
        existingCards.forEach(card => card.remove());

        const tempContainer = document.createDocumentFragment();
        
        if (data.length > 0) {
            data.forEach(doc => {
                const formattedDate = new Date(doc.date).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });

                const card = `
                    <div class="card" data-event-id="${doc.event_id}" data-date="${doc.date}">
                        <div class="text-content">
                            <br>
                            <h2> <i class="fas fa-image icon"></i> ${doc.event_name}</h2>
                            <br>
                            <p style="color: black;" font-weight="700"> <i class="fas fa-calendar-alt icon"></i> ${formattedDate}</p>
                        </div>
                        <div class="button-content">
                            <button class="show-requirements shwn-btn">Show Requirements</button>
                            <button class="edit-event shwn-btn" data-event-id="${doc.event_id}">Edit</button>
                            <button class="delete-event shwn-btn" data-event-id="${doc.event_id}">Delete</button>
                        </div>
                    </div>
                `;
                tempContainer.appendChild(document.createRange().createContextualFragment(card));
            });

            eventSection.appendChild(tempContainer);
            eventSection.style.display = 'grid';
        } else {
            eventSection.innerHTML += '<p>No events found</p>';
        }

        createEvent.style.display = 'block';
        yearSelector.style.display = 'block';

    } catch (error) {
        console.error('Error fetching events:', error);
    }
}

async function showRequirements(eventId) {
    const authToken = localStorage.getItem('authToken');
    document.getElementById('eventId').value = eventId;

    showLoadingSpinner();
    
    try {
        const response = await fetch(`/hk-roadmap/requirements/add?event_id=${eventId}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken}`
            }
        });

        if (!response.ok) throw new Error('Network response was not ok');

        const data = await response.json();
        const backButton = document.getElementById('backToEventsBtn');
        const requirementsSection = document.getElementById('requirements-section');

        if (Array.isArray(data)) {
            const requirementCards = requirementsSection.querySelectorAll('.card');
            requirementCards.forEach(card => card.remove());
            const requirementMessage = requirementsSection.querySelectorAll('p');
            requirementMessage.forEach(p => p.remove())

            document.getElementById('yearSelectorContainer').style.display = 'none';

            document.getElementById('yearSelect').style.display = 'none';
            document.getElementById('yearSelected').style.display = 'none';


            data.forEach(req => {    
                console.log('Identifications:', req.requirement_id, req.event_id); 
                const requirement = `
                    <div class="card" data-requirement-id="${req.requirement_id}" data-event-id="${req.event_id}">
                        <div class="text-content">
                            <h2> <i class="fas fa-flag icon"></i>Requirement: ${req.requirement_name}</h2>
                            <p><strong>Description:</strong> ${req.requirement_desc}</p>
                            <br>
                            <p><strong>Due:</strong> ${req.due_date}</p>
                        </div>
                        <div class="button-content">
                            <button class="edit-requirement shwn-btn" data-requirement-id="${req.requirement_id}">Edit</button>
                            <button class="delete-requirement shwn-btn" data-requirement-id="${req.requirement_id}" aria-label="Delete requirement ${req.requirement_id}">Delete</button>
                            <button class="show-comments shwn-btn" data-requirement-id="${req.requirement_id}">Show Comments</button>
                        </div>
                    </div>`;
                requirementsSection.insertAdjacentHTML('beforeend', requirement); 
            });
        } else {
            const requirementCards = requirementsSection.querySelectorAll('.card');
            requirementCards.forEach(card => card.remove());

            const requirementMessage = requirementsSection.querySelectorAll('p');
            requirementMessage.forEach(p => p.remove())


            const noRequirementMessage = document.createElement('p');
            noRequirementMessage.textContent = "No requirements as of now...";
            noRequirementMessage.classList.add('no-requirements-message')
            requirementsSection.appendChild(noRequirementMessage)
        }

        if (backButton) {
            backButton.style.display = 'block';
            backButton.parentElement.style.display = 'block'; 
        }

        document.getElementById('event-section').style.display = 'none';
        document.getElementById('event-create').style.display = 'none';
        document.getElementById('yearSelect').style.display = 'none';
        document.getElementById('yearSelected').style.display = 'none';
        requirementsSection.style.display = 'grid';
        backButton.style.display = 'block'; 

    } catch (error) {
        console.error('Error fetching requirements:', error);
    }
}

// Function to group events by month
function groupEventsByMonth(events) {
    const groupedEvents = {};

    events.forEach(event => {
        const eventDate = new Date(event.date);
        const monthYear = eventDate.toLocaleString('default', { month: 'long', year: 'numeric' }); // e.g., "January 2023"

        if (!groupedEvents[monthYear]) {
            groupedEvents[monthYear] = [];
        }
        groupedEvents[monthYear].push(event);
    });

    return groupedEvents;
}

// Event CRUD functions
async function updateEvent(eventId, eventData) {
    showLoadingSpinner();
    const authToken = localStorage.getItem('authToken');

    console.log(eventId, eventData);
    try {
        const response = await fetch(`/hk-roadmap/event/edit?event_id=${eventId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken}`
            },
            body: JSON.stringify(eventData)
        });

        console.log(response)

        const data = await response.json();
        console.log(data)
        if (response.ok) {
            alert('Event updated successfully!');
            fetchCardEvents(eventData.year);
            return true;
        } else {
            throw new Error(data.message || 'Failed to update event');
        }
    } catch (error) {
        console.error('Error updating event:', error);
        return false;
    }
}

async function createEvent(eventData) {
    const authToken = localStorage.getItem('authToken');
    showLoadingSpinner();
    console.log(eventId, eventData);
    try {
        const response = await fetch(`/hk-roadmap/event/add`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken}`
            },
            body: JSON.stringify(eventData)
        });

        console.log(response)

        const data = await response.json();
        console.log(data)
        if (response.ok) {
            alert('Event created successfully!');
            fetchCardEvents(eventData.year);
            sendEventEmails(eventData.event_name, eventData.date);
            return true;
        } else {
            throw new Error(data.message || 'Failed to create event');
        }
    } catch (error) {
        console.error('Error create event:', error);
        alert(error.message);

        return false;
    }
}

async function saveEvent() {
    showLoadingSpinner();
    const eventId = document.getElementById('eventId').value;
    const eventDate = document.getElementById('eventDate').value;
    const selectedYear = document.getElementById('yearSelect').value;
    const selectedDate = new Date(eventDate);
    const currentDate = new Date();

    currentDate.setHours(0, 0, 0, 0);

    if (selectedDate < currentDate) {
        alert("The event date cannot be in the past. Please select a valid date.");
        return; 
    }
    const eventData = {
        event_id: eventId,
        event_name: document.getElementById('eventName').value,
        date: document.getElementById('eventDate').value,
        year: selectedYear
    };

    if (eventId) {
        await updateEvent(eventId, eventData);
    } else {
        await createEvent(eventData);
    }
    toggleEventPopup();
}

async function loadEventForEdit(eventId) {
    const authToken = localStorage.getItem('authToken'); 
    showLoadingSpinner();

    try {
        const response = await fetch(`/hk-roadmap/event/edit?event_id=${eventId}`, { 
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken}`
            }
        });

        const data = await response.json(); 
        console.log('Event data:', data); 

        if (response.ok) {
            const eventData = data[0]; 
            if (eventData.event_id !== undefined) {
                document.getElementById('eventId').value = eventData.event_id;
            } else {
                console.warn('Event ID is undefined');
            }

            if (eventData.event_name !== undefined) {
                document.getElementById('eventName').value = eventData.event_name;
            } else {
                console.warn('Event name is undefined');
            }

            if (eventData.date) {
                const dateParts = eventData.date.split('-'); 
                const formattedDate = `${dateParts[1]}/${dateParts[2]}/${dateParts[0]}`; 
                document.getElementById('eventDate').value = formattedDate; 
            } else {
                console.warn('Date is undefined, setting to default date.');
                const defaultDate = new Date().toISOString().split('T')[0]; 
                document.getElementById('eventDate').value = defaultDate; 
            }
            toggleEventPopup();  
        } else {
            throw new Error(data.message || 'Failed to load event data'); 
        }
    } catch (error) {
        console.error('Error loading event:', error); 
    }
}

async function deleteEvent(eventId) {
    const authToken = localStorage.getItem('authToken');
    const selectedYear = document.getElementById('yearSelect').value;
    showLoadingSpinner();

    try {
        const response = await fetch(`/hk-roadmap/event/delete?event_id=${eventId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken}`
            }
        });

        const data = await response.json();

        if (response.ok) {
            alert('Event deleted successfully!');
            fetchCardEvents(selectedYear);
        } else {
            throw new Error(data.message || 'Failed to delete event');
        }
    } catch (error) {
        console.error('Error deleting event:', error);
    }
}

//event and require notif students
async function sendEventEmails(eventName, eventDate) {
    try {
        const studentsResponse = await fetch('/hk-roadmap/student/emails', {
            headers: getAuthHeaders()
        });
        
        if (!studentsResponse.ok) {
            console.error('Failed to fetch student emails');
            return;
        }

        const emails = await studentsResponse.json(); 
        
        emails.forEach(async (email) => { 
            try {
                await fetch('/hk-roadmap/mail/send', {
                    method: 'POST',
                    headers: getAuthHeaders(),
                    body: JSON.stringify({
                        recipient: email,
                        subject: "New Event Created",
                        body: `A new event has been created: ${eventName}. Date: ${eventDate}.`
                    })
                });
            } catch (emailError) {
                console.error('Failed to send email to', email, emailError);
            }
        });

    } catch (error) {
        console.error('Email sending failed:', error);
    }
}

async function sendRequirementEmails(requirementName, dueDate) {
    try {
        const studentsResponse = await fetch('/hk-roadmap/student/emails', {
            headers: getAuthHeaders()
        });
        
        if (!studentsResponse.ok) {
            console.error('Failed to fetch student emails');
            return;
        }

        const emails = await studentsResponse.json();
        
        const formattedDueDate = new Date(dueDate).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });

        emails.forEach(async (email) => {
            try {
                await fetch('/hk-roadmap/mail/send', {
                    method: 'POST',
                    headers: getAuthHeaders(),
                    body: JSON.stringify({
                        recipient: email,
                        subject: "New Requirement Added",
                        body: `A new requirement has been created: ${requirementName}. Due Date: ${formattedDueDate}.`
                    })
                });
            } catch (emailError) {
                console.error('Failed to send email to', email, emailError);
            }
        });

    } catch (error) {
        console.error('Requirement email sending failed:', error);
    }
}

// Requirement CRUD functions
async function updateRequirement(requirementId, requirementData) {
    console.log('Creating requirement with data:', requirementId, requirementData);
    const eventId = document.getElementById('eventId').value;
    requirementData.event_id = eventId;
    showLoadingSpinner();

    try {
        const response = await fetch(`/hk-roadmap/requirements/edit?requirement_id=${requirementId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`
            },
            body: JSON.stringify(requirementData)
        });

        if (!response.ok) {
            const errorText = await response.text();
            console.error('Error response:', errorText);
            throw new Error(`Failed to update requirement: ${response.status} ${response.statusText}`);
        }

        const data = await response.json();
        console.log('Updated requirement:', data);
        if (data) {
            alert('Requirement updated successfully!');
            showRequirements(eventId);
            return true;
        }
    } catch (error) {
        console.error('Error updating requirement:', error);
        alert(error.message);
        return false;
    }
}

async function createRequirement(requirementData) {
    console.log('Creating requirement with data:', requirementData);
    const authToken = localStorage.getItem('authToken');

    showLoadingSpinner();

    try {
        const response = await fetch(`/hk-roadmap/requirements/add`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken}`
            },
            body: JSON.stringify(requirementData)
        });

        const data = await response.json();

        if (response.ok) {
            alert('Requirement created successfully!');
            showRequirements(requirementData.event_id);
            
            // Send emails to students
            sendRequirementEmails(
                requirementData.requirement_name,
                requirementData.due_date
            );
            
            return true;
        } else {
            throw new Error(data.message || 'Failed to create requirement');
        }
    } catch (error) {
        console.error('Error creating requirement:', error);
        alert(error.message);
        return false;
    }
}

async function saveRequirement() {
    const requirementId = document.getElementById('requirementId').value;
    const eventId = document.getElementById('eventId').value;
    const requirementDate = document.getElementById('dueDate').value;
    const selectedDate = new Date(requirementDate);
    const currentDate = new Date();
    showLoadingSpinner();

    currentDate.setHours(0, 0, 0, 0);

    if (selectedDate < currentDate) {
        alert("The event date cannot be in the past. Please select a valid date.");
        return; 
    }

    const requirementData = {
        event_id: eventId,
        requirement_id: requirementId,
        requirement_name: document.getElementById('requirementName').value,
        requirement_desc: document.getElementById('requirementDescription').value,
        due_date: document.getElementById('dueDate').value,
    };

    if (requirementId) {
        await updateRequirement(requirementId, requirementData);
    } else {
        await createRequirement(requirementData);
    }
    toggleRequirementPopup();
}

async function loadRequirementForEdit(requirementId) {
    const authToken = localStorage.getItem('authToken'); 

    try {
        const response = await fetch(`/hk-roadmap/requirements/edit?requirement_id=${requirementId}`, { 
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken}`
            }
        });

        const data = await response.json(); 
        console.log('Requirement data:', data); 

        if (!response.ok) {
            const errorText = await response.text();
            console.error('Error response:', errorText);
            throw new Error(`Failed to load requirement data: ${response.status} ${response.statusText}`);
        }

        if (response.ok) {
            const requirementData = data[0]; 
            if (requirementData.requirement_id !== undefined) {
                document.getElementById('requirementId').value = requirementData.requirement_id;
            } else {
                console.warn('Requirement ID is undefined');
            }

            if (requirementData.requirement_name !== undefined) {
                document.getElementById('requirementName').value = requirementData.requirement_name;
            } else {
                console.warn('Requirement name is undefined');
            }

            if (requirementData.requirement_desc !== undefined) {
                document.getElementById('requirementDescription').value = requirementData.requirement_desc;
            } else {
                console.warn('Requirement Description is undefined');
            }

            if (requirementData.due_date) {
                const dateParts = requirementData.due_date.split('-'); 
                const formattedDate = `${dateParts[1]}/${dateParts[2]}/${dateParts[0]}`; 
                document.getElementById('dueDate').value = formattedDate; 
            } else {
                console.warn('Due date is undefined, setting to default date.');
                const defaultDate = new Date().toISOString().split('T')[0]; 
                document.getElementById('dueDate').value = defaultDate; 
            }
            toggleRequirementPopup();
        } else {
            console.warn('No requirement data found');
            alert('No requirement data found for the given ID.');
        }
    } catch (error) {
        console.error('Error loading requirement:', error);
        alert('Failed to load requirement data');
    }
}

async function deleteRequirement(requirementId) {
    const authToken = localStorage.getItem('authToken');
    const eventID = document.getElementById('eventId').value;
    showLoadingSpinner();

    try {
        const deleteResponse = await fetch(`/hk-roadmap/requirements/delete?requirement_id=${requirementId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken}`
            }
        });

        const data = await deleteResponse.json(); 
        console.log('Requirement data:', data);

        if (deleteResponse.ok) {
            alert('Requirement deleted successfully!');
            showRequirements(eventID);
        } else {
            throw new Error(data.message || 'Failed to delete event');
        }

    } catch (error) {
        console.error('Error deleting event:', error);
        alert(error.message);
    }
}

//sub-CRUD requirements, comments
function filterComments() {
    const searchTerm = document.getElementById('search-input').value.toLowerCase().trim();
    const studentGroups = document.querySelectorAll('.student-comment-group');

    studentGroups.forEach(group => {
        const comments = group.querySelectorAll('.comment-card');
        let hasVisibleComments = false;

        comments.forEach(comment => {
            const body = comment.querySelector('.comment-body').textContent.toLowerCase();
            const author = comment.querySelector('.comment-author').textContent.toLowerCase();
            const matches = body.includes(searchTerm) || author.includes(searchTerm);
            
            comment.style.display = matches ? 'block' : 'none';
            if (matches) hasVisibleComments = true;
        });

        group.style.display = hasVisibleComments ? 'block' : 'none';
        

        const container = group.querySelector('.comments-container');
        if (hasVisibleComments) {
            container.style.display = 'block';
            group.querySelector('.toggle-icon').textContent = '▼';
        }
    });
}
const commentCollapseStates = new Map();

async function showComments(requirementId) {
    showLoadingSpinner();
    try {
        currentRequirementId = requirementId;
        const authToken = localStorage.getItem('authToken');
        const response = await fetch(`/hk-roadmap/comments/admin?requirement_id=${requirementId}`, {
            headers: { 'Authorization': `Bearer ${authToken}` }
        });
        
        if (!response.ok) throw new Error('Failed to fetch comments');
        const comments = await response.json();

        const commentsList = document.getElementById('comments-list');
        commentsList.innerHTML = '';

        // Always navigate to comments section, even if empty
        document.getElementById('requirements-section').style.display = 'none';
        document.getElementById('comment-section').style.display = 'grid';
        document.getElementById('backtoRequirements').style.display = 'block';
        document.getElementById('backToEventsButton').style.display = 'none';

        if (comments.length === 0) {
            // Display a message in the comments section instead of an alert
            const emptyMessage = document.createElement('div');
            emptyMessage.className = 'empty-comments-message';
            emptyMessage.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-comments-alt" style="font-size: 48px; color: #ccc; margin-bottom: 15px;"></i>
                    <h3>No comments yet</h3>
                    <p>There are no comments for this requirement.</p>
                    <button id="addFirstCommentBtn" class="primary-button">Add First Comment</button>
                </div>
            `;
            commentsList.appendChild(emptyMessage);
            
            // Add event listener to the "Add First Comment" button
            document.getElementById('addFirstCommentBtn').addEventListener('click', () => {
                // You'll need to determine which student to associate this with
                // For now, we'll use a prompt to get the student ID
                const studentId = prompt("Enter student ID for this comment:");
                if (studentId) {
                    openCommentPopup(studentId, requirementId);
                }
            });
            
            hideLoadingSpinner();
            return;
        }

        const commentsByStudent = comments.reduce((groups, comment) => {
            const key = comment.student_id;
            if (!groups[key]) groups[key] = [];
            groups[key].push(comment);
            return groups;
        }, {});

        Object.entries(commentsByStudent).forEach(([studentId, studentComments]) => {
            const studentGroup = document.createElement('div');
            studentGroup.className = 'student-comment-group';
            
            // Create collapsible header
            const header = document.createElement('div');
            header.className = 'student-header';
            header.innerHTML = `
                <div class="header-content">
                    <span class="toggle-icon">▶</span>
                    <h4>${studentComments[0].user_name}</h4>
                    <span class="comment-count">${studentComments.length} comments</span>
                </div>
            `;

            // Create comments container
            const commentsContainer = document.createElement('div');
            commentsContainer.className = 'comments-container';
            commentsContainer.style.display = 'none';

            header.addEventListener('click', () => {
                const isCollapsed = commentsContainer.style.display === 'none';
                commentsContainer.style.display = isCollapsed ? 'block' : 'none';
                header.querySelector('.toggle-icon').textContent = isCollapsed ? '▼' : '▶';
                commentCollapseStates.set(studentId, !isCollapsed);
            });

            if (commentCollapseStates.has(studentId)) {
                const isExpanded = commentCollapseStates.get(studentId);
                commentsContainer.style.display = isExpanded ? 'block' : 'none';
                header.querySelector('.toggle-icon').textContent = isExpanded ? '▼' : '▶';
            }

            studentComments.forEach(comment => {
                const commentDate = new Date(comment.created_at).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                const commentCard = `
                    <div class="comment-card" data-comment-id="${comment.comment_id}">
                        <div class="comment-header">
                            <div class="comment-meta">
                                <div class="comment-author-header">
                                    <span class="comment-author">${comment.user_name}</span>
                                    <span class="comment-date">${commentDate}</span>
                                </div>
                            </div>
                            <div class="comment-actions">
                                <button class="menu-button" onclick="toggleCommentMenu(this)">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <div class="action-menu">
                                    <button onclick="handleEditComment('${comment.comment_id}')">Edit</button>
                                    <button onclick="handleDeleteComment('${comment.comment_id}')">Delete</button>
                                </div>
                            </div>
                        </div>
                        <p class="comment-body">${comment.body}</p>
                    </div>`;
                commentsContainer.insertAdjacentHTML('beforeend', commentCard);
            });

            const addButton = document.createElement('button');
            addButton.className = 'add-comment-button';
            addButton.textContent = 'Add Comment';
            addButton.onclick = () => openCommentPopup(studentId, requirementId);
            commentsContainer.appendChild(addButton);

            studentGroup.appendChild(header);
            studentGroup.appendChild(commentsContainer);
            commentsList.appendChild(studentGroup);
        });

        hideLoadingSpinner();
    } catch (error) {
        hideLoadingSpinner();
        console.error('Error loading comments:', error);
        alert('Failed to load comments');
    }
}

let currentEditCommentId = null;


function toggleCommentMenu(button) {
    const menu = button.closest('.comment-actions').querySelector('.action-menu');
    const allMenus = document.querySelectorAll('.action-menu');
    
    // Close all other menus
    allMenus.forEach(m => m !== menu ? m.style.display = 'none' : null);
    
    // Toggle current menu
    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
}

async function handleEditComment(commentId) {
    try {
        // Fetch existing comment content
        const authToken = localStorage.getItem('authToken');
        const response = await fetch(`/hk-roadmap/comments/id?comment_id=${commentId}`, {
            headers: { 'Authorization': `Bearer ${authToken}` }
        });
        
        if (!response.ok) throw new Error('Failed to fetch comment');
        const comment = await response.json();

        // Populate modal
        currentEditCommentId = commentId;
        document.getElementById('editCommentText').value = comment.body;
        document.getElementById('editCommentModal').style.display = 'block';
    } catch (error) {
        console.error('Error fetching comment:', error);
        alert('Failed to load comment for editing');
    }
}

async function submitCommentEdit() {
    if (!currentEditCommentId) return;

    try {
        // Show loading spinner
        showLoadingSpinner();
        
        const authToken = localStorage.getItem('authToken');
        const response = await fetch('/hk-roadmap/comments/update', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken}`
            },
            body: JSON.stringify({
                comment_id: currentEditCommentId,
                body: document.getElementById('editCommentText').value
            })
        });

        if (response.ok) {
            const commentBody = document.querySelector(
                `.comment-card[data-comment-id="${currentEditCommentId}"] .comment-body`
            );
            if (commentBody) {
                commentBody.textContent = document.getElementById('editCommentText').value;
            }
            closeEditModal();
            await showComments(currentRequirementId);
            // Note: No need to hide spinner here as showComments() already handles it
        } else {
            hideLoadingSpinner(); // Hide spinner on error
            throw new Error('Failed to update comment');
        }
    } catch (error) {
        hideLoadingSpinner(); // Hide spinner on error
        console.error('Error updating comment:', error);
        alert('Failed to update comment');
    }
}

function closeEditModal() {
    document.getElementById('editCommentModal').style.display = 'none';
    currentEditCommentId = null;
    document.getElementById('editCommentText').value = '';
}

function openCommentPopup(studentId, requirementId) {
    const popup = document.getElementById('commentPopup');
    popup.dataset.studentId = studentId;
    popup.dataset.requirementId = requirementId;
    popup.style.display = 'block';
}
  
function closeCommentPopup() {
    const popup = document.getElementById('commentPopup');
    popup.style.display = 'none';
    popup.dataset.studentId = '';
    document.getElementById('commentInput').value = '';
}
  
function submitComment() {
    const popup = document.getElementById('commentPopup');
    const studentId = popup.dataset.studentId;
    const requirementId = popup.dataset.requirementId;
    const commentText = document.getElementById('commentInput').value.trim();

    if (commentText) {
        // Close the popup immediately to improve perceived performance
        closeCommentPopup();
        
        // Show loading spinner IMMEDIATELY before any async operations
        showLoadingSpinner();
        
        // Use setTimeout with 0ms delay to ensure the spinner renders
        // before the potentially heavy network operation begins
        setTimeout(() => {
            fetch('/hk-roadmap/comments/add', {
                method: 'POST',
                headers: getAuthHeaders(),
                body: JSON.stringify({
                    requirement_id: requirementId,
                    student_id: studentId,
                    body: commentText
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showComments(requirementId);
                    // Note: showComments already handles the spinner
                } else {
                    hideLoadingSpinner();
                    alert('Failed to add comment: ' + data.message);
                }
            })
            .catch(error => {
                hideLoadingSpinner();
                console.error('Error:', error);
                alert('Failed to add comment');
            });
        }, 0);
    } else {
        closeCommentPopup();
    }
}

async function handleDeleteComment(commentId) {
    if (!confirm('Are you sure you want to delete this comment?')) return;
    
    try {
        const authToken = localStorage.getItem('authToken');
        const response = await fetch(`/hk-roadmap/comments/delete`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken}`
            },
            body: JSON.stringify({ comment_id: commentId })
        });

        if (response.ok) {
            document.querySelector(`.comment-card[data-comment-id="${commentId}"]`).remove();
            await showComments(currentRequirementId);
        } else {
            throw new Error('Failed to delete comment');
        }
    } catch (error) {
        console.error('Delete comment error:', error);
        alert('Failed to delete comment');
    }
}

//notifications
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
        
        const response = await fetch('/hk-roadmap/notification/get', {
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

async function toggleReadStatus(notificationId, button, status, requirementName) {
    const isRead = button.dataset.read === '1';

    try {
        const response = await fetch(`/hk-roadmap/notification/edit`, {
            method: 'PUT',
            headers: {
                ...getAuthHeaders(),
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                notification_id: parseInt(notificationId),  
                read: !isRead, 
                status: status,  
                requirement_name: requirementName  
            })
        });

        if (!response.ok) {
            const error = await response.text();
            console.error('Response error:', error); // Log the full error response
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
    showSection('admin');
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
        const response = await fetch(`/hk-roadmap/notification/edit?notification_id=${notificationId}`, {
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
        const response = await fetch('/hk-roadmap/notification/mark', {
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

//annoucement
async function fetchAnnouncements() {
    showLoadingSpinner();
    try {
        const response = await fetch('/hk-roadmap/announcements/get', {
            headers: getAuthHeaders()
        });
        
        const data = await response.json();
        const container = document.getElementById('announcementContainer');
        container.innerHTML = '';

        if (data.announcements && data.announcements.length > 0) {
            data.announcements.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
            
            data.announcements.forEach(announcement => {
                const card = `
                    <div class="two-card announcement-card" data-id="${announcement.announcement_id}">
                        <div class="upper-announce">
                            <div class="card-author"><strong>Posted by:</strong> ${announcement.author_name}</div>
                            <div class="announce-button-content">
                                <div class="card-menu">
                                    <button class="menu-button">⋯</button>
                                    <div class="menu-options">
                                        <button class="edit-announcement">Edit</button>
                                        <button class="delete-announcement">Delete</button>
                                    </div>
                                </div>
                            </div>
                            <div class="text-content">
                                <div class="card-title">
                                    <h3><strong>${announcement.title}</strong></h3>
                                    <div class="card-meta">
                                        <div class="card-date">
                                            <strong>${new Date(announcement.created_at).toLocaleDateString()}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="lower-announce">
                            <div class="text-content">
                                <div class="card-content">
                                    <p>${announcement.content.replace(/\n/g, '<br>')}</p>
                                </div>
                            </div>
                        </div>
                    </div>`;
                container.insertAdjacentHTML('beforeend', card);
            });
            
            addAnnouncementEventListeners();
        } else {
            container.innerHTML = '<p>No announcements found</p>';
        }
    } catch (error) {
        console.error('Error fetching announcements:', error);
    }
}

function addAnnouncementEventListeners() {
    document.querySelectorAll('.menu-button').forEach(button => {
        button.addEventListener('click', toggleMenu);
    });

    document.querySelectorAll('.delete-announcement').forEach(button => {
        button.addEventListener('click', handleAnnouncementDelete);
    });

    document.querySelectorAll('.edit-announcement').forEach(button => {
        button.addEventListener('click', handleAnnouncementEdit);
    });
}

function handleAnnouncementDelete(event) {
    const card = event.target.closest('.announcement-card');
    const id = card.dataset.id;
    
    if (confirm('Are you sure you want to delete this announcement?')) {
        fetch('/hk-roadmap/announcements/delete', {
            method: 'DELETE',
            headers: getAuthHeaders(),
            body: JSON.stringify({ id })
        }).then(fetchAnnouncements);
    }
}

async function handleAnnouncementEdit(event) {
    const card = event.target.closest('.announcement-card');
    const id = card.dataset.id;
    const title = card.querySelector('h3').innerText;
    const content = card.querySelector('p').innerText; 

    document.getElementById('editAnnouncementTitle').value = title;
    document.getElementById('editAnnouncementContent').value = content; 
    document.getElementById('editAnnouncementId').value = id;
    
    toggleEditAnnouncementPopup();
}

// frontend systems and etc
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

//profile
let adminProfile = null;
let departmentMapping = {};
let reverseDepartmentMapping = {};

async function fetchAdminProfile() {
    showLoadingSpinner();
    try {
        const response = await fetch('/hk-roadmap/profile/get', {
            headers: getAuthHeaders()
        });

        if (response.ok) {
            const adminProfile = await response.json();
            document.getElementById('adminName').value = adminProfile.name || '';
            document.getElementById('adminDepartment').value = adminProfile.department || '';
            document.getElementById('adminPosition').value = adminProfile.position || '';
            document.getElementById('adminContact').value = adminProfile.contact_number || '';
            document.getElementById('adminProfilePicture').src = adminProfile.profile_picture_url || '';
            updateProfileUI();
            updateNavProfile(adminProfile);
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
    const departmentSelect = document.getElementById('adminDepartment');
    
    const selectedDepartmentName = departmentSelect.value;
    const departmentAbbr = reverseDepartmentMapping[selectedDepartmentName] || 'OTH';

    formData.append('name', document.getElementById('adminName').value);
    formData.append('department', departmentAbbr);
    formData.append('position', document.getElementById('adminPosition').value);
    formData.append('contact_number', document.getElementById('adminContact').value);

    if (selectedDepartmentName === 'Others') {
        formData.append('department_others', document.getElementById('departmentOthers').value);
    }

    if (fileInput.files[0]) {
        formData.append('profile_picture', fileInput.files[0]);
    }

    try {
        const response = await fetch('/hk-roadmap/profile/update', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`
            },
            body: formData
        });

        const data = await response.json();
        if (response.ok) {
            alert('Profile updated successfully!');
            disableProfileEditing(inputs, editButton, saveButton);
            await fetchAdminProfile();
        } else {
            throw new Error(data.message || 'Failed to update profile');
        }
    } catch (error) {
        console.error('Error updating profile:', error);
        alert(error.message);
    }
}

function enableProfileEditing(inputs, editButton, saveButton) {
    inputs.forEach(input => input.disabled = false);
    
    document.getElementById('editProfileButton').style.display = 'none';
    document.getElementById('saveProfileButton').style.display = 'block';
    document.getElementById('cancelEditButton').style.display = 'block';
    document.getElementById('changeProfilePictureButton').style.display = 'block';
}

function disableProfileEditing(inputs, editButton, saveButton) {
    inputs.forEach(input => input.disabled = true);
    
    document.getElementById('editProfileButton').style.display = 'block';
    document.getElementById('saveProfileButton').style.display = 'none';
    document.getElementById('cancelEditButton').style.display = 'none';
    document.getElementById('changeProfilePictureButton').style.display = 'none';
}

function populateDepartmentSelect() {
    const departmentSelect = document.getElementById('adminDepartment');
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
    const departmentSelect = document.getElementById('adminDepartment');
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

        departmentMapping = departments; 
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
    const profileNameElement = document.getElementById('profileName');
    const profilePicElement = document.querySelector('.navprofile-container .profile-pic');
    
    if (profileNameElement && profileData.name) {
        profileNameElement.textContent = profileData.name;
    }

    if (profilePicElement && profileData.profile_picture_url) {
        profilePicElement.src = profileData.profile_picture_url;
    }
}

function setupProfilePictureUpload() {
    const profilePicture = document.getElementById('adminProfilePicture');
    const editButton = document.getElementById('editProfileButton');
    const saveButton = document.getElementById('saveProfileButton');
    const cancelButton = document.getElementById('cancelEditButton');
    const departmentSelect = document.getElementById('adminDepartment');
    const changeProfilePictureButton = document.getElementById('changeProfilePictureButton');

    const fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.accept = 'image/*';
    fileInput.style.display = 'none';
    document.body.appendChild(fileInput);

    changeProfilePictureButton.addEventListener('click', () => {
        fileInput.click();
    });

    let isEditMode = false;

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
        fetchAdminProfile();
    });

    profilePicture.addEventListener('click', () => {
        if (isEditMode) {
            fileInput.click();
        }
    });

    departmentSelect.addEventListener('change', handleDepartmentChange);

    fileInput.addEventListener('change', async (e) => {
        if (e.target.files && e.target.files[0]) {
            const file = e.target.files[0];
            
            if (!file.type.startsWith('image/')) {
                alert('Please select an image file');
                return;
            }

            const maxSize = 5 * 1024 * 1024; 
            if (file.size > maxSize) {
                alert('File size should be less than 5MB');
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                profilePicture.src = e.target.result;
            };
            reader.readAsDataURL(file);
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
            formData.append('name', document.getElementById('adminName').value);
            formData.append('department', document.getElementById('adminDepartment').value);
            formData.append('position', document.getElementById('adminPosition').value);
            formData.append('contact_number', document.getElementById('adminContact').value);

            if (document.getElementById('adminDepartment').value === 'Others') {
                formData.append('department_others', document.getElementById('departmentOthers').value);
            }

            const token = localStorage.getItem('authToken');
            const response = await fetch('/hk-roadmap/profile/update', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`
                },
                body: formData
            });

            if (!response.ok) {
                throw new Error('Failed to update profile');
            }

            isEditMode = false;
            profilePicture.style.cursor = 'default';
            document.querySelectorAll('#profileForm input, #profileForm select').forEach(input => {
                input.disabled = true;
            });
            editButton.style.display = 'block';
            saveButton.style.display = 'none';
            cancelButton.style.display = 'none';
            saveButton._fileToUpload = null;

            await fetchAdminProfile();
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

    if (adminProfile) {
        document.getElementById('adminName').value = adminProfile.name || '';
        document.getElementById('adminPosition').value = adminProfile.position || '';
        document.getElementById('adminContact').value = adminProfile.contact_number || '';

        const departmentSelect = document.getElementById('adminDepartment');
        const departmentOthersGroup = document.getElementById('departmentOthersGroup');
        const departmentOthers = document.getElementById('departmentOthers');

        const departmentFullName = departmentMapping[adminProfile.department] || 'Others';
        departmentSelect.value = departmentFullName;

        if (departmentFullName === 'Others') {
            departmentOthersGroup.style.display = 'block';
            departmentOthers.value = adminProfile.department_others || '';
        }

        const profilePictureUrl = getProfilePictureUrl(adminProfile.profile_picture_url);
        document.getElementById('adminProfilePicture').src = profilePictureUrl;
        document.getElementById('headerProfilePic').src = profilePictureUrl;

        document.getElementById('profileName').textContent = adminProfile.name || 'Admin';
    }
}

let loadingSpinnerTimeout;

function showLoadingSpinner(duration = 700) {
    const spinner = document.getElementById('loadingSpinner');
    if (spinner) {
        spinner.style.display = 'flex'; 
    }

    clearTimeout(loadingSpinnerTimeout);

    loadingSpinnerTimeout = setTimeout(() => {
        hideLoadingSpinner();
    }, duration);
}

function hideLoadingSpinner() {
    const spinner = document.getElementById('loadingSpinner');
    if (spinner) {
        spinner.style.display = 'none';
    }
    clearTimeout(loadingSpinnerTimeout);
}

// frontend listeners
document.addEventListener('DOMContentLoaded', initializeYearDropdown);

function logout() {
    alert('Logging out...');
    adminLogout();
}


document.addEventListener('DOMContentLoaded', () => {
    const refreshControls = createRefreshControls(fetchNotifications, 10000);
    refreshControls.start();
    showSection('home');
    fetchCardEvents();
    showTab('submissions');

    getAuthHeaders();

    document.getElementById('profileForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        await saveProfile(inputs, editButton, saveButton);
    });

    document.querySelectorAll('.view-document-button').forEach(button => {
        button.addEventListener('click', () => viewDocument(button.getAttribute('data-id')));
    });
    document.querySelectorAll('.approve-button').forEach(button => {
        button.addEventListener('click', handleStatusUpdate);
    });
    document.querySelectorAll('.reject-button').forEach(button => {
        button.addEventListener('click', handleStatusUpdate);
    });

    document.getElementById('event-section').addEventListener('click', function(event) {
        if (event.target.classList.contains('show-requirements')) {
            const eventId = event.target.closest('.card').dataset.eventId;
            showRequirements(eventId);
        } else if (event.target.classList.contains('edit-event')) {
            const eventId = event.target.closest('.card').dataset.eventId;
            console.log(`Edit event ID: ${eventId}`);
        } else if (event.target.classList.contains('delete-event')) {
            const eventId = event.target.closest('.card').dataset.eventId;
            if (confirm(`Are you sure you want to delete this event?`)) {
                deleteEvent(eventId);
            }
        }
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.comment-actions')) {
            document.querySelectorAll('.action-menu').forEach(menu => {
                menu.style.display = 'none';
            });
        }
    });

    document.getElementById('requirements-section').addEventListener('click', function(event) {
        const card = event.target.closest('.card');
        if (!card) return;

        const requirementId = card.dataset.requirementId;
        if (event.target.classList.contains('edit-requirement')) {
            const requirementId = event.target.closest('.card').dataset.requirementId;
            console.log(`Edit requirement ID: ${requirementId}`);
        } else if (event.target.classList.contains('delete-requirement')) {
            const requirementId = event.target.closest('.card').dataset.requirementId;
            if (confirm(`Are you sure you want to delete this requirement?`)) {
                deleteRequirement(requirementId);
            }
        } 
    });

    const eventCreateButton = document.getElementById('event-create');
    const requirementCreateButton = document.getElementById('requirement-create');

    if (eventCreateButton) {
        eventCreateButton.addEventListener('click', () => {
            document.getElementById('eventId').value = ''; 
            document.getElementById('eventName').value = '';
            document.getElementById('eventDate').value = '';
            toggleEventPopup(); 
        });
    } else {
        console.error('Event Create button not found');
    }

    if (requirementCreateButton) {
        requirementCreateButton.addEventListener('click', () => {
            document.getElementById('requirementId').value = ''; 
            document.getElementById('requirementName').value = '';
            document.getElementById('requirementDescription').value = '';
            document.getElementById('dueDate').value = '';
            toggleRequirementPopup(); 
        });
    } else {
        console.error('Requirement Create button not found');
    }

    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('edit-event')) {
            const eventId = e.target.dataset.eventId; 
            console.log('Event ID:', eventId); 
            if (eventId) {
                loadEventForEdit(eventId); 
            } else {
                console.error('Event ID is not defined.');
                alert('Event ID is required to load the event for editing.');
            }
        }
    });

    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('edit-requirement')) {
            const requirementId = e.target.dataset.requirementId; 
            console.log('Requirement ID:', requirementId); 
            if (requirementId) {
                loadRequirementForEdit(requirementId); 
            } else {
                console.error('Requirement ID is not defined.');
                alert('Requirement ID is required to load the event for editing.');
            }
        }
    });

    
    document.addEventListener('DOMContentLoaded', () => {
        badgeRefresher.init();
    });

    window.addEventListener('beforeunload', () => {
        badgeRefresher.cleanup();
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.notifications-container')) {
            document.getElementById('notificationPopup').style.display = 'none';
        }
        if (!e.target.closest('.navprofile-container')) {
            document.getElementById('profileMenu').style.display = 'none';
        }
    });

    document.getElementById('searchInput').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase().trim();
        const searchTerms = searchTerm.split(/\s+/);
        
        const filtered = allSubmissions.filter(group => {
            const eventName = group.event_name.toLowerCase();
            const requirementName = group.requirement_name.toLowerCase();
            const studentName = group.student_name.toLowerCase();
            const status = group.status.toLowerCase();
            const submissionDate = new Date(group.submission_date).toLocaleDateString().toLowerCase();
            
            return searchTerms.every(term => 
                eventName.includes(term) ||
                requirementName.includes(term) ||
                studentName.includes(term) ||
                status.includes(term) ||
                submissionDate.includes(term)
            );
        });
        
        renderTable(filtered);
    });

    
    document.getElementById('requirements-section').addEventListener('click', function(event) {
        if (event.target.classList.contains('show-comments')) {
            const requirementId = event.target.closest('.card').dataset.requirementId;
            showComments(requirementId);
        }
    });

    document.getElementById('backToReqBtn').addEventListener('click', () => {
        document.getElementById('comment-section').style.display = 'none';
        document.getElementById('requirements-section').style.display = 'grid';
        document.getElementById('backtoRequirements').style.display = 'none';
        document.getElementById('backToEventsButton').style.display = 'block';
    });

    document.getElementById('yearSelect').addEventListener('change', function() {
        const selectedYear = this.value;
        fetchCardEvents(selectedYear); 
    });

    const backButton = document.querySelector('.backToEventsBtn');
    if (backButton) {
        backButton.addEventListener('click', () => {
            const requirementsSection = document.getElementById('requirements-section');
            const eventSection = document.getElementById('event-section');
            const backButtonContainer = document.getElementById('backToEventsButton');
            const createEvent = document.getElementById('event-create');
            const yearSelect = document.getElementById('yearSelect');
            const yearSelected = document.getElementById('yearSelected');

            if (requirementsSection && eventSection && backButtonContainer && yearSelect && yearSelected) {
                requirementsSection.style.display = 'none';
                eventSection.style.display = 'grid'; 
                backButtonContainer.style.display = 'none';
                createEvent.style.display = 'block';
                yearSelect.style.display = 'block';
                yearSelected.style.display = 'block';
                document.getElementById('yearSelectorContainer').style.display = 'block';
            }
        });
    }

    const accountButton = document.querySelector('.account-button');
    if (accountButton) {
        accountButton.addEventListener('click', toggleAccountPopup);
    }
    
    document.getElementById('announcementForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const id = document.getElementById('announcementId').value;
        const title = document.getElementById('announcementTitle').value;
        const content = document.getElementById('announcementContent').value;
    
        try {
            if (!id) {
                await fetch('/hk-roadmap/announcements/add', {
                    method: 'POST',
                    headers: getAuthHeaders(),
                    body: JSON.stringify({ title, content })
                });
            }
            
            this.reset();
            document.getElementById('announcementId').value = '';
            
            await fetchAnnouncements();
            
        } catch (error) {
            console.error('Error saving announcement:', error);
            alert('Failed to save announcement');
        }
    });

    document.getElementById('editAnnouncementForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const id = document.getElementById('editAnnouncementId').value;
        const title = document.getElementById('editAnnouncementTitle').value;
        const content = document.getElementById('editAnnouncementContent').value;
    
        try {
            // Update existing announcement
            await fetch('/hk-roadmap/announcements/update', {
                method: 'PUT',
                headers: getAuthHeaders(),
                body: JSON.stringify({ id, title, content })
            });
    
            this.reset();
            document.getElementById('editAnnouncementId').value = '';
    
            await fetchAnnouncements();
            
            toggleEditAnnouncementPopup();
            
        } catch (error) {
            console.error('Error updating announcement:', error);
            alert('Failed to update announcement');
        }
    });

    if (document.getElementById('announce-section')) {
        fetchAnnouncements();
    }

    document.getElementById('searchInput').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const commentGroups = document.querySelectorAll('.student-comment-group');
        commentGroups.forEach(group => {
            const userName = group.querySelector('.student-header h4').textContent.toLowerCase();
            if (userName.includes(searchTerm)) {
                group.style.display = 'block';
            } else {
                group.style.display = 'none';
            }
        });
    });

    const studentSearch = document.getElementById('studentSearch');
    studentSearch?.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase().trim();
        const filtered = allStudents.filter(student => {
            const searchString = [
                student.student_number,
                student.name,
                student.department,
                student.year_level,
                student.college_program
            ].join(' ').toLowerCase();
            return searchString.includes(searchTerm);
        });
        renderStudents(filtered);
    });

    const adminSearch = document.getElementById('adminSearch');
    adminSearch?.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase().trim();
        const filtered = allAdmins.filter(admin => {
            const searchString = [
                admin.name,
                admin.position,
                admin.department,
                admin.email
            ].join(' ').toLowerCase();
            return searchString.includes(searchTerm);
        });
        renderAdmins(filtered);
    });

    const staffSearch = document.getElementById('staffSearch');
    if (staffSearch) {
        staffSearch.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase().trim();
            const filtered = allStaff.filter(staffMember => {
                const searchString = [
                    staffMember.name || '',
                    staffMember.position || '',
                    staffMember.department || '',
                    staffMember.contact_number || ''
                ].join(' ').toLowerCase();
                return searchString.includes(searchTerm);
            });
            renderStaff(filtered);
        });
    }
 

    const editButton = document.getElementById('editProfileButton');
    const saveButton = document.getElementById('saveProfileButton');
    const inputs = document.querySelectorAll('#profile-section input');

    editButton.addEventListener('click', function() {
        enableProfileEditing(inputs, editButton, saveButton);
    });

    saveButton.addEventListener('click', function() {
        saveProfile(inputs, editButton, saveButton);
    });

    const menuButton = document.getElementById('toggleMenuButton');
    menuButton.addEventListener('click', function() {
        toggleSidebar();
    });

    document.addEventListener('click', function(event) {
        const sidebar = document.querySelector('.sidebar');
        const toggleButton = document.getElementById('toggleMenuButton');
        const content = document.querySelector('.content');
    
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

    document.addEventListener('click', function(event) {
        const menuButton = event.target.closest('.menu-button'); // Check if the clicked element is a menu button
        const menuOptions = event.target.closest('.menu-options'); // Check if the clicked element is within menu options
    
        // If the click is outside the menu button and menu options, close the menu options
        if (!menuButton && !menuOptions) {
            closeMenuOptions();
        }
    });
    
    document.getElementById('submitCommentButton').addEventListener('click', submitComment);

    document.getElementById('closeCommentPopupButton').addEventListener('click', closeCommentPopup);

    fetchDepartments().then(populateDepartmentSelect);
    fetchAdminProfile();
    setupProfilePictureUpload();
    showOnBoardingScreens();
});