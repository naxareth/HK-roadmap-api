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

function showSuccess() {
    const successPopup = document.getElementById('successPopup');
    if (successPopup) {
        successPopup.style.display = 'block';
    }
}

function validateChangePassword(currentPassword, newPassword, confirmNewPassword) {
    if (!currentPassword || !newPassword || !confirmNewPassword) {
        showError('Please fill in all fields');
        return false;
    }
    
    if (newPassword !== confirmNewPassword) {
        showError('New passwords do not match');
        return false;
    }
    
    if (newPassword.length < 8 || !/[!@#$%^&*(),.?":{}|<>]/.test(newPassword)) {
        showError('New password must be at least 8 characters long and include at least one special character');
        return false;
    }
    
    return true;
}

document.addEventListener('DOMContentLoaded', function() {
    const closeButtons = document.querySelectorAll('.close-popup');
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            this.parentElement.parentElement.style.display = 'none';
        });
    });

    document.getElementById('togglePassword').addEventListener('click', function() {
        const passwordField = document.getElementById('new-password');
        const type = passwordField.type === 'password' ? 'text' : 'password';
        passwordField.type = type;
        this.classList.toggle('fa-eye');
        this.classList.toggle('fa-eye-slash');
    });
    
    document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
        const confirmPasswordField = document.getElementById('confirm-password');
        const type = confirmPasswordField.type === 'password' ? 'text' : 'password';
        confirmPasswordField.type = type;
        this.classList.toggle('fa-eye');
        this.classList.toggle('fa-eye-slash');
    });
    
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

            if (!validateChangePassword(email, newPassword, confirmedPassword)) {
                console.log('Validation failed');
                showError('Validation failed');
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
                    showSuccess('Password changed successfully!');
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
