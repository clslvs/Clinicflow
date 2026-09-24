// assets/js/auth.js

document.addEventListener('DOMContentLoaded', () => {
    
    // --- LOGIN FORM HANDLING ---
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const submitBtn = loginForm.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.textContent;
            submitBtn.textContent = 'Signing in...';
            submitBtn.disabled = true;

            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const roleInput = document.querySelector('input[name="role"]:checked');
            const role = roleInput ? roleInput.value : 'patient';
            
            let alertBox = document.getElementById('auth-alert');
            if (!alertBox) {
                alertBox = document.createElement('div');
                alertBox.id = 'auth-alert';
                loginForm.insertBefore(alertBox, loginForm.firstChild);
            }

            try {
                const response = await fetch('backend/login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, password, role })
                });

                const result = await response.json();

                if (result.success) {
                    // Redirect based on role
                    if (result.role === 'patient') {
                        window.location.href = 'patient/dashboard.html';
                    } else if (result.role === 'staff') {
                        window.location.href = 'staff/dashboard.html';
                    } else if (result.role === 'admin') {
                        window.location.href = 'admin/users.html';
                    }
                } else {
                    showAlert(alertBox, result.message, 'error');
                    submitBtn.textContent = originalBtnText;
                    submitBtn.disabled = false;
                }
            } catch (error) {
                console.error(error);
                showAlert(alertBox, 'A network error occurred. Make sure XAMPP is running and the database is configured.', 'error');
                submitBtn.textContent = originalBtnText;
                submitBtn.disabled = false;
            }
        });
    }

    // --- REGISTER FORM HANDLING ---
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const submitBtn = registerForm.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.textContent;
            submitBtn.textContent = 'Creating Account...';
            submitBtn.disabled = true;

            const firstName = document.getElementById('firstName').value;
            const lastName = document.getElementById('lastName').value;
            const email = document.getElementById('email').value;
            const phone = document.getElementById('phone').value;
            const password = document.getElementById('password').value;
            
            let alertBox = document.getElementById('auth-alert');
            if (!alertBox) {
                alertBox = document.createElement('div');
                alertBox.id = 'auth-alert';
                registerForm.insertBefore(alertBox, registerForm.firstChild);
            }

            try {
                const response = await fetch('backend/register.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ firstName, lastName, email, phone, password, role: 'patient' })
                });

                const result = await response.json();

                if (result.success) {
                    showAlert(alertBox, 'Registration successful! Redirecting...', 'success');
                    setTimeout(() => {
                        window.location.href = 'patient/dashboard.html';
                    }, 1500);
                } else {
                    showAlert(alertBox, result.message, 'error');
                    submitBtn.textContent = originalBtnText;
                    submitBtn.disabled = false;
                }
            } catch (error) {
                console.error(error);
                showAlert(alertBox, 'A network error occurred. Make sure XAMPP is running and the database is configured.', 'error');
                submitBtn.textContent = originalBtnText;
                submitBtn.disabled = false;
            }
        });
    }

    // Utility function to show alerts
    function showAlert(element, message, type) {
        if (!element) return;
        element.textContent = message;
        element.className = `auth-alert ${type}`; // Add classes like 'error' or 'success'
        element.style.display = 'block';
    }
});
