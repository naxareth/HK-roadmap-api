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

function setupPasswordToggles() {
    document.querySelectorAll('.toggle-password').forEach(icon => {
        icon.addEventListener('click', function() {
            const input = this.previousElementSibling;
            const type = input.type === 'password' ? 'text' : 'password';
            input.type = type;
            this.classList.toggle('fa-eye-slash');
            this.classList.toggle('fa-eye');
        });
    });
}

document.addEventListener('DOMContentLoaded', function() {

    setupPasswordToggles()
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

    const resetData = JSON.parse(localStorage.getItem('passwordResetData'));
    
    if (!resetData || !resetData.role || !resetData.email) {
        showError('Invalid password reset request');
        setTimeout(() => {
            localStorage.removeItem('passwordResetData');
            window.location.href = 'login.html';
        }, 3000);
        return;
    }

    const { role, email } = resetData;

    // Display account info
    document.getElementById('displayRole').textContent = role === 'admin' ? 'Administrator' : 'Staff';
    document.getElementById('displayEmail').textContent = email;

    // Cleanup on successful password change
    localStorage.removeItem('passwordResetData');

    // Validate parameters
    if (!role || !email) {
        showError('Invalid password reset request');
        setTimeout(() => window.location.href = 'login.html', 3000);
        return;
    }

    // Decode email only if it's not null
    const decodedEmail = email ? decodeURIComponent(email) : '';

    // Display account info
    document.getElementById('displayRole').textContent = role === 'admin' ? 'Administrator' : 'Staff';
    document.getElementById('displayEmail').textContent = decodedEmail;

    // Set hidden fields
    document.getElementById('roleField').value = role;
    document.getElementById('emailField').value = decodedEmail;

    // OTP Verification Form
    const verifyForm = document.getElementById('verifyForm');
    if (verifyForm) {
        verifyForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            clearError();

            const otp = verifyForm.querySelector('input[name="otp"]').value.trim();
            const submitButton = verifyForm.querySelector('button[type="submit"]');
            
            submitButton.disabled = true;
            submitButton.textContent = 'Verifying...';

            try {
                let response;
                if (role === 'admin') {
                    response = await apiService.verifyAdminOTP({ email, otp });
                } else {
                    response = await apiService.verifyStaffOTP({ email, otp });
                }

                if (response.success) {
                    showSuccess('OTP verified successfully');
                    verifyForm.style.display = 'none';
                    document.getElementById('changePasswordForm').style.display = 'block';
                } else {
                    showError(response.message || 'Invalid OTP code');
                }
            } catch (error) {
                showError('Error verifying OTP. Please try again.');
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = 'Verify';
            }
        });
    }

    // Password Change Form
    const changePasswordForm = document.getElementById('changePasswordForm');
    if (changePasswordForm) {
        changePasswordForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            clearError();

            const newPassword = changePasswordForm.querySelector('input[name="new-password"]').value.trim();
            const confirmPassword = changePasswordForm.querySelector('input[name="confirmed-password"]').value.trim();
            const submitButton = changePasswordForm.querySelector('button[type="submit"]');

            if (newPassword !== confirmPassword) {
                showError('Passwords do not match');
                return;
            }

            if (newPassword.length < 8 || !/[!@#$%^&*(),.?":{}|<>]/.test(newPassword)) {
                showError('Password must be at least 8 characters with a special character');
                return;
            }

            submitButton.disabled = true;
            submitButton.textContent = 'Changing...';

            try {
                let response;
                if (role === 'admin') {
                    response = await apiService.changeAdminPassword({ email, newPassword });
                } else {
                    response = await apiService.changeStaffPassword({ email, newPassword });
                }

                if (response.success) {
                    showSuccess('Password changed successfully! Redirecting...');
                    setTimeout(() => {
                        window.location.href = 'login.html';
                    }, 1500);
                } else {
                    showError(response.message || 'Password change failed');
                }
            } catch (error) {
                showError('Error changing password. Please try again.');
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = 'Change Password';
            }
        });
    }
});