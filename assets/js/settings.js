// assets/js/settings.js - Patient Settings

document.addEventListener('DOMContentLoaded', () => {

    const inputFirstName = document.getElementById('input-firstname');
    const inputMiddleName = document.getElementById('input-middlename');
    const inputLastName = document.getElementById('input-lastname');
    const inputSex = document.getElementById('input-sex');
    const inputEmail = document.getElementById('input-email');
    const inputPhone = document.getElementById('input-phone');
    const personalPhoneHint = document.getElementById('personal-phone-hint');
    const personalPhoneCounter = document.getElementById('personal-phone-counter');

    const btnEditPersonal = document.getElementById('btn-edit-personal');
    const btnSavePersonal = document.getElementById('btn-save-personal');
    const personalForm = document.getElementById('personal-form');

    // Fetch Patient Settings
    fetch('../backend/get_settings.php')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const user = data.data;

                const initial = user.first_name ? user.first_name.charAt(0).toUpperCase() : 'P';
                const middleInitial = user.middle_name ? ` ${user.middle_name.charAt(0)}.` : '';
                const fullName = `${user.first_name || ''}${middleInitial} ${user.last_name || ''}`.trim();

                const sidebarAvatar = document.getElementById('sidebar-avatar');
                const sidebarName = document.getElementById('sidebar-name');
                if (sidebarAvatar) sidebarAvatar.textContent = initial;
                if (sidebarName) sidebarName.textContent = fullName;

                const settingsAvatar = document.getElementById('settings-avatar');
                const settingsName = document.getElementById('settings-name');
                const settingsPatientId = document.getElementById('settings-patient-id');
                if (settingsAvatar) settingsAvatar.textContent = initial;
                if (settingsName) settingsName.textContent = fullName;
                if (settingsPatientId) settingsPatientId.textContent = `Patient ID: ${user.patient_id_formatted}`;

                if (inputFirstName) inputFirstName.value = user.first_name || '';
                if (inputMiddleName) inputMiddleName.value = user.middle_name || '';
                if (inputLastName) inputLastName.value = user.last_name || '';
                if (inputSex) inputSex.value = user.sex || '';
                if (inputEmail) inputEmail.value = user.email || '';
                if (inputPhone) {
                    inputPhone.value = user.phone || '';
                    updatePhoneCounter(inputPhone.value);
                }

                const inputAllergies = document.getElementById('input-allergies');
                const inputConditions = document.getElementById('input-conditions');
                const inputPriority = document.getElementById('input-priority');
                if (inputAllergies) inputAllergies.value = user.allergies || '';
                if (inputConditions) inputConditions.value = user.medical_conditions || '';
                if (inputPriority) inputPriority.checked = user.is_priority === 1 || user.is_priority === true;
            } else if (data.message === 'Unauthorized' || data.message === 'Not logged in.') {
                window.location.href = '../login.html';
            } else {
                console.error("Failed to load settings:", data.message);
                alert(data.message || 'Could not load settings.');
            }
        })
        .catch(err => {
            console.error("Error fetching settings:", err);
        });

    // Phone Counter & Strict 11-digit Input Helper
    function updatePhoneCounter(val) {
        if (!personalPhoneCounter) return;
        const len = (val || '').length;
        personalPhoneCounter.textContent = `${len}/11`;
        if (personalPhoneHint) {
            personalPhoneHint.classList.remove('valid', 'invalid');
            if (len === 11) {
                personalPhoneHint.classList.add('valid');
            } else if (len > 0) {
                personalPhoneHint.classList.add('invalid');
            }
        }
    }

    if (inputPhone) {
        inputPhone.addEventListener('input', () => {
            // Strip any non-digit character and limit to 11 digits
            inputPhone.value = inputPhone.value.replace(/\D/g, '').slice(0, 11);
            updatePhoneCounter(inputPhone.value);
        });
    }

    // Personal Information Edit / Save
    if (btnEditPersonal && personalForm) {
        btnEditPersonal.addEventListener('click', () => {
            inputFirstName.removeAttribute('readonly');
            inputMiddleName.removeAttribute('readonly');
            inputLastName.removeAttribute('readonly');
            inputEmail.removeAttribute('readonly');
            inputPhone.removeAttribute('readonly');
            if (inputSex) inputSex.removeAttribute('disabled');

            if (personalPhoneHint) personalPhoneHint.style.display = 'flex';
            updatePhoneCounter(inputPhone.value);

            btnEditPersonal.style.display = 'none';
            btnSavePersonal.style.display = 'inline-block';
            inputFirstName.focus();
        });

        personalForm.addEventListener('submit', (e) => {
            e.preventDefault();

            const phoneVal = inputPhone.value.trim();
            if (phoneVal.length !== 11) {
                alert("Phone number must be exactly 11 digits (e.g., 09123456789).");
                inputPhone.focus();
                return;
            }

            btnSavePersonal.disabled = true;
            btnSavePersonal.textContent = 'Saving...';

            const payload = {
                first_name: inputFirstName.value.trim(),
                middle_name: inputMiddleName.value.trim(),
                last_name: inputLastName.value.trim(),
                sex: inputSex ? inputSex.value : null,
                email: inputEmail.value.trim(),
                phone: phoneVal
            };

            fetch('../backend/update_personal_info.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                btnSavePersonal.disabled = false;
                btnSavePersonal.textContent = 'Save';

                if (data.success) {
                    inputFirstName.setAttribute('readonly', true);
                    inputMiddleName.setAttribute('readonly', true);
                    inputLastName.setAttribute('readonly', true);
                    inputEmail.setAttribute('readonly', true);
                    inputPhone.setAttribute('readonly', true);
                    if (inputSex) inputSex.setAttribute('disabled', true);

                    if (personalPhoneHint) personalPhoneHint.style.display = 'none';
                    btnSavePersonal.style.display = 'none';
                    btnEditPersonal.style.display = 'inline-block';

                    // Update UI names and avatars
                    const initial = payload.first_name.charAt(0).toUpperCase();
                    const middleInitial = payload.middle_name ? ` ${payload.middle_name.charAt(0)}.` : '';
                    const fullName = `${payload.first_name}${middleInitial} ${payload.last_name}`.trim();

                    const sidebarAvatar = document.getElementById('sidebar-avatar');
                    const sidebarName = document.getElementById('sidebar-name');
                    if (sidebarAvatar) sidebarAvatar.textContent = initial;
                    if (sidebarName) sidebarName.textContent = fullName;

                    const settingsAvatar = document.getElementById('settings-avatar');
                    const settingsName = document.getElementById('settings-name');
                    if (settingsAvatar) settingsAvatar.textContent = initial;
                    if (settingsName) settingsName.textContent = fullName;

                    alert("Personal information updated successfully.");
                } else {
                    alert(data.message || "Failed to update personal information.");
                }
            })
            .catch(err => {
                console.error("Error updating personal info:", err);
                btnSavePersonal.disabled = false;
                btnSavePersonal.textContent = 'Save';
                alert("Error connecting to server.");
            });
        });
    }

    // Medical Information
    const btnEditMedical = document.getElementById('btn-edit-medical');
    const btnSaveMedical = document.getElementById('btn-save-medical');
    const inputAllergies = document.getElementById('input-allergies');
    const inputConditions = document.getElementById('input-conditions');
    const inputPriority = document.getElementById('input-priority');
    const medicalForm = document.getElementById('medical-form');

    if (btnEditMedical && medicalForm) {
        btnEditMedical.addEventListener('click', () => {
            inputAllergies.removeAttribute('readonly');
            inputConditions.removeAttribute('readonly');

            btnEditMedical.style.display = 'none';
            btnSaveMedical.style.display = 'inline-block';

            inputAllergies.focus();
        });

        medicalForm.addEventListener('submit', (e) => {
            e.preventDefault();
            
            const payload = {
                allergies: inputAllergies.value.trim(),
                medical_conditions: inputConditions.value.trim(),
                is_priority: inputPriority.checked
            };

            btnSaveMedical.disabled = true;
            btnSaveMedical.textContent = 'Saving...';

            fetch('../backend/update_medical_info.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                btnSaveMedical.disabled = false;
                btnSaveMedical.textContent = 'Save';

                if (data.success) {
                    inputAllergies.setAttribute('readonly', true);
                    inputConditions.setAttribute('readonly', true);

                    btnSaveMedical.style.display = 'none';
                    btnEditMedical.style.display = 'inline-block';

                    alert("Medical information updated successfully.");
                } else {
                    alert(data.message || "Failed to update medical information.");
                }
            })
            .catch(err => {
                console.error("Error updating medical info:", err);
                btnSaveMedical.disabled = false;
                btnSaveMedical.textContent = 'Save';
                alert("Error connecting to server.");
            });
        });
    }

    // Password Reset
    const passwordForm = document.getElementById('password-form');
    if (passwordForm) {
        passwordForm.addEventListener('submit', (e) => {
            e.preventDefault();
            
            const oldPassword = document.getElementById('input-old-password').value;
            const newPassword = document.getElementById('input-new-password').value;
            const btnSubmit = passwordForm.querySelector('button[type="submit"]');
            
            if (newPassword.length < 6) {
                alert("New password must be at least 6 characters long.");
                return;
            }

            btnSubmit.disabled = true;
            btnSubmit.textContent = 'Saving...';

            const payload = {
                old_password: oldPassword,
                new_password: newPassword
            };

            fetch('../backend/update_password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                btnSubmit.disabled = false;
                btnSubmit.textContent = 'Save';

                if (data.success) {
                    alert("Password successfully updated.");
                    passwordForm.reset();
                } else {
                    alert(data.message || "Failed to update password.");
                }
            })
            .catch(err => {
                console.error("Error updating password:", err);
                btnSubmit.disabled = false;
                btnSubmit.textContent = 'Save';
                alert("Error connecting to server.");
            });
        });
    }

    // Password Visibility Toggle (Show / Hide password)
    document.querySelectorAll('.toggle-password-btn').forEach(btn => {
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

    // Logout Handler
    const logoutBtn = document.getElementById('logout-btn') || document.querySelector('.sign-out');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', (e) => {
            e.preventDefault();
            fetch('../backend/logout.php')
                .finally(() => window.location.href = '../login.html');
        });
    }
});
