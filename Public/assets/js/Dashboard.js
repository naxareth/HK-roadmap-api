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
    console.log(`Trying to show section: ${sectionToShow ? sectionToShow.id : 'not found'}`);
    const sectionsToHide = ['home-section', 'admin-section'];

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

function fetchDocuments() {
    fetch('/hk-roadmap/documents/upload')
        .then(response => response.json())
        .then(data => {
            const tableBody = document.querySelector('#documentsTable tbody');
            tableBody.innerHTML = ''; 
            data.forEach(doc => {
                const row = `<tr>
                    <td>${doc.document_id}</td>
                    <td>${doc.event_id}</td>
                    <td>${doc.requirement_id}</td>
                    <td>${doc.student_id}</td>
                    <td>${doc.file_path}</td>
                    <td>${doc.upload_at}</td>
                    <td>${doc.status}</td>
                </tr>`;
                tableBody.innerHTML += row;
            });
        })
        .catch(error => console.error('Error fetching documents:', error));
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
                </tr>`;
                tableBody.innerHTML += row;
            });
        })
        .catch(error => console.error('Error fetching submissions:', error));
}

// others, misc, etc

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

function populateYearDropdown(data, yearSelect, selectedYear) {
    const years = new Set();

    // Extract unique years from the data
    data.forEach(doc => {
        const eventYear = new Date(doc.date).getFullYear();
        years.add(eventYear);
    });

    // Clear existing options
    yearSelect.innerHTML = '<option value="">All Years</option>'; // Reset dropdown to default

    // Populate the dropdown with unique years
    years.forEach(year => {
        yearSelect.innerHTML += `<option value="${year}" ${year === selectedYear ? 'selected' : ''}>${year}</option>`;
    });
}

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

        // Extract unique years
        const years = new Set();
        data.forEach(doc => {
            const eventYear = new Date(doc.date).getFullYear();
            years.add(eventYear);
        });

        // Populate dropdown
        yearSelect.innerHTML = '<option value="">All Years</option>';
        years.forEach(year => {
            yearSelect.innerHTML += `<option value="${year}">${year}</option>`;
        });
    } catch (error) {
        console.error('Error initializing year dropdown:', error);
    }
}

// cards

async function fetchCardEvents(selectedYear) {
    const authToken = localStorage.getItem('authToken');
    const eventSection = document.getElementById('event-section');
    const createEvent = document.getElementById('event-create');
    const yearSelect = document.getElementById('yearSelect');

    try {
        const response = await fetch('/hk-roadmap/event/get', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken}`
            }
        });

        if (!response.ok) throw new Error(`Error: ${response.status} ${response.statusText}`);
        const data = await response.json();

        const eventCards = eventSection.querySelectorAll('.card');
        const monthLabels = eventSection.querySelectorAll('.month-label');
        eventCards.forEach(card => card.remove());
        monthLabels.forEach(label => label.remove());

        if (Array.isArray(data)) {
            data.sort((a, b) => new Date(a.date) - new Date(b.date));

            const filteredData = selectedYear
                ? data.filter(doc => new Date(doc.date).getFullYear() === parseInt(selectedYear))
                : data;

            const groupedEvents = groupEventsByMonth(filteredData);
            for (const [monthYear, events] of Object.entries(groupedEvents)) {
                const monthLabel = document.createElement('h3');
                monthLabel.classList.add('month-label');
                monthLabel.textContent = monthYear;
                eventSection.appendChild(monthLabel);

                events.forEach(doc => {
                    const card = `
                        <div class="card" data-event-id="${doc.event_id}" data-date="${doc.date}">
                            <div class="text-content">
                                <h2>Event ID: ${doc.event_id}</h2>
                                <br>
                                <p><strong>Event:</strong> ${doc.event_name}</p>
                                <p><strong>Date:</strong> ${doc.date}</p>
                                <i class="far fa-file-alt"></i>
                            </div>
                            <div class="button-content">
                                <button class="show-requirements" aria-label="Show requirements for event ${doc.event_id}">Show Requirements</button>
                                <button class="edit-event" data-event-id="${doc.event_id}" aria-label="Edit event ${doc.event_id}">Edit</button>
                            </div>
                        </div>
                    `;
                    eventSection.insertAdjacentHTML('beforeend', card);
                });
            }

            eventSection.style.display = 'grid';
            createEvent.style.display = 'block';
        }
    } catch (error) {
        console.error('Error fetching events:', error);
        alert('Failed to load events. Please try again later.');
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


async function showRequirements(eventId) {
    const authToken = localStorage.getItem('authToken');
    document.getElementById('eventId').value = eventId;
    
    try {
        const response = await fetch(`/hk-roadmap/requirements/get?event_id=${eventId}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken}`
            }
        });

        if (!response.ok) throw new Error('Network response was not ok');

        const data = await response.json();

        if (Array.isArray(data)) {
            const requirementsSection = document.getElementById('requirements-section');
            const backButton = document.getElementById('backToEventsBtn');
            
            const requirementCards = requirementsSection.querySelectorAll('.card');
            requirementCards.forEach(card => card.remove());

            data.forEach(req => {    
                console.log('Identifications:', req.requirement_id, req.event_id); 
                const requirement = `
                    <div class="card" data-requirement-id="${req.requirement_id}" data-event-id="${req.event_id}">
                        <h2>Requirement: ${req.requirement_name}</h2>
                        <br>
                        <p><strong>Due:</strong> ${req.due_date}</p>
                        <button class="edit-requirement" data-requirement-id="${req.requirement_id}">Edit</button>
                    </div>`;
                requirementsSection.insertAdjacentHTML('beforeend', requirement); 
            });

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
        }
    } catch (error) {
        console.error('Error fetching requirements:', error);
        alert('Error loading requirements');
    }
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
            fetchCardEvents();
            return true;
        } else {
            throw new Error(data.message || 'Failed to update event');
        }
    } catch (error) {
        console.error('Error updating event:', error);
        alert(error.message);
        return false;
    }
}

async function createEvent(eventData) {
    const authToken = localStorage.getItem('authToken');

    console.log(eventId, eventData);
    try {
        const response = await fetch(`/hk-roadmap/event/get`, {
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
            fetchCardEvents();
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
    const selectedDate = new Date(eventDate);
    const currentDate = new Date();

    currentDate.setHours(0, 0, 0, 0);

    if (selectedDate < currentDate) {
        alert("The event date cannot be in the past. Please select a valid date.");
        return; 
    }

    const eventDataId = {
        event_id: eventId,
        event_name: document.getElementById('eventName').value,
        date: document.getElementById('eventDate').value
    };
    const eventData = {
        event_id: eventId,
        event_name: document.getElementById('eventName').value,
        date: document.getElementById('eventDate').value
    };

    if (eventId) {
        await updateEvent(eventId, eventDataId);
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
        alert('Failed to load event data'); // Show error message to the user
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

        const data = await response.json();
        if (response.ok) {
            alert('Requirement updated successfully!');
            showRequirements(eventId);
            return true;
        } else {
            throw new Error(data.message || 'Failed to update requirement');
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

    console.log(requirementData);
    try {
        const response = await fetch(`/hk-roadmap/requirements/add`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken}`
            },
            body: JSON.stringify(requirementData)
        });

        console.log(response)

        const data = await response.json();
        console.log(data)

        if (response.ok) {
            alert('Requirement created successfully!');
            showRequirements(requirementData.event_id);
            return true;
        } else {
            throw new Error(data.message || 'Failed to create requirement');
        }
    } catch (error) {
        console.error('Error create event:', error);
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

    const requirementDataId = {
        requirement_name: document.getElementById('requirementName').value,
        due_date: document.getElementById('dueDate').value,
        requirement_id: requirementId,
    };

    const requirementData = {
        event_id: eventId,
        requirement_id: requirementId,
        requirement_name: document.getElementById('requirementName').value,
        due_date: document.getElementById('dueDate').value,
    };

    if (requirementId) {
        await updateRequirement(requirementId, requirementDataId);
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
            const errorText = await response.text(); // Get the response text for debugging
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

// frontend listener
document.addEventListener('DOMContentLoaded', initializeYearDropdown);

document.addEventListener('DOMContentLoaded', () => {
    showSection('home');
    fetchCardEvents();
    showTab('documents');
    
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
        }
    });

    document.getElementById('requirements-section').addEventListener('click', function(event) {
        if (event.target.classList.contains('edit-requirement')) {
            const requirementId = event.target.closest('.card').dataset.requirementId;
            console.log(`Edit requirement ID: ${requirementId}`);
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
});