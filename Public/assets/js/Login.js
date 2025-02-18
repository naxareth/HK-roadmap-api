<script>
import apiService from './apiService.js';

document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    
    if (loginForm) {
        loginForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            try {
                const response = await apiService.login({ email, password });
                
                if (response.success) {
                    // Store token and redirect
                    localStorage.setItem('authToken', response.token);
                    window.location.href = '/Home.html';
                } else {
                    alert('Login failed: ' + response.message);
                }
            } catch (error) {
                console.error('Login error:', error);
                alert('An error occurred during login. Please try again.');
            }
        });
    }
});
</script>
