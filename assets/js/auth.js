document.addEventListener('DOMContentLoaded', () => {

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
                    if (result.role === 'patient') {
                        window.location.href = 'patient/dashboard.html';
                    } else if (result.role === 'staff') {
                        window.location.href = 'staff/dashboard.html';
                    } else if (result.role === 'admin') {
                        window.location.href = 'admin/users.html';
                    } else if (result.role === 'doctor') {
                        window.location.href = 'doctor/dashboard.html';
                    }
                } else {
                    showAlert(alertBox, result.message, 'error');
                    submitBtn.textContent = originalBtnText;
                    submitBtn.disabled = false;
                }
            } catch (error) {
                console.error(error);
                showAlert(alertBox, 'A network error occurred. Make sure the server and database are configured.', 'error');
                submitBtn.textContent = originalBtnText;
                submitBtn.disabled = false;
            }
        });
    }

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
            const isPriority = document.getElementById('isPriority') ? document.getElementById('isPriority').checked : false;
            const agreeTerms = document.getElementById('agreeTerms') ? document.getElementById('agreeTerms').checked : false;

            let alertBox = document.getElementById('auth-alert');
            if (!alertBox) {
                alertBox = document.createElement('div');
                alertBox.id = 'auth-alert';
                registerForm.insertBefore(alertBox, registerForm.firstChild);
            }

            if (!agreeTerms) {
                showAlert(alertBox, 'You must agree to the Terms & Conditions and Privacy Policy.', 'error');
                submitBtn.textContent = originalBtnText;
                submitBtn.disabled = false;
                return;
            }

            try {
                const response = await fetch('backend/register.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ firstName, lastName, email, phone, password, role: 'patient', is_priority: isPriority, terms_agreed: agreeTerms })
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
                showAlert(alertBox, 'A network error occurred. Make sure the server and database are configured.', 'error');
                submitBtn.textContent = originalBtnText;
                submitBtn.disabled = false;
            }
        });
    }

    // Password Visibility Toggle (Show / Hide password)
    const toggleButtons = document.querySelectorAll('.toggle-password-btn');
    toggleButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const wrapper = btn.closest('.password-input-wrapper');
            if (!wrapper) return;
            const input = wrapper.querySelector('input');
            if (!input) return;

            const eyeIcon = btn.querySelector('.eye-icon');
            const eyeOffIcon = btn.querySelector('.eye-off-icon');
            const isPassword = input.type === 'password';

            input.type = isPassword ? 'text' : 'password';

            if (eyeIcon && eyeOffIcon) {
                eyeIcon.style.display = isPassword ? 'none' : 'block';
                eyeOffIcon.style.display = isPassword ? 'block' : 'none';
            }

            const labelText = isPassword ? 'Hide password' : 'Show password';
            btn.setAttribute('aria-label', labelText);
            btn.setAttribute('title', labelText);
        });
    });

    // Forgot Password Modal Handlers
    const forgotModal = document.getElementById('forgotPasswordModal');
    const openForgotBtn = document.getElementById('forgotPasswordBtn');
    const closeForgotBtn = document.getElementById('closeForgotModal');
    const backToSignInLink = document.getElementById('backToSignInLink');
    const forgotForm = document.getElementById('forgotPasswordForm');
    const forgotEmailInput = document.getElementById('forgotEmail');
    const forgotAlert = document.getElementById('forgot-alert');
    const forgotSubmitBtn = document.getElementById('forgotSubmitBtn');

    function openForgotModal() {
        if (!forgotModal) return;
        const loginEmail = document.getElementById('email');
        if (loginEmail && loginEmail.value && forgotEmailInput) {
            forgotEmailInput.value = loginEmail.value;
        }
        if (forgotAlert) {
            forgotAlert.style.display = 'none';
            forgotAlert.innerHTML = '';
        }
        forgotModal.classList.add('active');
        forgotModal.setAttribute('aria-hidden', 'false');
        setTimeout(() => {
            if (forgotEmailInput) forgotEmailInput.focus();
        }, 150);
    }

    function closeForgotModal() {
        if (!forgotModal) return;
        forgotModal.classList.remove('active');
        forgotModal.setAttribute('aria-hidden', 'true');
    }

    if (openForgotBtn) {
        openForgotBtn.addEventListener('click', (e) => {
            e.preventDefault();
            openForgotModal();
        });
    }

    if (closeForgotBtn) {
        closeForgotBtn.addEventListener('click', closeForgotModal);
    }

    if (backToSignInLink) {
        backToSignInLink.addEventListener('click', (e) => {
            e.preventDefault();
            closeForgotModal();
            const emailInput = document.getElementById('email');
            if (emailInput) emailInput.focus();
        });
    }

    if (forgotModal) {
        forgotModal.addEventListener('click', (e) => {
            if (e.target === forgotModal) closeForgotModal();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && forgotModal.classList.contains('active')) {
                closeForgotModal();
            }
        });
    }

    if (forgotForm) {
        forgotForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const email = forgotEmailInput.value.trim();
            if (!email) return;

            const originalBtnText = forgotSubmitBtn.textContent;
            forgotSubmitBtn.textContent = 'Sending...';
            forgotSubmitBtn.disabled = true;

            try {
                const response = await fetch('backend/forgot_password.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email })
                });

                const result = await response.json();

                if (result.success) {
                    showAlert(forgotAlert, result.message, 'success');
                    if (!result.demo_mode) {
                        forgotEmailInput.value = '';
                    }
                } else {
                    showAlert(forgotAlert, result.message || 'Unable to process request.', 'error');
                }
            } catch (err) {
                console.error(err);
                showAlert(forgotAlert, 'A network error occurred. Please try again.', 'error');
            } finally {
                forgotSubmitBtn.textContent = originalBtnText;
                forgotSubmitBtn.disabled = false;
            }
        });
    }

    function showAlert(element, message, type) {
        if (!element) return;
        element.innerHTML = message;
        element.className = `auth-alert ${type}`;
        element.style.display = 'block';
    }
});

let currentSlide = 0;
const slides = document.querySelectorAll('.slide');
const indicators = document.querySelectorAll('.indicator');
const slideTitles = [
    "Perfecting Smiles,<br>Restoring Confidence",
    "Expert Care for Your<br>Whole Family",
    "Modern Equipment,<br>Comfortable Experience"
];
const slideDescs = [
    "Experience the best dental care in the city.",
    "Comprehensive treatments for all ages.",
    "State-of-the-art facility for your comfort."
];

function setSlide(index) {
    if (slides.length === 0) return;

    slides.forEach(s => s.classList.remove('active'));
    indicators.forEach(i => i.classList.remove('active'));

    currentSlide = index;
    slides[currentSlide].classList.add('active');
    indicators[currentSlide].classList.add('active');

    const titleEl = document.getElementById('slide-title');
    const descEl = document.getElementById('slide-desc');
    if (titleEl && descEl) {
        titleEl.innerHTML = slideTitles[currentSlide];
        descEl.innerHTML = slideDescs[currentSlide];
    }
}

if (slides.length > 0) {
    setInterval(() => {
        let next = (currentSlide + 1) % slides.length;
        setSlide(next);
    }, 5000);
}
