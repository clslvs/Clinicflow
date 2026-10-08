

document.addEventListener('DOMContentLoaded', () => {

    fetch('../backend/get_settings.php')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const user = data.data;

                const initial = user.first_name.charAt(0).toUpperCase();
                const fullName = `${user.first_name} ${user.last_name}`;

                document.getElementById('sidebar-avatar').textContent = initial;
                document.getElementById('sidebar-name').textContent = fullName;

                document.getElementById('settings-avatar').textContent = initial;
                document.getElementById('settings-name').textContent = fullName;
                document.getElementById('settings-patient-id').textContent = `Patient ID: ${user.patient_id_formatted}`;

                document.getElementById('input-firstname').value = user.first_name;
                document.getElementById('input-lastname').value = user.last_name;
                document.getElementById('input-email').value = user.email;
                document.getElementById('input-phone').value = user.phone;

                document.getElementById('input-allergies').value = user.allergies || '';
                document.getElementById('input-conditions').value = user.medical_conditions || '';
                document.getElementById('input-priority').checked = user.is_priority === 1 || user.is_priority === true;
            } else {
                alert(data.message || "Failed to load settings.");
            }
        })
        .catch(err => {
            console.error("Error fetching settings:", err);
        });

    const btnEditPersonal = document.getElementById('btn-edit-personal');
    const btnSavePersonal = document.getElementById('btn-save-personal');
    const inputFirstName = document.getElementById('input-firstname');
    const inputLastName = document.getElementById('input-lastname');
    const inputEmail = document.getElementById('input-email');
    const inputPhone = document.getElementById('input-phone');
    const personalForm = document.getElementById('personal-form');

    if (btnEditPersonal) {
        btnEditPersonal.addEventListener('click', () => {
            inputFirstName.removeAttribute('readonly');
            inputLastName.removeAttribute('readonly');
            inputEmail.removeAttribute('readonly');
            inputPhone.removeAttribute('readonly');

            btnEditPersonal.style.display = 'none';
            btnSavePersonal.style.display = 'inline-block';

            inputFirstName.focus();
        });

        personalForm.addEventListener('submit', (e) => {
            e.preventDefault();
            
            const payload = {
                first_name: inputFirstName.value.trim(),
                last_name: inputLastName.value.trim(),
                email: inputEmail.value.trim(),
                phone: inputPhone.value.trim()
            };

            btnSavePersonal.disabled = true;
            btnSavePersonal.textContent = 'Saving...';

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
                    inputLastName.setAttribute('readonly', true);
                    inputEmail.setAttribute('readonly', true);
                    inputPhone.setAttribute('readonly', true);

                    btnSavePersonal.style.display = 'none';
                    btnEditPersonal.style.display = 'inline-block';

                    const initial = payload.first_name.charAt(0).toUpperCase();
                    const fullName = `${payload.first_name} ${payload.last_name}`;
                    const sidebarAvatar = document.getElementById('sidebar-avatar');
                    if (sidebarAvatar) sidebarAvatar.textContent = initial;
                    const sidebarName = document.getElementById('sidebar-name');
                    if (sidebarName) sidebarName.textContent = fullName;
                    const settingsAvatar = document.getElementById('settings-avatar');
                    if (settingsAvatar) settingsAvatar.textContent = initial;
                    const settingsName = document.getElementById('settings-name');
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

    const btnEditMedical = document.getElementById('btn-edit-medical');
    const btnSaveMedical = document.getElementById('btn-save-medical');
    const inputAllergies = document.getElementById('input-allergies');
    const inputConditions = document.getElementById('input-conditions');
    const inputPriority = document.getElementById('input-priority');
    const medicalForm = document.getElementById('medical-form');

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

    const passwordForm = document.getElementById('password-form');
    
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
});
