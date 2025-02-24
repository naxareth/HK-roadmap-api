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
            
            const email = recoveryForm.querySelector('input[name="email"]').value.trim();

            if (!email) {
                showError('Please enter your email address');
                return;
            }

            
            // Disable submit button during request
            const submitButton = recoveryForm.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.textContent = 'Sending OTP...';

            try {
                const response = await apiService.requestOTP({ email });
                if (response.success) {
                    alert('OTP has been sent to your email');
                    window.location.href = 'changePassword.html';
                } else {
                    alert(response.message || 'Failed to send OTP. Please try again.');

                }
            } catch (error) {
                console.error('OTP request error:', error);
                const errorMessage = error.message || 'An error occurred while sending OTP. Please try again.';
                if (error.message.includes('NetworkError')) {
                    showError('Network error. Please check your internet connection.');

                } else {
                    showError(errorMessage);

                }
            } finally {
                // Re-enable submit button
                const submitButton = recoveryForm.querySelector('button[type="submit"]');
                submitButton.disabled = false;
                submitButton.textContent = 'Send OTP';
            }
        });
    }
});
