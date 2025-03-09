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
    const sectionsToHide = ['home-section', 'admin-section', 'notif-section', 'announce-section'];

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
                    <td>${doc.student_id}</td>
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
            data.forEach(sub => {
                const row = `<tr>
                    <td>${sub.submission_id}</td>
                    <td>${sub.requirement_id}</td>
                    <td>${sub.event_id}</td>
                    <td>${sub.student_id}</td>
                    <td>${sub.file_path}</td>
                    <td>${sub.submission_date}</td>
                    <td>${sub.status}</td>
                    <td>${sub.approved_by}</td>
                    <td>
                        <button class="approve-button" data-id="${sub.submission_id}">Approve</button>
                        <button class="reject-button" data-id="${sub.submission_id}">Reject</button>
                    </td>
                </tr>`;
                tableBody.innerHTML += row;
            });

            // Add event listeners for the new buttons
            document.querySelectorAll('.approve-button').forEach(button => {
                button.addEventListener('click', handleStatusUpdate);
            });
            document.querySelectorAll('.reject-button').forEach(button => {
                button.addEventListener('click', handleStatusUpdate);
            });
        })
        .catch(error => console.error('Error fetching submissions:', error));
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
                            <i class="far fa-file-alt"></i>
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

//submissions

async function handleStatusUpdate(event) {
    const button = event.target;
    const submissionId = button.getAttribute('data-id');
    const action = button.classList.contains('approve-button') ? 'approved' : 'rejected';

    try {
        const response = await fetch(`/hk-roadmap/submission/update`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`
            },
            body: JSON.stringify({
                submission_id: submissionId,
                status: action
            })
        });

        const text = await response.text();
        const data = text ? JSON.parse(text) : {};

        if (!response.ok) {
            throw new Error(data.message || `HTTP error! Status: ${response.status}`);
        }

        alert(`Submission ${action}!`);
        fetchSubmissions();
    } catch (error) {
        console.error('Status update error:', error);
        alert(`Error: ${error.message}`);
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
        const authToken = localStorage.getItem('authToken');
        const notificationContainer = document.getElementById('notificationContainer');

        const response = await fetch('/hk-roadmap/notification/get', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken}`
            }
        });

        if (!response.ok) throw new Error(`Error: ${response.status} ${response.statusText}`);
        const notifications = await response.json();

        // Clear existing content
        notificationContainer.innerHTML = '';

        if (Array.isArray(notifications)) {

            // Create notification cards
            notifications.forEach(notification => {
                // Add temporary logging
                console.log('Notification ID:', notification.notification_id);
                console.log('Read status:', notification.read_notif, typeof notification.read_notif);
                
                const card = `
                    <div class="card notification-card" data-notification-id="${notification.notification_id}">
                        <div class="text-content">
                            <p class="notification-body">${notification.notification_body}</p>
                        </div>
                        <div class="button-content">
                            <small class="notification-meta">
                                ${formatDateTime(notification.created_at)}
                                ${notification.read_notif === 0 ? 
                                    `<button class="acknowledge-btn" 
                                        onclick="markAsRead(${notification.notification_id})">
                                        Mark Read
                                    </button>` : 
                                    '<span class="read-badge">Read</span>'
                                }
                            </small>
                        </div>
                    </div>
                `;
                notificationContainer.insertAdjacentHTML('beforeend', card);
            });
        }
    } catch (error) {
        console.error('Error fetching notifications:', error);
        alert('Failed to load notifications. Please try again later.');
    }
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


// frontend listeners
document.addEventListener('DOMContentLoaded', initializeYearDropdown);


document.addEventListener('DOMContentLoaded', () => {
    showSection('home');
    fetchCardEvents();
    showTab('documents');

    getAuthHeaders();

    
    function logout() {
        alert('Logging out...');
        adminLogout();
    }

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

    document.getElementById('requirements-section').addEventListener('click', function(event) {
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

    if (document.getElementById('notif-section')) {
        fetchNotifications();
    }
    if (document.getElementById('announce-section')) {
        fetchAnnouncements();
    }
});