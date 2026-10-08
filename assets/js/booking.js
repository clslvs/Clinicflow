
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

    function getServiceDetails(serviceName) {
        const lower = (serviceName || '').toLowerCase();
        if (lower.includes('clean')) return { icon: '✨', est: 'est. 45 min', price: '$45' };
        if (lower.includes('whiten')) return { icon: '💎', est: 'est. 60 min', price: '$85' };
        if (lower.includes('ortho') || lower.includes('brace')) return { icon: '🔩', est: 'est. 45 min', price: '$90' };
        if (lower.includes('root') || lower.includes('canal')) return { icon: '🏥', est: 'est. 60 min', price: '$120' };
        if (lower.includes('extract') || lower.includes('remov')) return { icon: '🩹', est: 'est. 30 min', price: '$60' };
        if (lower.includes('consult')) return { icon: '🩺', est: 'est. 30 min', price: '$35' };
        if (lower.includes('implant')) return { icon: '🔬', est: 'est. 90 min', price: '$150' };
        return { icon: '🦷', est: 'est. 45 min', price: '$45' };
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
                        div.innerHTML = `
                            <div class="service-icon">${details.icon}</div>
                            <h3>${s.name}</h3>
                            <p>${s.description || details.est}</p>
                            <div class="price">${details.price}</div>
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

                        div.innerHTML = `
                            <div class="doctor-list-avatar">${initials}</div>
                            <div class="doctor-list-info">
                                <h3>${d.name}</h3>
                                <p class="status-available">${d.specialty || 'Available'}</p>
                            </div>
                        `;
                        div.addEventListener('click', () => {
                            document.querySelectorAll('.doctor-list-card').forEach(c => c.classList.remove('selected'));
                            div.classList.add('selected');
                            bookingData.doctorId = d.id;
                            bookingData.doctorName = d.name;
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
                timeSlotsContainer.style.display = 'none';
                return;
            }
        }

        bookingData.time = null;
        updateNextButtonState();

        if (bookingData.date && bookingData.doctorId) {
            timeSlotsContainer.style.display = 'block';
            const grid = document.getElementById('time-grid');
            const msg = document.getElementById('no-slots-msg');
            grid.innerHTML = '<p style="color:var(--color-grey); grid-column:1/-1;">Loading slots...</p>';
            msg.style.display = 'none';

            fetch(`../backend/get_available_times.php?doctor_id=${bookingData.doctorId}&date=${bookingData.date}`)
                .then(res => res.json())
                .then(data => {
                    grid.innerHTML = '';
                    if (data.success && data.slots && data.slots.length > 0) {
                        data.slots.forEach(time => {

                            const [h, m] = time.split(':');
                            const hour = parseInt(h);
                            const ampm = hour >= 12 ? 'PM' : 'AM';
                            const hour12 = hour % 12 || 12;
                            const displayTime = `${hour12}:${m} ${ampm}`;

                            const slot = document.createElement('div');
                            slot.className = 'time-slot';
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
                        msg.style.display = 'block';
                        if (data.message) {
                            msg.textContent = data.message;
                        }
                    }
                })
                .catch(err => {
                    grid.innerHTML = '';
                    msg.style.display = 'block';
                    msg.textContent = 'Error loading slots.';
                });
        } else {
            timeSlotsContainer.style.display = 'none';
        }
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
