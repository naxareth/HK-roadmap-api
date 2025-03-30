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

function validateLogin(role, email, password) {
    if (!role || !email || !password) {
        showError('All fields are required');
        return false;
    }
    if (!/\S+@\S+\.\S+/.test(email)) {
        showError('Invalid email format');
        return false;
    }
    return true;
}

document.addEventListener('DOMContentLoaded', function() {

    localStorage.removeItem('passwordResetData');
    localStorage.removeItem("hasSeenWelcomeScreen");

    const authToken = localStorage.getItem('authToken');
    if (authToken) {
        window.location.href = 'dashboard.html';
        return; 
    }

    const closeButtons = document.querySelectorAll('.close-popup');
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            this.parentElement.parentElement.style.display = 'none';
        });
    });

    document.getElementById('togglePassword').addEventListener('click', function() {
        const passwordField = document.getElementById('password');
        const type = passwordField.type === 'password' ? 'text' : 'password';
        passwordField.type = type;
        this.classList.toggle('fa-eye');
        this.classList.toggle('fa-eye-slash');
    });

    const loginForm = document.getElementById('loginForm');
    
    loginForm.addEventListener('submit', async function(event) {
        event.preventDefault();
        clearError();
    
        const roleSelect = this.querySelector('select[name="role"]');
        const emailInput = this.querySelector('input[name="email"]');
        const passwordInput = this.querySelector('input[name="password"]');
    
        const role = roleSelect.value;
        const email = emailInput.value.trim();
        const password = passwordInput.value.trim();
    
        if (!validateLogin(role, email, password)) return;
    
        const submitButton = this.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.textContent = 'Logging in...';
    
        try {
            let response;
            if (role === 'admin') {
                response = await apiService.verifyAdminCredentials({ email, password });
            } else {
                response = await apiService.verifyStaffCredentials({ email, password });
            }
    
            if (response.success) {
                const token = response.data.token;
                console.log('Redirecting to:', window.location.href); 
                localStorage.setItem('lastRedirect', new Date().toISOString());
                localStorage.setItem('authToken', token); 
                localStorage.setItem('userRole', role);
                showSuccess(`${role.toUpperCase()} login successful! Redirecting...`);
                apiService.setAuthToken(token);
                showSuccess('Logging in successfully! Please wait...');

                // Redirect based on role
                const redirectUrl = role === 'admin' ? 'dashboard.html' : 'staffDashboard.html';
                setTimeout(() => {
                    window.location.href = redirectUrl;
                }, 2000);
                
            } else {
                showError(response.message || `Failed to login as ${role}`);
            }
        } catch (error) {
            handleNetworkError(error);
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = 'Login';
        }
    });
});
