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

function handleNetworkError(error) {
    console.error('Network error:', error);
    const errorMessage = error.message.includes('NetworkError') 
        ? 'Network error. Check your internet connection'
        : 'Server error. Please try again later';
    showError(errorMessage);
}

function validateRegistration(role, name, email, password, confirmPassword) {
    const errors = [];
    
    if (!role) errors.push('Select a role');
    if (!name) errors.push('Name is required');
    if (!email) errors.push('Email is required');
    if (!password) errors.push('Password is required');
    if (!confirmPassword) errors.push('Confirm password is required');
    
    if (errors.length > 0) {
        showError(errors.join(', '));
        return false;
    }
    
    if (password !== confirmPassword) {
        showError('Passwords do not match');
        return false;
    }
    
    if (password.length < 8) {
        showError('Password must be at least 8 characters');
        return false;
    }
    
    return true;
}

document.addEventListener('DOMContentLoaded', function() {
    if (!registerForm) {
        console.error('Registration form not found!');
        return;
    }
    // Initialize password toggles first
    document.getElementById('togglePassword').addEventListener('click', function() {
        const passwordField = document.getElementById('password');
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

    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', async function(event) {
            event.preventDefault();
        clearError();

        const closeButtons = document.querySelectorAll('.close-popup');
        closeButtons.forEach(button => {
            button.addEventListener('click', function() {
                this.parentElement.parentElement.style.display = 'none';
            });
        });

        const roleSelect = this.querySelector('select[name="role"]');
        const nameInput = this.querySelector('input[name="name"]');
        const emailInput = this.querySelector('input[name="email"]');
        const passwordInput = this.querySelector('input[name="password"]');
        const confirmInput = this.querySelector('input[name="confirm-password"]');

        const role = roleSelect.value;
        const name = nameInput.value.trim();
        const email = emailInput.value.trim();
        const password = passwordInput.value.trim();
        const confirmPassword = confirmInput.value.trim();

        if (!validateRegistration(role, name, email, password, confirmPassword)) return;

        // Disable submit button during request
        const submitButton = this.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.textContent = 'Registering...';

        try {
            let response;
            const baseData = { name, email, password, confirm_password: confirmPassword };
            
            if (role === 'admin') {
                response = await apiService.registerAdmin(baseData);
            } else {
                response = await apiService.registerStaff(baseData);
            }

            if (response.success) {
                
                console.log('Redirecting to:', window.location.href); 
                localStorage.setItem('lastRedirect', new Date().toISOString());
                showSuccess(`${role.toUpperCase()} registration successful! Redirecting...`);
                setTimeout(() => window.location.href = '/login.html', 1500);

            } else {
                showError(response.message || `Failed to register as ${role}`);
            }
        } catch (error) {
            handleNetworkError(error);
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = 'Register';
        }
    });
    }});
