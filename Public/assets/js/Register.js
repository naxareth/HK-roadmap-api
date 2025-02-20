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

function validateRegistration(name, email, password, confirmPassword) {
    if (!name || !email || !password || !confirmPassword) {
        showError('Please fill in all fields');
        return false;
    }
    
    if (!/\S+@\S+\.\S+/.test(email)) {
        showError('Please enter a valid email address');
        return false;
    }
    
    if (password !== confirmPassword) {
        showError('Passwords do not match');
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

    const registerForm = document.getElementById('registerForm');
    
    if (registerForm) {
        registerForm.addEventListener('submit', async function(event) {
            console.log('Form submission started');
            event.preventDefault();
            
            console.log('Clearing previous errors');
            clearError();

            console.log('Form inputs:', { // Debug log
                name: registerForm.querySelector('input[name="name"]')?.value,
                email: registerForm.querySelector('input[name="email"]')?.value,
                password: registerForm.querySelector('input[name="password"]')?.value,
                confirmPassword: registerForm.querySelector('input[name="confirm-password"]')?.value
            });

            
            const nameInput = registerForm.querySelector('input[name="name"]');
            const emailInput = registerForm.querySelector('input[name="email"]');
            const passwordInput = registerForm.querySelector('input[name="password"]');
            const confirmPasswordInput = registerForm.querySelector('input[name="confirm-password"]');
            
            if (!nameInput || !emailInput || !passwordInput || !confirmPasswordInput) {
                console.error('Form fields not found');
                showError('Form fields not found');
                return;
            }

            console.log('All form fields found');

            console.log('All form fields found'); // Debug log

            
            const name = nameInput.value.trim();
            const email = emailInput.value.trim();
            const password = passwordInput.value.trim();
            const confirmPassword = confirmPasswordInput.value.trim();
            
            console.log('Trimmed values:', { // Debug log
                name,
                email,
                password,
                confirmPassword
            });



            if (!validateRegistration(name, email, password, confirmPassword)) {
                console.log('Validation failed');
                return;
            }

            console.log('Validation successful');

            
            // Disable submit button during request
            const submitButton = registerForm.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.textContent = 'Registering...';

            try {
                console.log('Sending registration request');
                const response = await apiService.registerAdmin({ 


                    name, 
                    email, 
                    password, 
                    confirm_password: confirmPassword 
                });
                
                if (response.success) {
                    console.log('Registration successful');
                    // Show success popup
                    const popup = document.getElementById('successPopup');
                    if (popup) {
                        popup.style.display = 'block';
                        console.log('Showing success popup');
                    }
                    
                    // Redirect after 2 seconds
                    setTimeout(() => {
                        console.log('Redirecting to login page');
                        window.location.href = 'login.html';
                    }, 2000);
                } else {
                    console.error('Registration failed:', response.message);
                    showError(response.message || 'Registration failed. Please try again.');
                }

            } catch (error) {
                console.error('Registration error:', error);
                const errorMessage = error.message || 'An error occurred during registration. Please try again.';
                if (error.message.includes('NetworkError')) {
                    console.error('Network error detected');
                    showError('Network error. Please check your internet connection.');
                } else {
                    console.error('Registration error:', errorMessage);
                    showError(errorMessage);
                }
            } finally {
                // Re-enable submit button
                const submitButton = registerForm.querySelector('button[type="submit"]');
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = 'Register';
                    console.log('Submit button re-enabled');
                }
            }

        });
    }
});
