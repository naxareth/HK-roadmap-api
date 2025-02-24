export default class ApiService {
    constructor(baseURL) {
        this.baseURL = baseURL;
    }

    async request(method, endpoint, data = null) {
        const url = `${this.baseURL}${endpoint}`;
        const headers = {
            'Content-Type': 'application/json',
        };

        const options = {
            method,
            headers,
            body: data ? JSON.stringify(data) : null,
        };

        try {
            const response = await fetch(url, options);
            const responseText = await response.text();
            
            if (!response.ok) {
                let errorData;
                try {
                    errorData = JSON.parse(responseText);
                } catch {
                    errorData = { message: responseText };
                }
                
                throw {
                    message: errorData.message || 'Request failed',
                    status: response.status,
                    details: errorData.details || null
                };
            }

            try {
                return JSON.parse(responseText);
            } catch {
                return { message: responseText };
            }
        } catch (error) {
            console.error('API request failed:', error);
            throw error;
        }
    }

    handleError(error, defaultMessage) {
        console.error(defaultMessage, error);
        let errorMessage = defaultMessage;

        if (error.status === 401) {
            errorMessage = 'Unauthorized access';
        } else if (error.message.includes('NetworkError')) {
            errorMessage = 'Network error. Please check your internet connection';
        }

        return {
            success: false,
            data: null,
            error: {
                message: errorMessage,
                status: error.status || 500,
                details: error.details || null
            }
        };
    }

    async addEvent(eventData, token) {
        try {
            const response = await this.request('POST', '/hk-roadmap/event/add', eventData, token);
            return {
                success: true,
                data: response,
                error: null
            };
        } catch (error) {
            return this.handleError(error, 'Event creation error');
        }
    }

    async addRequirement(requirementData, token) {
        try {
            const response = await this.request('POST', '/hk-roadmap/requirement/add', requirementData, token);
            return {
                success: true,
                data: response,
                error: null
            };
        } catch (error) {
            return this.handleError(error, 'Requirement creation error');
        }
    }

    async request(method, endpoint, data = null, token = null) {
        const url = `${this.baseURL}${endpoint}`;
        const headers = {
            'Content-Type': 'application/json',
        };

        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }

        const options = {
            method,
            headers,
            body: data ? JSON.stringify(data) : null,
        };

        try {
            const response = await this.request('POST', '/hk-roadmap/admin/login', credentials);
            return {
                success: true,
                data: response, // Ensure the response contains the token
                error: null
            };
        } catch (error) {
            return this.handleError(error, 'Login error');
        }
    }

    async registerAdmin(adminData) {
        try {
            const response = await this.request('POST', '/hk-roadmap/admin/register', adminData);
            return {
                success: true,
                data: response,
                error: null
            };
        } catch (error) {
            return this.handleError(error, 'Registration error');
        }
    }

    async requestOTP(emailData) {
        try {
            console.log('Attempting to send OTP to:', emailData.email);
            const response = await this.request('POST', '/hk-roadmap/admin/request-otp', emailData);
            
            // Check if the response indicates success
            if (response.success) {
                return {
                    success: true,
                    data: response,
                    error: null
                };
            } else {
                // Handle the case where the email is not found
                return {
                    success: false,
                    data: null,
                    error: {
                        message: response.message || 'Failed to send OTP. Please try again.',
                        status: 404 // or appropriate status code
                    }
                };
            }
        } catch (error) {
            return this.handleError(error, 'OTP request error');
        }
    }

    async verifyOTP(otpData) {
        try {
            console.log('Verifying OTP with data:', otpData);
            const response = await this.request('POST', '/hk-roadmap/admin/verify-otp', otpData);
            
            if (response.message && response.message.includes('OTP verified successfully')) {
                console.log('OTP verified successfully');
                return {
                    success: true,
                    data: response,
                    error: null
                };
            }

            console.error('OTP verification failed:', response.message);
            return {
                success: false,
                data: null,
                error: {
                    message: response.message || 'OTP verification failed',
                    status: response.status || 500,
                    details: response.details || null
                }
            };

        } catch (error) {
            return this.handleError(error, 'OTP verification error');
        }
    }

    async changePassword(passwordData) {
        try {
            console.log('Attempting password change with data:', passwordData);
            
            if (!passwordData.email || !passwordData.newPassword) {
                return {
                    success: false,
                    data: null,
                    error: {
                        message: 'Email and new password are required',
                        status: 400,
                        details: null
                    }
                };
            }

            const payload = {
                email: passwordData.email,
                new_password: passwordData.newPassword
            };

            const response = await this.request('POST', '/hk-roadmap/admin/change-password', payload);

            if (response.message && response.message.includes('Password changed successfully.')) {
                console.log('Password changed successfully');
                return {
                    success: true,
                    data: response,
                    error: null
                };
            }

            console.error('Password change failed:', response.message);
            return {
                success: false,
                data: null,
                error: {
                    message: response.message || 'Password change failed',
                    status: response.status || 500,
                    details: response.details || null
                }
            };

        } catch (error) {
            return this.handleError(error, 'Password change error');
        }
    }
}
