import ApiService from './apiService.js';

const apiService = new ApiService('http://localhost:8000');

function showError(message) {
    const errorElement = document.getElementById('errorMessage');
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.style.display = 'block';
    }
}

function clearError() {
    const errorElement = document.getElementById('errorMessage');
    if (errorElement) {
        errorElement.style.display = 'none';
    }
}

function validateInput(email, password) {
    if (!email || !password) {
        showError('Please fill in all fields');
        return false;
    }
    
    if (!/\S+@\S+\.\S+/.test(email)) {
        showError('Please enter a valid email address');
        return false;
    }
    
    return true;
}

document.addEventListener('DOMContentLoaded', function() {
    // Close popup when clicking the close button
    const closePopup = document.querySelector('.close-popup');
    if (closePopup) {
        closePopup.addEventListener('click', () => {
            const popup = document.getElementById('successPopup');
            popup.style.display = 'none';
        });
    }

    const loginForm = document.getElementById('loginForm');
    
    if (loginForm) {
        loginForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            
            clearError();
            
            const emailInput = loginForm.querySelector('input[name="email"]');
            const passwordInput = loginForm.querySelector('input[name="password"]');
            
            if (!emailInput || !passwordInput) {
                showError('Form fields not found');
                return;
            }
            
            const email = emailInput.value.trim();
            const password = passwordInput.value.trim();


            if (!validateInput(email, password)) {
                return;
            }
            
            // Disable submit button during request
            const submitButton = loginForm.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.textContent = 'Logging in...';

            try {
                const response = await apiService.verifyAdminCredentials({ email, password });
                
                if (response.success) {
                    // Show success popup
                    const popup = document.getElementById('successPopup');
                    popup.style.display = 'block';
                    
                    // Redirect after 2 seconds
                    setTimeout(() => {
                        window.location.href = 'home.html';
                    }, 2000);
                } else {
                    showError(response.message || 'Login failed. Please check your credentials.');
                }
            } catch (error) {
                console.error('Login error:', error);
                const errorMessage = error.message || 'An error occurred during login. Please try again.';
                if (error.message.includes('NetworkError')) {
                    showError('Network error. Please check your internet connection.');
                } else {
                    showError(errorMessage);
                }
            } finally {
                // Re-enable submit button
                const submitButton = loginForm.querySelector('button[type="submit"]');
                submitButton.disabled = false;
                submitButton.textContent = 'Login';
            }
        });
    }
});
