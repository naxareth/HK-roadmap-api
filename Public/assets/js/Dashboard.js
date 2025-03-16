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

// All Popups
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
    } else if (tabId === 'admins') {
        fetchAdmin();
    }
}

function showSection(section) {
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

function fetchAdmin() {
    fetch('/hk-roadmap/admin/profile')
        .then(response => response.json())
        .then(data => {
            const tableBody = document.querySelector('#adminsTable tbody');
            tableBody.innerHTML = ''; 
            data.forEach(doc => {
                const row = `<tr>
                    <td>${doc.admin_id}</td>
                    <td>${doc.name}</td>
                    <td>${doc.email}</td>
                    <td>${doc.password}</td>
                </tr>`;
                tableBody.innerHTML += row;
            });
        })
        .catch(error => console.error('Error fetching documents:', error));
}

async function fetchDocuments() {
    const authToken = localStorage.getItem('authToken');
    try {
        const response = await fetch('/hk-roadmap/documents/admin', {
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

            // Attach click handlers for grouped submissions
            document.querySelectorAll('.view-docs-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const submissionIds = btn.dataset.ids.split(',');
                    viewDocuments(submissionIds); // Open popup with all docs in group
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

let allSubmissions = [];

function fetchSubmissions() {
    fetch('/hk-roadmap/submission/update')
        .then(response => response.json())
        .then(data => {
            allSubmissions = data; // Store submissions globally
            renderTable(data);
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

    // Reattach event listeners
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

        // Clear and populate dropdown with sorting options
        yearSelect.innerHTML = `
            <option value="most_recent" selected>Most Recent</option>
            <option value="oldest">Oldest</option>
        `;

        // Initial load with most recent events
        fetchCardEvents('most_recent');

    } catch (error) {
        console.error('Error initializing dropdown:', error);
    }
}

//cards
async function fetchCardEvents(selectedFilter) {
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

        // Apply sorting
        if (selectedFilter === 'most_recent') {
            data = data.sort((a, b) => b.event_id - a.event_id);
        } else if (selectedFilter === 'oldest') {
            data = data.sort((a, b) => a.event_id - b.event_id);
        }

        // Clear only the cards while preserving other elements
        const existingCards = eventSection.querySelectorAll('.card');
        existingCards.forEach(card => card.remove());

        // Create temporary container
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
                            <h2>Event ID: ${doc.event_id}</h2>
                            <br>
                            <p><strong>Event:</strong> ${doc.event_name}</p>
                            <p><strong>Date:</strong> ${formattedDate}</p>
                        </div>
                        <div class="button-content">
                            <button class="show-requirements">Show Requirements</button>
                            <button class="edit-event" data-event-id="${doc.event_id}">Edit</button>
                            <button class="delete-event" data-event-id="${doc.event_id}">Delete</button>
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

        // Keep these elements visible
        createEvent.style.display = 'block';
        yearSelector.style.display = 'block';

    } catch (error) {
        console.error('Error fetching events:', error);
    }
}

async function showRequirements(eventId) {
    const authToken = localStorage.getItem('authToken');
    document.getElementById('eventId').value = eventId;
    
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

            // Hide year selector container
            document.getElementById('yearSelectorContainer').style.display = 'none'; // Add this line

            // Hide individual elements
            document.getElementById('yearSelect').style.display = 'none';
            document.getElementById('yearSelected').style.display = 'none';


            data.forEach(req => {    
                console.log('Identifications:', req.requirement_id, req.event_id); 
                const requirement = `
                    <div class="card" data-requirement-id="${req.requirement_id}" data-event-id="${req.event_id}">
                        <div class="text-content">
                            <h2>Requirement: ${req.requirement_name}</h2>
                            <p><strong>Description:</strong> ${req.requirement_desc}</p>
                            <br>
                            <p><strong>Due:</strong> ${req.due_date}</p>
                        </div>
                        <div class="button-content">
                            <button class="edit-requirement" data-requirement-id="${req.requirement_id}">Edit</button>
                            <button class="delete-requirement" data-requirement-id="${req.requirement_id}" aria-label="Delete requirement ${req.requirement_id}">Delete</button>
                            <button class="show-comments" data-requirement-id="${req.requirement_id}">Show Comments</button>
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
            throw new Error(data.message || 'Failed to load event data'); // Handle error
        }
    } catch (error) {
        console.error('Error loading event:', error); // Log the error
    }
}

async function deleteEvent(eventId) {
    const authToken = localStorage.getItem('authToken');
    const selectedYear = document.getElementById('yearSelect').value;

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
        // 1. Get all student emails
        const studentsResponse = await fetch('/hk-roadmap/student/emails', {
            headers: getAuthHeaders()
        });
        
        if (!studentsResponse.ok) {
            console.error('Failed to fetch student emails');
            return;
        }

        const emails = await studentsResponse.json(); // Changed variable name
        
        // 2. Send emails in background
        emails.forEach(async (email) => { // Changed parameter name
            try {
                await fetch('/hk-roadmap/mail/send', {
                    method: 'POST',
                    headers: getAuthHeaders(),
                    body: JSON.stringify({
                        recipient: email, // Use email directly
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
        // 1. Get all student emails
        const studentsResponse = await fetch('/hk-roadmap/student/emails', {
            headers: getAuthHeaders()
        });
        
        if (!studentsResponse.ok) {
            console.error('Failed to fetch student emails');
            return;
        }

        const emails = await studentsResponse.json();
        
        // 2. Format due date
        const formattedDueDate = new Date(dueDate).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });

        // 3. Send emails in background
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

    try {
        const response = await fetch(`/hk-roadmap/requirements/edit?requirement_id=${requirementId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`
            },
            body: JSON.stringify(requirementData)
        });

        // Check if the response is OK
        if (!response.ok) {
            const errorText = await response.text(); // Get the response text for debugging
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
    const searchTerm = document.getElementById('search-input').value.toLowerCase();
    const commentCards = document.querySelectorAll('.comment-card');

    commentCards.forEach(card => {
        const commentBody = card.querySelector('.comment-body').textContent.toLowerCase();
        const commentAuthor = card.querySelector('.comment-author').textContent.toLowerCase();

        if (commentBody.includes(searchTerm) || commentAuthor.includes(searchTerm)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

async function showComments(requirementId) {
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
        if (comments.length === 0) {
            commentsList.innerHTML = '<p class="no-comments">No comments found</p>';
            return;
        }
 
 
        // Group comments by student_id
        const commentsByStudent = comments.reduce((groups, comment) => {
            const key = comment.student_id;
            if (!groups[key]) {
                groups[key] = [];
            }
            groups[key].push(comment);
            return groups;
        }, {});

        const commentsContainer = document.createElement('div');
        commentsContainer.className = 'student-comments';
 
 
        // Create sections for each student
        Object.entries(commentsByStudent).forEach(([studentId, studentComments]) => {
            const studentSection = document.createElement('div');
            studentSection.className = 'student-comment-group';
            studentSection.dataset.studentId = studentId;
 
 
            // Student header
            const studentHeader = document.createElement('div');
            studentHeader.className = 'student-header';
            studentHeader.innerHTML = `
                <h4>${studentComments[0].user_name} (ID: ${studentId})</h4>
            `;
 
 
            // Comments list
            const commentsContainer = document.createElement('div');
            commentsContainer.className = 'student-comments';
            studentComments.forEach(comment => {
                const commentDate = new Date(comment.created_at).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                const commentCard = `
                    <div class="card comment-card" data-comment-id="${comment.comment_id}">
                        <div class="comment-header">
                            <div class="comment-meta">
                                <div class="comment-author-header">
                                    <span class="comment-author">${comment.user_name}</span>
                                    <span class="comment-date">${commentDate}</span>
                                </div>
                                <p class="comment-body">${comment.body}</p>
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
                    </div>`;
                commentsContainer.insertAdjacentHTML('beforeend', commentCard);
            });
            
            const buttonContainer = document.createElement('div');
            buttonContainer.className = 'comment-button-container';

            const addButton = document.createElement('button');
            addButton.className = 'add-comment-button';
            addButton.textContent = 'Add Comment';
            addButton.onclick = () => openCommentPopup(studentId, requirementId);

            buttonContainer.appendChild(addButton);
            commentsContainer.appendChild(buttonContainer);
 
            studentSection.appendChild(studentHeader);
            studentSection.appendChild(commentsContainer);
            commentsList.appendChild(studentSection);
        });
 
 
        document.getElementById('requirements-section').style.display = 'none';
        document.getElementById('backToEventsButton').style.display = 'none';
        document.getElementById('comment-section').style.display = 'grid';
        document.getElementById('backtoRequirements').style.display = 'block';
    } catch (error) {
        console.error('Error loading comments:', error);
        alert('Failed to load comments');
    }
}
 

function toggleCommentMenu(button) {
    const menu = button.closest('.comment-actions').querySelector('.action-menu');
    const allMenus = document.querySelectorAll('.action-menu');
    
    // Close all other menus
    allMenus.forEach(m => m !== menu ? m.style.display = 'none' : null);
    
    // Toggle current menu
    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
}

let currentEditCommentId = null;

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
            // Update the comment in the UI
            const commentBody = document.querySelector(
                `.comment-card[data-comment-id="${currentEditCommentId}"] .comment-body`
            );
            if (commentBody) {
                commentBody.textContent = document.getElementById('editCommentText').value;
            }
            closeEditModal();
            await showComments(currentRequirementId);
        } else {
            throw new Error('Failed to update comment');
        }
    } catch (error) {
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
                showComments(requirementId); // Refresh comments
            } else {
                alert('Failed to add comment: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to add comment');
        });
    }

    closeCommentPopup();
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('editCommentModal');
    if (event.target === modal) {
        closeEditModal();
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
        const response = await fetch(`/hk-roadmap/notification/edit`, {
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
                    <div class="card announcement-card" data-id="${announcement.announcement_id}">
                        <div class="text-content">
                            <div class="card-title">
                                <div class="card-author">Posted by: ${announcement.author_name}</div> <!-- Access admin name -->
                                <h3>${announcement.title}</h3>
                            </div>
                            <div class="card-content">
                                <p>${announcement.content.replace(/\n/g, '<br>')}</p>
                            </div>
                            <div class="card-meta">
                                <div class="card-date">
                                    ${new Date(announcement.created_at).toLocaleDateString()}
                                </div>
                            </div>
                        </div>
                        <div class="button-content">
                            <div class="card-menu">
                                <button class="menu-button">⋯</button>
                                <div class="menu-options">
                                    <button class="edit-announcement">Edit</button>
                                    <button class="delete-announcement">Delete</button>
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
    const title = card.querySelector('h3').innerText; // Use innerText to preserve formatting
    const content = card.querySelector('p').innerText; // Use innerText to preserve formatting

    // Populate edit form
    document.getElementById('editAnnouncementTitle').value = title;
    document.getElementById('editAnnouncementContent').value = content; // Keep line breaks
    document.getElementById('editAnnouncementId').value = id;
    
    // Show edit announcement popup
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
async function fetchAdminProfile() {
    try {
        const response = await fetch('/hk-roadmap/profile/get', {
            headers: getAuthHeaders()
        });

        if (response.ok) {
            const data = await response.json();
            document.getElementById('adminName').value = data.name || '';
            document.getElementById('adminEmail').value = data.email || '';
            document.getElementById('adminDepartment').value = data.department || '';
            document.getElementById('adminPosition').value = data.position || '';
            document.getElementById('adminContact').value = data.contact_number || '';
            document.getElementById('adminProfilePicture').src = data.profile_picture_url || '';
        } else {
            throw new Error('Failed to fetch profile');
        }
    } catch (error) {
        console.error('Error fetching profile:', error);
    }
}

async function saveProfile(inputs, editButton, saveButton) {
    const profileData = {
        name: document.getElementById('adminName').value,
        email: document.getElementById('adminEmail').value,
        department: document.getElementById('adminDepartment').value,
        position: document.getElementById('adminPosition').value,
        contact_number: document.getElementById('adminContact').value
    };

    try {
        const response = await fetch('/hk-roadmap/profile/update', {
            method: 'POST',
            headers: getAuthHeaders(),
            body: JSON.stringify(profileData)
        });

        if (response.ok) {
            alert('Profile updated successfully!');
            disableProfileEditing(inputs, editButton, saveButton);
        } else {
            throw new Error('Failed to update profile');
        }
    } catch (error) {
        console.error('Error updating profile:', error);
        alert('Failed to update profile');
    }
}

function enableProfileEditing(inputs, editButton, saveButton) {
    inputs.forEach(input => {
        input.disabled = false;
        input.style.backgroundColor = '#fff';
    });
    editButton.style.display = 'none';
    saveButton.style.display = 'block';
}

function disableProfileEditing(inputs, editButton, saveButton) {
    inputs.forEach(input => {
        input.disabled = true;
        input.style.backgroundColor = '#f0f0f0';
    });
    editButton.style.display = 'block';
    saveButton.style.display = 'none';
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
    showTab('documents');

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

    document.getElementById('event-section').addEventListener('click', function(event) {
        if (event.target.classList.contains('show-requirements')) {
            const eventId = event.target.closest('.card').dataset.eventId;
            showRequirements(eventId);
        } else if (event.target.classList.contains('edit-event')) {
            const eventId = event.target.closest('.card').dataset.eventId;
            console.log(`Edit event ID: ${eventId}`);
        } else if (event.target.classList.contains('delete-event')) {
            const eventId = event.target.closest('.card').dataset.eventId;
            if (confirm(`Are you sure you want to delete event ID: ${eventId}?`)) {
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
            if (confirm(`Are you sure you want to delete requirement ID: ${requirementId}?`)) {
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
        if (!e.target.closest('.profile-container')) {
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
        document.getElementById('backToEventsButton').style.display = 'grid';
    });

    document.getElementById('yearSelect').addEventListener('change', function() {
        const selectedYear = this.value;
        fetchCardEvents(selectedYear); // Fetch events for the selected year
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
                document.getElementById('yearSelectorContainer').style.display = 'block';
            }
        });
    }

    const accountButton = document.querySelector('.account-button');
    if (accountButton) {
        accountButton.addEventListener('click', toggleAccountPopup);
    }

    const logoutButton = document.querySelector('.popup-button');
    if (logoutButton) {
        logoutButton.addEventListener('click', logout);
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
 

    const editButton = document.getElementById('editProfileButton');
    const saveButton = document.getElementById('saveProfileButton');
    const inputs = document.querySelectorAll('#profile-section input');

    editButton.addEventListener('click', function() {
        enableProfileEditing(inputs, editButton, saveButton);
    });

    saveButton.addEventListener('click', function() {
        saveProfile(inputs, editButton, saveButton);
    });

    
    document.getElementById('submitCommentButton').addEventListener('click', submitComment);

    document.getElementById('closeCommentPopupButton').addEventListener('click', closeCommentPopup);

    fetchAdminProfile();
});