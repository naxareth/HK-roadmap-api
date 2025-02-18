class ApiService {
    constructor(baseURL) {
        this.baseURL = baseURL;
    }

    async request(method, endpoint, data = null) {
        const url = `${this.baseURL}${endpoint}`;
        const headers = {
            'Content-Type': 'application/json',
        };

        // Add authorization header if token exists
        const token = localStorage.getItem('authToken');
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }

        const options = {
            method,
            headers,
            body: data ? JSON.stringify(data) : null,
        };

        try {
            const response = await fetch(url, options);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return await response.json();
        } catch (error) {
            console.error('API request failed:', error);
            throw error;
        }
    }

    // Specific API methods
    async login(credentials) {
        return this.request('POST', '/api.php?action=student_login', credentials);
    }

    async logout() {
        return this.request('POST', '/api.php?action=student_logout');
    }

    async getDocuments() {
        return this.request('GET', '/api.php?action=get_documents');
    }

    async getRequirements() {
        return this.request('GET', '/api.php?action=get_requirements');
    }

    // Add more API methods as needed
}

// Initialize API service with base URL
const apiService = new ApiService('http://localhost'); // Update with your actual base URL

export default apiService;
