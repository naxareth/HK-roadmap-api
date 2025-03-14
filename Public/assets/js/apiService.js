export default class ApiService {
    constructor(baseURL) {
        this.baseURL = baseURL;
    }

    setAuthToken(token) {
        this.authToken = token; // Store the token in the service

        // Set the default headers for future requests
        this.defaultHeaders = {
            'Authorization': `Bearer ${token}`,
            // Add other headers if needed
        };
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
            const response = await fetch(url, options);
            const responseText = await response.text();

            let responseData;
            try {
                responseData = JSON.parse(responseText);
            } catch (error) {
                responseData = { message: responseText };
            }

            if (!response.ok) {
                throw {
                    message: responseData.message || 'Request failed',
                    status: response.status,
                    details: responseData.details || null
                };
            }


            return responseData;

        } catch (error) {
            console.error('API request failed:', error);
            throw error; 
        }
    }

    async verifyAdminCredentials(credentials) {
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

    async verifyStaffCredentials(credentials) {
        try {
            const response = await fetch(`${this.baseURL}/hk-roadmap/staff/login`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.authToken}`
                },
                body: JSON.stringify(credentials)
            });

            const responseData = await response.json();

            if (!response.ok) {
                return {
                    success: false,
                    data: null,
                    error: {
                        message: responseData.message || 'Staff authentication failed',
                        status: response.status,
                        details: responseData.details
                    }
                };
            }

            return { success: true, data: responseData, error: null };

        } catch (error) {
            return this.handleError(error, 'Staff Login');
        }
    }

    async registerStaff(staffData) {
        try {
            const response = await fetch(`${this.baseURL}/hk-roadmap/staff/register`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.authToken}`
                },
                body: JSON.stringify(staffData)
            });

            const responseData = await response.json();

            if (!response.ok) {
                return {
                    success: false,
                    data: null,
                    error: {
                        message: responseData.message || 'Staff registration failed',
                        status: response.status,
                        details: responseData.details
                    }
                };
            }

            return { success: true, data: responseData, error: null };

        } catch (error) {
            return this.handleError(error, 'Staff Registration');
        }
    }

    async requestStaffOTP(emailData) {
        try {
            const response = await fetch(`${this.baseURL}/hk-roadmap/staff/request-otp`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.authToken}`
                },
                body: JSON.stringify(emailData)
            });

            const responseData = await response.json();

            if (responseData.success) {
                return { success: true, data: responseData, error: null };
            }

            return {
                success: false,
                data: null,
                error: {
                    message: responseData.message || 'Failed to send OTP to staff',
                    status: response.status || 404,
                    details: responseData.details
                }
            };

        } catch (error) {
            return this.handleError(error, 'Staff OTP Request');
        }
    }

    async verifyStaffOTP(otpData) {
        try {
            const response = await fetch(`${this.baseURL}/hk-roadmap/staff/verify-otp`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.authToken}`
                },
                body: JSON.stringify(otpData)
            });

            const responseData = await response.json();

            if (responseData.message && responseData.message.includes('OTP verified successfully')) {
                return { success: true, data: responseData, error: null };
            }

            return {
                success: false,
                data: null,
                error: {
                    message: responseData.message || 'Staff OTP verification failed',
                    status: response.status || 500,
                    details: responseData.details
                }
            };

        } catch (error) {
            return this.handleError(error, 'Staff OTP Verification');
        }
    }

    async changeStaffPassword(passwordData) {
        try {
            if (!passwordData.email || !passwordData.newPassword) {
                return {
                    success: false,
                    data: null,
                    error: {
                        message: 'Staff email and new password are required',
                        status: 400,
                        details: null
                    }
                };
            }

            const response = await fetch(`${this.baseURL}/hk-roadmap/staff/change-password`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.authToken}`
                },
                body: JSON.stringify({
                    email: passwordData.email,
                    new_password: passwordData.newPassword
                })
            });

            const responseData = await response.json();

            if (responseData.message && responseData.message.includes('Password changed successfully')) {
                return { success: true, data: responseData, error: null };
            }

            return {
                success: false,
                data: null,
                error: {
                    message: responseData.message || 'Staff password change failed',
                    status: response.status || 500,
                    details: responseData.details
                }
            };

        } catch (error) {
            return this.handleError(error, 'Staff Password Change');
        }
    }
}