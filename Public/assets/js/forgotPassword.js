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


// Add success message handling
function showSuccess(message) {
    const successPopup = document.getElementById('successPopup');
    const successText = document.getElementById('successText');
    if (successPopup && successText) {
        successText.textContent = message;
        successPopup.style.display = 'block';
        setTimeout(() => {
            successPopup.style.display = 'none';
        }, 3000);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Close button handlers
    const closeButtons = document.querySelectorAll('.close-popup');
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const popup = this.closest('.popup');
            if (popup) {
                popup.style.display = 'none';
            }
        });
    });

    // Form submission handler
    const recoveryForm = document.querySelector('.Recovery');
    
    if (recoveryForm) {
        recoveryForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            clearError();
            
            const roleSelect = recoveryForm.querySelector('select[name="role"]');
            const emailInput = recoveryForm.querySelector('input[name="email"]');
            
            const role = roleSelect.value;
            const email = emailInput.value.trim();

            if (!role || !email) {
                showError('Please select a role and enter your email');
                return;
            }

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
                    showSuccess('OTP has been sent to your email');
                    localStorage.setItem('passwordResetData', JSON.stringify({
                        role: role,
                        email: email
                    }));
                    setTimeout(() => {
                        window.location.href = `changePassword.html`;
                    }, 1500);
                } else {
                    showError(response.message || `${role} OTP request failed`);
                }
            } catch (error) {
                console.error('OTP request error:', error);
                showError(error.message || 'An error occurred while sending OTP');
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = 'Send OTP';
            }
        });
    }
});