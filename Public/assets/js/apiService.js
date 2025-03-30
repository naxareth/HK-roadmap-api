export default class ApiService {
    constructor(baseURL) {
        this.baseURL = baseURL;
    }

    setAuthToken(token) {
        this.authToken = token;
        this.defaultHeaders = {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
        };
    }

    async request(method, endpoint, data = null) {
        const url = `${this.baseURL}${endpoint}`;
        const headers = this.defaultHeaders || {
            'Content-Type': 'application/json'
        };

        const options = {
            method,
            headers,
            body: data ? JSON.stringify(data) : null
        };

        try {
            const response = await fetch(url, options);
            const responseData = await response.json();

            if (!response.ok) {
                throw {
                    message: responseData.message || 'Request failed',
                    status: response.status,
                    details: responseData.details
                };
            }

            return { success: true, data: responseData, error: null };

        } catch (error) {
            console.error('API Error:', error);
            return {
                success: false,
                data: null,
                error: {
                    message: error.message || 'Network error',
                    status: error.status || 500,
                    details: error.details
                }
            };
        }
    }

    // Admin Methods
    async verifyAdminCredentials(credentials) {
        return this.request('POST', '/hk-roadmap/admin/login', credentials);
    }

    async registerAdmin(adminData) {
        return this.request('POST', '/hk-roadmap/admin/register', adminData);
    }

    async requestAdminOTP(emailData) {
        return this.request('POST', '/hk-roadmap/admin/request-otp', emailData);
    }

    async verifyAdminOTP(otpData) {
        return this.request('POST', '/hk-roadmap/admin/verify-otp', otpData);
    }

    async changeAdminPassword(passwordData) {
        return this.request('POST', '/hk-roadmap/admin/change-password', passwordData);
    }

    // Staff Methods
    async verifyStaffCredentials(credentials) {
        return this.request('POST', '/hk-roadmap/staff/login', credentials);
    }

    async registerStaff(staffData) {
        return this.request('POST', '/hk-roadmap/staff/register', staffData);
    }

    async requestStaffOTP(emailData) {
        return this.request('POST', '/hk-roadmap/staff/send-otp', emailData);
    }

    async verifyStaffOTP(otpData) {
        return this.request('POST', '/hk-roadmap/staff/verify-otp', otpData);
    }

    async changeStaffPassword(passwordData) {
        return this.request('POST', '/hk-roadmap/staff/change-password', passwordData);
    }
}