import ApiService from './apiService.js';

const apiService = new ApiService('http://localhost:8000');

function showError(message) {
    const errorElement = document.getElementById('errorMessage');
    if (!errorElement) {
        console.error('Error element not found');
        return;
    }
    errorElement.textContent = message;
    errorElement.style.display = 'block';
}

function clearError() {
    const errorElement = document.getElementById('errorMessage');
    if (!errorElement) {
        console.error('Error element not found');
        return;
    }
    errorElement.style.display = 'none';
}

document.addEventListener('DOMContentLoaded', function() {
    const verifyForm = document.getElementById('verifyForm');
    const changePasswordForm = document.getElementById('changePasswordForm');

    if (verifyForm) {
        verifyForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            
            clearError();
            
            const email = verifyForm.querySelector('input[name="email"]').value.trim();
            const otp = verifyForm.querySelector('input[name="otp"]').value.trim();

            if (!email || !otp) {
                showError('Please fill in all fields');
                return;
            }

            // Disable submit button during request
            const submitButton = verifyForm.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.textContent = 'Verifying...';

            try {
                const response = await apiService.verifyOTP({ email, otp });
                
                if (response.success) {
                    // Show password change form
                    verifyForm.style.display = 'none';
                    changePasswordForm.style.display = 'block';
                } else {
                    showError(response.error.message || 'Verification failed. Please try again.');
                }
            } catch (error) {
                console.error('Verification error:', error);
                showError('An error occurred during verification. Please try again.');
            } finally {
                // Re-enable submit button
                const submitButton = verifyForm.querySelector('button[type="submit"]');
                submitButton.disabled = false;
                submitButton.textContent = 'Verify';
            }
        });
    }

    if (changePasswordForm) {
        changePasswordForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            
            clearError();

            const email = changePasswordForm.querySelector('input[name="email"]').value.trim();
            const newPassword = changePasswordForm.querySelector('input[name="new-password"]').value.trim();
            const confirmedPassword = changePasswordForm.querySelector('input[name="confirmed-password"]').value.trim();

            if (!newPassword || !confirmedPassword) {
                showError('Please fill in all fields');
                return;
            }

            if (newPassword !== confirmedPassword) {
                showError('Passwords do not match');
                return;
            }

            // Disable submit button during request
            const submitButton = changePasswordForm.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.textContent = 'Changing Password...';

            try {
                const response = await apiService.changePassword({ 
                    email,
                    newPassword
                });
                
                if (response.success) {
                    alert('Password changed successfully!');
                    window.location.href = 'login.html';
                } else {
                    showError(response.error.message || 'Password change failed. Please try again.');
                }
            } catch (error) {
                console.error('Password change error:', error);
                showError('An error occurred while changing password. Please try again.');
            } finally {
                // Re-enable submit button
                const submitButton = changePasswordForm.querySelector('button[type="submit"]');
                submitButton.disabled = false;
                submitButton.textContent = 'Change Password';
            }
        });
    }
});
