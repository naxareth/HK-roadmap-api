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

function showTab(tabId) {
    // Hide all tab contents
    const tabContents = document.querySelectorAll('.tab-content');
    tabContents.forEach(content => content.style.display = 'none');

    // Show the selected tab content
    document.getElementById(tabId).style.display = 'block';

    // Fetch data for the selected tab
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


async function adminLogout() {
    const authToken = localStorage.getItem('authToken'); // Retrieve the token from local storage

    try {
        const response = await fetch('/hk-roadmap/admin/logout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken}` // Include the token in the Authorization header
            }
        });

        const data = await response.json(); // Parse the JSON response

        if (response.ok) {
            alert(data.message); // Show success message
            localStorage.removeItem('authToken'); // Remove the token from local storage
            window.location.href = 'login.html'; // Redirect to login page
        } else {
            alert(data.message || 'Logout failed. Please try again.'); // Show error message
        }
    } catch (error) {
        console.error('Logout error:', error);
        alert('An error occurred while logging out. Please try again.');
    }
}


function fetchStudent() {
    // Fetch data from the server for documents
    fetch('/hk-roadmap/student/profile')
        .then(response => response.json())
        .then(data => {
            const tableBody = document.querySelector('#studentsTable tbody');
            tableBody.innerHTML = ''; // Clear existing data
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
    // Fetch data from the server for documents
    fetch('/hk-roadmap/admin/profile')
        .then(response => response.json())
        .then(data => {
            const tableBody = document.querySelector('#adminsTable tbody');
            tableBody.innerHTML = ''; // Clear existing data
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
    // Fetch data from the server for documents
    fetch('/hk-roadmap/documents/upload')
        .then(response => response.json())
        .then(data => {
            const tableBody = document.querySelector('#documentsTable tbody');
            tableBody.innerHTML = ''; // Clear existing data
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
    // Fetch data from the server for submissions
    fetch('/hk-roadmap/submission/update')
        .then(response => response.json())
        .then(data => {
            const tableBody = document.querySelector('#submissionsTable tbody');
            tableBody.innerHTML = ''; // Clear existing data
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
async function fetchCardEvents() {
    const authToken = localStorage.getItem('authToken'); 

    try {
        const response = await fetch('/hk-roadmap/event/get', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken}`
            }
        });

        if (!response.ok) {
            throw new Error('Network response was not ok');
        }

        const data = await response.json();
        const eventSection = document.getElementById('event-section');
        
        // Clear only the event cards, not the entire section
        const eventCards = eventSection.querySelectorAll('.card');
        eventCards.forEach(card => card.remove());

        if (Array.isArray(data)) {
            data.forEach(doc => {
                console.log('Event ID:', doc.event_id); 
                const card = `
                    <div class="card" onclick="showRequirements(${doc.event_id})">
                        <h2>Event ID: ${doc.event_id}</h2>
                        <br>
                        <p><strong>Event:</strong> ${doc.event_name}</p>
                        <p><strong>Date:</strong> ${doc.date}</p>
                        <i class="far fa-file-alt"></i>
                        <button class="edit-event" data-event-id="${doc.event_id}">Edit</button>
                    </div>`;
                eventSection.insertAdjacentHTML('beforeend', card); // Append the card
            });
            eventSection.style.display = 'grid';
        } else {
            console.error('Expected an array but received:', data);
            alert('Failed to load events. Please try again later.');
        }
    } catch (error) {
        console.error('Error fetching events:', error);
    }
}

async function showRequirements(eventId) {
    const authToken = localStorage.getItem('authToken');
    
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
            
            // Clear only the requirement cards, not the entire section
            const requirementCards = requirementsSection.querySelectorAll('.card');
            requirementCards.forEach(card => card.remove());

            data.forEach(req => {
                const requirement = `
                    <div class="card">
                        <h2>Requirement: ${req.requirement_name}</h2>
                        <br>
                        <p><strong>Due:</strong> ${req.due_date}</p>
                        <button class="edit-requirement" data-requirement-id="${req.requirement_id}">Edit</button>
                    </div>`;
                requirementsSection.insertAdjacentHTML('beforeend', requirement); // Append the requirement
            });

            if (backButton) {
                backButton.style.display = 'block';
                backButton.parentElement.style.display = 'block'; 
            }

            document.getElementById('event-section').style.display = 'none';
            requirementsSection.style.display = 'grid';
            backButton.style.display = 'block'; 
        }
    } catch (error) {
        console.error('Error fetching requirements:', error);
        alert('Error loading requirements');
    }
}


function showSection(section) {
    const sectionToShow = document.getElementById(section + '-section');
    console.log(`Trying to show section: ${sectionToShow ? sectionToShow.id : 'not found'}`);

    // List of all sections to hide
    const sectionsToHide = ['home-section', 'admin-section']; // Add other sections as needed

    // Hide all sections
    sectionsToHide.forEach(sec => {
        const element = document.getElementById(sec);
        if (element) {
            element.style.display = 'none';
        } else {
            console.error(`Section "${sec}" not found.`);
        }
    });

    // Show the specified section
    if (sectionToShow) {
        sectionToShow.style.display = 'block';
    } else {
        console.error(`Section "${section}-section" not found.`);
    }
}

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

async function updateRequirement(requirementId, requirementData) {
    try {
        const response = await fetch(`/hk-roadmap/requirement/edit?id=${requirementId}`, {
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
            const eventId = new URLSearchParams(window.location.search).get('eventId');
            if (eventId) showRequirements(eventId); // Refresh requirements list
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

async function saveEvent() {
    const eventId = document.getElementById('eventId').value;
    console.log('Event ID:', eventId);
    const eventData = {
        event_id: eventId,
        event_name: document.getElementById('eventName').value,
        date: document.getElementById('eventDate').value
    };

    if (eventId) {
        await updateEvent(eventId, eventData);
    } else {
        await createEvent(eventData);
    }
    toggleEventPopup();
}

async function saveRequirement() {
    const requirementId = document.getElementById('requirementId').value;
    const requirementData = {
        requirement_name: document.getElementById('requirementName').value,
        due_date: document.getElementById('dueDate').value,
        event_id: document.querySelector('.event-section').dataset.eventId // Get current event ID
    };

    if (requirementId) {
        await updateRequirement(requirementId, requirementData);
    } else {
        await createRequirement(requirementData);
    }
    toggleRequirementPopup();
}

async function loadEventForEdit(eventId) {
    const authToken = localStorage.getItem('authToken'); // Retrieve the token from local storage

    try {
        const response = await fetch(`/hk-roadmap/event/edit?event_id=${eventId}`, { // Ensure the endpoint is correct
            method: 'GET', // Use GET to fetch the event details
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken}` // Include the token in the Authorization header
            }
        });

        const data = await response.json(); 
        console.log('Event data:', data); 

        if (response.ok) {
            // Access the first element of the array
            const eventData = data[0]; // Get the first object from the array

            // Populate the form fields with the event data
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

            // Directly set the date input if it exists
            if (eventData.date) {
                const dateParts = eventData.date.split('-'); // Split the date string
                const formattedDate = `${dateParts[1]}/${dateParts[2]}/${dateParts[0]}`; // Rearrange to MM/DD/YYYY
                document.getElementById('eventDate').value = formattedDate; // Set the formatted date
            } else {
                console.warn('Date is undefined, setting to default date.');
                const defaultDate = new Date().toISOString().split('T')[0]; // Get today's date in YYYY-MM-DD format
                document.getElementById('eventDate').value = defaultDate; // Set to today's date
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

async function loadRequirementForEdit(requirementId) {
    try {
        const response = await fetch(`/hk-roadmap/requirements/add?id=${requirementId}`);
        const data = await response.json();
        
        document.getElementById('requirementId').value = data.requirement_id;
        document.getElementById('requirementName').value = data.requirement_name;
        document.getElementById('dueDate').value = data.due_date.split('T')[0];
        toggleRequirementPopup();
    } catch (error) {
        console.error('Error loading requirement:', error);
        alert('Failed to load requirement data');
    }
}


// Initialize the first tab
document.addEventListener('DOMContentLoaded', () => {
    showSection('home');
    fetchCardEvents();
    showTab('documents');
    
    function logout() {
        alert('Logging out...');
        adminLogout();
    }

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
            const eventId = e.target.dataset.eventId; // Get the event ID from the button's data attribute
            console.log('Event ID:', eventId); // Log the event ID to verify it's being retrieved correctly
            if (eventId) {
                loadEventForEdit(eventId); 
            } else {
                console.error('Event ID is not defined.');
                alert('Event ID is required to load the event for editing.');
            }
        }
    });

    const backButton = document.querySelector('.backToEventsBtn');
    if (backButton) {
        backButton.addEventListener('click', () => {
            const requirementsSection = document.getElementById('requirements-section');
            const eventSection = document.getElementById('event-section');
            const backButtonContainer = document.getElementById('backToEventsButton');

            if (requirementsSection && eventSection && backButtonContainer) {
                requirementsSection.style.display = 'none';
                eventSection.style.display = 'grid'; 
                backButtonContainer.style.display = 'none';
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