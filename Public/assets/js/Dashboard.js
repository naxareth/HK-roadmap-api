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
    }
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
});