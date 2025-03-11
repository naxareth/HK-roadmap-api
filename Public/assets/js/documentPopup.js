const DocumentPopup = (() => {
    const popup = document.getElementById('documentPopup');
    const content = document.getElementById('documentContent');
    const closeBtn = popup.querySelector('.close-popup');

    const handleImageError = (img) => {
        img.onerror = null;
        img.style.display = 'none';
        const errorDiv = document.createElement('div');
        errorDiv.className = 'document-error';
        errorDiv.innerHTML = `
            <i class="fas fa-exclamation-triangle"></i>
            <p>Failed to load image. <a href="${img.src}" download>Download instead</a></p>
        `;
        img.parentNode.replaceChild(errorDiv, img);
    };

    const createViewer = (fileData) => {
        const viewer = document.createElement('div');
        viewer.className = 'document-viewer';

        // Header with action buttons
        const header = document.createElement('div');
        header.className = 'document-header';
        header.innerHTML = `
            <h3>${fileData.fileName}</h3>
            <div class="document-actions">
                <button class="btn-approve">Approve</button>
                <button class="btn-reject">Reject</button>
                <button class="btn-download">
                    <i class="fas fa-download"></i>
                </button>
                <button class="btn-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;

        // Body content
        const body = document.createElement('div');
        body.className = 'document-body';
        // ... (existing body content setup)

        viewer.appendChild(header);
        viewer.appendChild(body);

        // Event listeners
        header.querySelector('.btn-approve').addEventListener('click', () => {
            handleStatusUpdate(fileData.submissionId, 'approved');
            DocumentPopup.close();
        });

        header.querySelector('.btn-reject').addEventListener('click', () => {
            handleStatusUpdate(fileData.submissionId, 'rejected');
            DocumentPopup.close();
        });

        header.querySelector('.btn-download').addEventListener('click', () => {
            window.open(fileData.downloadUrl, '_blank');
        });

        header.querySelector('.btn-close').addEventListener('click', () => {
            DocumentPopup.close();
        });

        return viewer;
    };

    return {
        open: (submissionId) => {
            fetch(`/hk-roadmap/submission/detail?submission_id=${submissionId}`)
                .then(response => {
                    if (!response.ok) throw new Error('Failed to fetch document details');
                    return response.json();
                })
                .then(data => {
                    const fileExt = data.file_path.split('.').pop().toLowerCase();
                    const fileData = {
                        submissionId: data.submission_id,
                        fileName: data.file_name,
                        fileType: fileExt,
                        isImage: ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileExt),
                        isPDF: fileExt === 'pdf',
                        viewUrl: `/hk-roadmap/uploads/${encodeURIComponent(data.file_path)}`,
                        downloadUrl: `/hk-roadmap/uploads/${encodeURIComponent(data.file_path)}?download=true`
                    };

                    content.innerHTML = '';
                    content.appendChild(createViewer(fileData));
                    popup.style.display = 'flex';
                })
                .catch(error => {
                    console.error('Document load error:', error);
                    content.innerHTML = `<div class="document-error">Error loading document: ${error.message}</div>`;
                    popup.style.display = 'flex';
                });
        },

        close: () => {
            popup.style.display = 'none';
            content.innerHTML = '';
        },

        init: () => {
            closeBtn.addEventListener('click', this.close);
            popup.addEventListener('click', (e) => {
                if (e.target === popup) this.close();
            });
        }
    };
})();

// Enhanced CSS with mobile support
const documentPopupStyles = `
.document-popup {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.8);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.document-viewer {
    background: white;
    border-radius: 8px;
    max-width: 90vw;
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.document-header {
    padding: 1rem;
    background: #f5f5f5;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
}

.document-actions {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.btn-approve {
    background: #4CAF50;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    cursor: pointer;
}

.btn-reject {
    background: #f44336;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    cursor: pointer;
}

@media (max-width: 768px) {
    .document-actions {
        flex-direction: column;
        width: 100%;
    }
    
    .btn-approve, .btn-reject {
        width: 100%;
        margin: 0.25rem 0;
    }
}
`;

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    const styleTag = document.createElement('style');
    styleTag.innerHTML = documentPopupStyles;
    document.head.appendChild(styleTag);
    
    DocumentPopup.init();

    document.querySelectorAll('.view-document-button').forEach(button => {
        button.addEventListener('click', () => {
            const submissionId = button.dataset.id;
            DocumentPopup.open(submissionId);
        });
    });
});

// Status update handler (keep existing functionality)
async function handleStatusUpdate(submissionId, action) {
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

        if (!response.ok) {
            const error = await response.text();
            throw new Error(`Error: ${error}`);
        }

        alert(`Submission ${action}!`);
        fetchSubmissions(); // Refresh table
    } catch (error) {
        console.error('Status update error:', error);
        alert(`Error: ${error.message}`);
    }
}

export default DocumentPopup;