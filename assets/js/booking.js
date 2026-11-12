
document.addEventListener('DOMContentLoaded', () => {
    let currentStep = 1;
    const totalSteps = 4;

    const bookingData = {
        service: null,
        doctorId: null,
        doctorName: null,
        date: null,
        time: null
    };

    const form = document.getElementById('booking-form');
    const steps = document.querySelectorAll('.form-step');
    const stepperCircles = document.querySelectorAll('.step');
    const stepperLines = document.querySelectorAll('.step-line');

    const nextBtns = document.querySelectorAll('.btn-next');
    const prevBtns = document.querySelectorAll('.btn-prev');
    const submitBtn = document.getElementById('btn-submit');
    
    const timeSlots = document.querySelectorAll('.time-slot');
    const dateInput = document.getElementById('appointment_date');

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function getServiceDetails(serviceName) {
        const lower = (serviceName || '').toLowerCase();
        if (lower.includes('clean')) return { icon: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/></svg>', est: 'est. 45 min', price: '$45' };
        if (lower.includes('whiten')) return { icon: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h12l4 6-10 12L2 9Z"/><path d="M11 3 8 9l4 12 4-12-3-6"/><path d="M2 9h20"/></svg>', est: 'est. 60 min', price: '$85' };
        if (lower.includes('ortho') || lower.includes('brace')) return { icon: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M3 12h6m6 0h6"/></svg>', est: 'est. 45 min', price: '$90' };
        if (lower.includes('root') || lower.includes('canal')) return { icon: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>', est: 'est. 60 min', price: '$120' };
        if (lower.includes('extract') || lower.includes('remov')) return { icon: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>', est: 'est. 30 min', price: '$60' };
        if (lower.includes('consult')) return { icon: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>', est: 'est. 30 min', price: '$35' };
        if (lower.includes('implant')) return { icon: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M8 6h8M9 11h6M10 16h4"/></svg>', est: 'est. 90 min', price: '$150' };
        return { icon: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2C8 2 6 5 6 9c0 3 1.5 6.5 2 9.5.5 3 2.5 3.5 4 3.5s3.5-.5 4-3.5c.5-3 2-6.5 2-9.5 0-4-2-7-6-7z"/></svg>', est: 'est. 45 min', price: '$45' };
    }

    function loadServices() {
        fetch('../backend/get_active_services.php')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const grid = document.querySelector('.services-grid');
                    grid.innerHTML = '';
                    data.services.forEach(s => {
                        const div = document.createElement('div');
                        div.className = 'service-card';
                        div.dataset.service = s.name;
                        const details = getServiceDetails(s.name);
                        const safeName = escapeHtml(s.name);
                        const safeDesc = escapeHtml(s.description || details.est);
                        div.innerHTML = `
                            <div class="service-card-top">
                                <div class="service-icon" aria-hidden="true">${details.icon}</div>
                                <div>
                                    <h3>${safeName}</h3>
                                    <div class="price">${details.price}</div>
                                </div>
                            </div>
                            <p>${safeDesc}</p>
                        `;
                        div.addEventListener('click', () => {
                            document.querySelectorAll('.service-card').forEach(c => c.classList.remove('selected'));
                            div.classList.add('selected');
                            bookingData.service = s.name;
                            updateNextButtonState();
                        });
                        grid.appendChild(div);
                    });
                }
            });
    }

    function loadDoctors() {
        fetch('../backend/staff_get_doctors.php')
            .then(res => res.json())
            .then(data => {
                if(data.success && data.doctors) {
                    const list = document.querySelector('.doctors-list');
                    if (!list) return;
                    list.innerHTML = '';

                    if (data.doctors.length === 0) {
                        list.innerHTML = '<p style="color: var(--light-text); font-style: italic; padding: 1rem;">No doctors currently available. Please contact clinic reception.</p>';
                        return;
                    }

                    data.doctors.forEach(d => {
                        const div = document.createElement('div');
                        div.className = 'doctor-list-card';
                        div.dataset.doctor = d.id;
                        div.dataset.doctorName = d.name;

                        const names = d.name.replace(/^Dr\.\s*/i, '').trim().split(' ');
                        const initials = names.length > 1 
                            ? (names[0][0] + names[names.length - 1][0]).toUpperCase() 
                            : d.name.slice(0, 2).toUpperCase();

                        const safeDocName = escapeHtml(d.name);
                        const safeSpecialty = escapeHtml(d.specialty || 'Available');
                        const safeInitials = escapeHtml(initials);

                        div.innerHTML = `
                            <div class="doctor-list-avatar">${safeInitials}</div>
                            <div class="doctor-list-info">
                                <h3>${safeDocName}</h3>
                                <p class="status-available">${safeSpecialty}</p>
                            </div>
                        `;
                        div.addEventListener('click', () => {
                            const doctorChanged = bookingData.doctorId !== d.id;
                            document.querySelectorAll('.doctor-list-card').forEach(c => c.classList.remove('selected'));
                            div.classList.add('selected');
                            bookingData.doctorId = d.id;
                            bookingData.doctorName = d.name;
                            if (doctorChanged) {
                                bookingData.time = null; // Invalidate previously chosen doctor's time slot
                                const grid = document.getElementById('time-grid');
                                if (grid) grid.innerHTML = '';
                                const msg = document.getElementById('no-slots-msg');
                                if (msg) msg.style.display = 'none';
                            }
                            updateNextButtonState();
                        });
                        list.appendChild(div);
                    });
                }
            });
    }

    loadServices();
    loadDoctors();

    const updateNextButtonState = () => {
        const currentStepContainer = document.getElementById(`step-${currentStep}`);
        const nextBtn = currentStepContainer.querySelector('.btn-next');
        if (!nextBtn) return; // Last step has submit button instead

        let isValid = false;
        if (currentStep === 1) isValid = bookingData.service !== null;
        if (currentStep === 2) isValid = bookingData.doctorId !== null;
        if (currentStep === 3) isValid = bookingData.date !== null && bookingData.time !== null;

        nextBtn.disabled = !isValid;
    };

    const updateStepperUI = () => {

        steps.forEach((step, index) => {
            if (index + 1 === currentStep) {
                step.classList.add('active');
            } else {
                step.classList.remove('active');
            }
        });

        stepperCircles.forEach((circle, index) => {
            const stepNum = index + 1;
            circle.classList.remove('active', 'completed');
            if (stepNum === currentStep) {
                circle.classList.add('active');
            } else if (stepNum < currentStep) {
                circle.classList.add('completed');
            }
        });

        stepperLines.forEach((line, index) => {
            if (index + 1 < currentStep) {
                line.classList.add('active');
            } else {
                line.classList.remove('active');
            }
        });

        if (currentStep === 2) {
            document.querySelectorAll('.doctor-list-card').forEach(c => {
                if (bookingData.doctorId && c.dataset.doctor == bookingData.doctorId) {
                    c.classList.add('selected');
                } else {
                    c.classList.remove('selected');
                }
            });
        }

        if (currentStep === 3) {
            // Re-fetch slots if date and doctor are set to ensure they always belong to current doctor
            if (bookingData.doctorId && bookingData.date) {
                loadTimeSlots();
            } else {
                timeSlotsContainer.style.display = 'none';
            }
        }

        if (currentStep === 4) {
            document.getElementById('summary-service').textContent = bookingData.service;
            document.getElementById('summary-doctor').textContent = bookingData.doctorName;

            const dateObj = new Date(bookingData.date);
            const formattedDate = dateObj.toLocaleDateString('en-US', { year: 'numeric', month: '2-digit', day: '2-digit' });

            let formattedTime = bookingData.time;
            if (formattedTime) {
                const [h, m] = formattedTime.split(':');
                const hour = parseInt(h);
                const ampm = hour >= 12 ? 'PM' : 'AM';
                const hour12 = hour % 12 || 12;
                formattedTime = `${hour12}:${m} ${ampm}`;
            }

            document.getElementById('summary-date').textContent = `${formattedDate} at ${formattedTime}`;

            document.getElementById('summary-notes').textContent = bookingData.note || 'None';
        }
    };

    nextBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            if (currentStep < totalSteps) {
                currentStep++;
                updateStepperUI();
                updateNextButtonState();
            }
        });
    });

    prevBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            if (currentStep > 1) {
                currentStep--;
                updateStepperUI();
                updateNextButtonState();
            }
        });
    });

    const timeSlotsContainer = document.getElementById('time-slots-container');
    let closedDays = [];

    fetch('../backend/get_public_settings.php')
        .then(res => res.json())
        .then(data => {
            if(data.success && data.settings.closed_days) {
                closedDays = JSON.parse(data.settings.closed_days);
            }
        });
    
    let currentSlotsFetchId = 0;

    function loadTimeSlots() {
        if (!bookingData.date || !bookingData.doctorId) {
            timeSlotsContainer.style.display = 'none';
            return;
        }

        const fetchId = ++currentSlotsFetchId;
        timeSlotsContainer.style.display = 'block';
        const grid = document.getElementById('time-grid');
        const msg = document.getElementById('no-slots-msg');
        const docLabel = bookingData.doctorName ? escapeHtml(bookingData.doctorName) : 'attending doctor';
        grid.innerHTML = `<p style="color:var(--color-grey); grid-column:1/-1;">Loading slots for ${docLabel}...</p>`;
        msg.style.display = 'none';

        fetch(`../backend/get_available_times.php?doctor_id=${bookingData.doctorId}&date=${bookingData.date}`)
            .then(res => res.json())
            .then(data => {
                if (fetchId !== currentSlotsFetchId) return; // Stale fetch response

                grid.innerHTML = '';
                const slots = data.slots || data.times || [];
                if (data.success && slots.length > 0) {
                    // If previously chosen time isn't in this doctor's available slots, clear it
                    if (bookingData.time && !slots.includes(bookingData.time)) {
                        bookingData.time = null;
                    }

                    slots.forEach(time => {
                        const [h, m] = time.split(':');
                        const hour = parseInt(h);
                        const ampm = hour >= 12 ? 'PM' : 'AM';
                        const hour12 = hour % 12 || 12;
                        const displayTime = `${hour12}:${m} ${ampm}`;

                        const slot = document.createElement('div');
                        slot.className = 'time-slot';
                        if (bookingData.time === time) {
                            slot.classList.add('selected');
                        }
                        slot.textContent = displayTime;
                        slot.dataset.time = time;
                        
                        slot.addEventListener('click', () => {
                            document.querySelectorAll('.time-slot').forEach(s => s.classList.remove('selected'));
                            slot.classList.add('selected');
                            bookingData.time = time;
                            updateNextButtonState();
                        });
                        grid.appendChild(slot);
                    });
                } else {
                    bookingData.time = null;
                    msg.style.display = 'block';
                    msg.textContent = data.message || `No available slots for ${docLabel} on this date.`;
                }
                updateNextButtonState();
            })
            .catch(err => {
                if (fetchId !== currentSlotsFetchId) return;
                grid.innerHTML = '';
                msg.style.display = 'block';
                msg.textContent = 'Error loading slots.';
                bookingData.time = null;
                updateNextButtonState();
            });
    }

    dateInput.addEventListener('change', (e) => {
        bookingData.date = e.target.value;
        if(bookingData.date) {
            const dateObj = new Date(bookingData.date);
            const daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            const dayName = daysOfWeek[dateObj.getDay()];
            
            if(closedDays.includes(dayName)) {
                alert(`Sorry, the clinic is closed on ${dayName}s. Please select another date.`);
                e.target.value = '';
                bookingData.date = null;
                bookingData.time = null;
                timeSlotsContainer.style.display = 'none';
                updateNextButtonState();
                return;
            }
        }

        bookingData.time = null;
        updateNextButtonState();
        loadTimeSlots();
    });

    const noteInput = document.getElementById('appointment_note');
    if (noteInput) {
        noteInput.addEventListener('input', (e) => {
            bookingData.note = e.target.value;
        });
    }

    submitBtn.addEventListener('click', async () => {
        const errorDiv = document.getElementById('booking-error');
        errorDiv.textContent = '';
        submitBtn.disabled = true;
        submitBtn.textContent = 'Processing...';

        try {
            const response = await fetch('../backend/book_appointment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(bookingData)
            });

            const data = await response.json();

            if (data.success) {

                document.getElementById('booking-flow').style.display = 'none';

                document.getElementById('success-queue').textContent = data.queue_number;
                document.getElementById('success-service').textContent = bookingData.service;
                document.getElementById('success-doctor').textContent = bookingData.doctorName;
                
                const dateObj = new Date(bookingData.date);
                const formattedDate = dateObj.toLocaleDateString('en-US', { year: 'numeric', month: '2-digit', day: '2-digit' }).replace(/\//g, '-');
                
                let formattedTime = bookingData.time;
                if (formattedTime) {
                    const [h, m] = formattedTime.split(':');
                    const hour = parseInt(h);
                    const ampm = hour >= 12 ? 'PM' : 'AM';
                    const hour12 = hour % 12 || 12;
                    formattedTime = `${hour12}:${m} ${ampm}`;
                }

                document.getElementById('success-datetime').textContent = `${formattedDate} at ${formattedTime}`;

                document.getElementById('success-screen').style.display = 'flex';
            } else {
                errorDiv.textContent = data.message || 'Failed to book appointment. Please try again.';
                errorDiv.style.display = 'block';
                submitBtn.disabled = false;
                submitBtn.textContent = 'For Approval ->';
            }

        } catch (error) {
            errorDiv.textContent = 'Network error. Please try again.';
            submitBtn.disabled = false;
            submitBtn.textContent = 'Confirm Appointment';
        }
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const logoutBtn = document.getElementById('logout-btn') || document.querySelector('.sign-out');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', (e) => {
            e.preventDefault();
            fetch('../backend/logout.php')
                .finally(() => window.location.href = '../login.html');
        });
    }
});
