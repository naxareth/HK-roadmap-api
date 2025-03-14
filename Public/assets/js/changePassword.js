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
    // Get role and email from URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const role = urlParams.get('role');
    const email = decodeURIComponent(urlParams.get('email'));

    // Update forms with email and role
    const emailInputs = document.querySelectorAll('input[name="email"]');
    emailInputs.forEach(input => input.value = email);
    
    const roleInputs = document.querySelectorAll('input[name="role"]');
    roleInputs.forEach(input => input.value = role);

    // Verification form handler
    const verifyForm = document.getElementById('verifyForm');
    if (verifyForm) {
        verifyForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            clearError();

            const otp = verifyForm.querySelector('input[name="otp"]').value.trim();

            try {
                let response;
                if (role === 'admin') {
                    response = await apiService.verifyAdminOTP({ email, otp });
                } else {
                    response = await apiService.verifyStaffOTP({ email, otp });
                }

                if (response.success) {
                    verifyForm.style.display = 'none';
                    document.getElementById('changePasswordForm').style.display = 'block';
                } else {
                    showError(response.message || `${role} OTP verification failed`);
                }
            } catch (error) {
                showError('Error verifying OTP');
            }
        });
    }

    // Password change form handler
    const changePasswordForm = document.getElementById('changePasswordForm');
    if (changePasswordForm) {
        changePasswordForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            clearError();

            const newPassword = changePasswordForm.querySelector('input[name="new-password"]').value.trim();
            const confirmPassword = changePasswordForm.querySelector('input[name="confirm-password"]').value.trim();

            if (newPassword !== confirmPassword) {
                showError('Passwords do not match');
                return;
            }

            try {
                let response;
                if (role === 'admin') {
                    response = await apiService.changeAdminPassword({ email, newPassword });
                } else {
                    response = await apiService.changeStaffPassword({ email, newPassword });
                }

                if (response.success) {
                    alert('Password changed successfully!');
                    window.location.href = 'login.html';
                } else {
                    showError(response.message || `${role} password change failed`);
                }
            } catch (error) {
                showError('Error changing password');
            }
        });
    }
});