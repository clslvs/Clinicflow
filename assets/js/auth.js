

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
                showAlert(alertBox, 'A network error occurred. Make sure Laragon is running and the database is configured.', 'error');
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
                    body: JSON.stringify({ firstName, lastName, email, phone, password, role: 'patient', is_priority: isPriority })
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
                showAlert(alertBox, 'A network error occurred. Make sure Laragon is running and the database is configured.', 'error');
                submitBtn.textContent = originalBtnText;
                submitBtn.disabled = false;
            }
        });
    }

    function showAlert(element, message, type) {
        if (!element) return;
        element.textContent = message;
        element.className = `auth-alert ${type}`; // Add classes like 'error' or 'success'
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
