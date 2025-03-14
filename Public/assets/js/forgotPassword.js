import ApiService from './apiService.js';

const apiService = new ApiService('http://localhost:8000');

function showError(message) {
    const errorElement = document.getElementById('errorMessage');
    const errorText = document.getElementById('errorText');
    if (errorElement && errorText) {
        errorText.textContent = message;
        errorElement.style.display = 'block';
    }
}

function clearError() {
    const errorElement = document.getElementById('errorMessage');
    if (errorElement) {
        errorElement.style.display = 'none';
    }
}


document.addEventListener('DOMContentLoaded', function() {
    const recoveryForm = document.querySelector('.Recovery');
    
    if (recoveryForm) {
        recoveryForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            clearError();
            
            // Get form elements
            const roleSelect = recoveryForm.querySelector('select[name="role"]');
            const emailInput = recoveryForm.querySelector('input[name="email"]');
            
            const role = roleSelect.value;
            const email = emailInput.value.trim();

            // Validate inputs
            if (!role || !email) {
                showError('Please select a role and enter your email');
                return;
            }

            // Disable submit button during request
            const submitButton = recoveryForm.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.textContent = 'Sending OTP...';

            try {
                let response;
                if (role === 'admin') {
                    response = await apiService.requestAdminOTP({ email });
                } else {
                    response = await apiService.requestStaffOTP({ email });
                }

                if (response.success) {
                    alert('OTP has been sent to your email');
                    // Redirect with role parameter
                    window.location.href = `changePassword.html?role=${role}&email=${encodeURIComponent(email)}`;
                } else {
                    showError(response.message || `${role} OTP request failed`);
                }
            } catch (error) {
                console.error('OTP request error:', error);
                const errorMessage = error.message || 'An error occurred while sending OTP';
                showError(errorMessage);
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = 'Send OTP';
            }
        });
    }
});

