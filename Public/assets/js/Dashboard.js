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

function showSection(section) {
    document.getElementById('home-section').style.display = 'none';
    document.getElementById('announcement-section').style.display = 'none';
    document.getElementById('admin-section').style.display = 'none';
    document.getElementById(section + '-section').style.display = 'block';
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

// Initialize the first tab
document.addEventListener('DOMContentLoaded', () => {
    showSection('home');
    showTab('documents');

    // Function to toggle the popup visibility
    function togglePopup() {
        const popup = document.getElementById('popup');
        popup.style.display = popup.style.display === 'block' ? 'none' : 'block';
    }

    // Function to handle logout
    function logout() {
        alert('Logging out...');
        adminLogout();
        // Implement logout logic here
    }

    const accountButton = document.querySelector('.account-button');
    accountButton.addEventListener('click', togglePopup);

    const logoutButton = document.querySelector('.popup-button');
    logoutButton.addEventListener('click', logout);

    const closeButton = document.querySelector('.close-popup');
    closeButton.addEventListener('click', togglePopup);
});