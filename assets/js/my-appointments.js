// my-appointments.js

document.addEventListener('DOMContentLoaded', () => {
    const tbody = document.getElementById('appointments-tbody');
    const filterBtns = document.querySelectorAll('.filter-btn');
    let allAppointments = [];
    
    // Fetch appointments
    fetch('../backend/get_my_appointments.php')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                allAppointments = data.appointments;
                renderAppointments('all');
            } else {
                tbody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: var(--light-text);">${data.message || 'Failed to load appointments.'}</td></tr>`;
            }
        })
        .catch(err => {
            console.error(err);
            tbody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: var(--light-text);">Error connecting to server.</td></tr>`;
        });

    // Filter logic
    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            // Update active state
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            // Render filtered
            renderAppointments(btn.dataset.filter);
        });
    });

    function renderAppointments(filter) {
        tbody.innerHTML = '';
        
        const now = new Date();
        
        let filtered = allAppointments.filter(app => {
            // Compare dates only so today's appointments don't disappear when time passes
            const appDateObj = new Date(app.appointment_date);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (filter === 'upcoming') {
                return appDateObj >= today && (app.status === 'pending' || app.status === 'confirmed');
            } else if (filter === 'past') {
                return appDateObj < today || app.status === 'completed' || app.status === 'cancelled';
            }
            return true; // 'all'
        });

        if (filtered.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: var(--light-text);">No appointments found.</td></tr>`;
            return;
        }

        filtered.forEach(app => {
            const tr = document.createElement('tr');
            
            // Format Date & Time
            const dateObj = new Date(`${app.appointment_date}T${app.appointment_time}`);
            let formattedDate = dateObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            
            // Add dot after month abbreviation if needed to match mockup (e.g. "Aug. 27, 2026")
            formattedDate = formattedDate.replace(/([a-zA-Z]{3})\s/, '$1. ');

            let formattedTime = 'Time not set';
            if (app.appointment_time) {
                const [hours, minutes] = app.appointment_time.split(':');
                const ampm = hours >= 12 ? 'PM' : 'AM';
                const hr = hours % 12 || 12;
                formattedTime = `${hr}:${minutes} ${ampm}`;
            }
            
            // Status Pill
            let statusPillClass = '';
            let statusText = '';
            let actionLinks = '';
            
            switch (app.status) {
                case 'confirmed':
                    statusPillClass = 'status-confirmed';
                    statusText = 'Confirmed';
                    actionLinks = `<a href="#" class="action-link" onclick="openRescheduleModal(${app.id})"><i data-lucide="calendar"></i> Reschedule</a> <a href="#" class="action-link cancel" onclick="cancelAppointment(${app.id})"><i data-lucide="x-circle"></i> Cancel</a>`;
                    break;
                case 'pending':
                    statusPillClass = 'status-pending';
                    statusText = 'Pending';
                    actionLinks = `<a href="#" class="action-link" onclick="openRescheduleModal(${app.id})"><i data-lucide="calendar"></i> Reschedule</a> <a href="#" class="action-link cancel" onclick="cancelAppointment(${app.id})"><i data-lucide="x-circle"></i> Cancel</a>`;
                    break;
                case 'cancelled':
                    statusPillClass = 'status-cancelled';
                    statusText = 'Cancelled';
                    actionLinks = '';
                    break;
                case 'completed':
                    statusPillClass = 'status-completed';
                    statusText = 'Completed';
                    actionLinks = `<a href="#" class="action-link" onclick="showDetails(${app.id})"><i data-lucide="file-text"></i> Details</a>`;
                    break;
                default:
                    statusPillClass = 'status-pending';
                    statusText = app.status;
            }

            tr.innerHTML = `
                <td>${app.service}</td>
                <td style="color: var(--light-text);">${app.doctor_name}</td>
                <td style="color: var(--light-text);">${formattedDate} | ${formattedTime}</td>
                <td><span class="status-pill ${statusPillClass}">${statusText}</span></td>
                <td><div class="action-links">${actionLinks}</div></td>
            `;
            
            tbody.appendChild(tr);
        });
        
        if (window.lucide) {
            lucide.createIcons();
        }
    }

    // Assign globally to be callable from onclick
    window.showDetails = function(appId) {
        const app = allAppointments.find(a => a.id == appId);
        if(!app) return;

        document.getElementById('modal-service').textContent = app.service;
        document.getElementById('modal-doctor').textContent = app.doctor_name;
        
        const dateObj = new Date(app.appointment_date);
        let formattedTime = 'Time not set';
        if (app.appointment_time) {
            const [hours, minutes] = app.appointment_time.split(':');
            const ampm = hours >= 12 ? 'PM' : 'AM';
            const hr = hours % 12 || 12;
            formattedTime = `${hr}:${minutes} ${ampm}`;
        }
        document.getElementById('modal-datetime').textContent = `${dateObj.toLocaleDateString()} at ${formattedTime}`;
        
        document.getElementById('modal-notes').textContent = app.notes || "No notes added by doctor.";
        
        const modal = document.getElementById('details-modal');
        if(modal) {
            modal.style.display = 'flex';
        }
    };

    // Global cancel function
    window.cancelAppointment = function(appointmentId) {
        if (confirm("Are you sure you want to cancel this appointment?")) {
            fetch('../backend/cancel_appointment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ appointment_id: appointmentId })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Update status in local list and re-render
                    const appToUpdate = allAppointments.find(app => app.id == appointmentId);
                    if (appToUpdate) {
                        appToUpdate.status = 'cancelled';
                    }
                    const activeFilter = document.querySelector('.filter-btn.active').dataset.filter;
                    renderAppointments(activeFilter);
                } else {
                    alert(data.message || "Failed to cancel appointment.");
                }
            })
            .catch(err => {
                console.error(err);
                alert("Error connecting to server.");
            });
        }
    };

    window.openRescheduleModal = function(appId) {
        const app = allAppointments.find(a => a.id == appId);
        if(!app) return;

        document.getElementById('reschedule-id').value = app.id;
        document.getElementById('reschedule-doctor-id').value = app.doctor_id;
        document.getElementById('reschedule-date').value = app.appointment_date;
        
        // Reset time dropdown
        document.getElementById('reschedule-time').innerHTML = '<option value="">Select a date first</option>';
        
        document.getElementById('reschedule-modal').style.display = 'flex';
        
        // Load times for the current date
        window.loadAvailableTimes();
    };

    window.loadAvailableTimes = function() {
        const date = document.getElementById('reschedule-date').value;
        const doctorId = document.getElementById('reschedule-doctor-id').value;
        const timeSelect = document.getElementById('reschedule-time');
        
        if(!date || !doctorId) return;

        timeSelect.innerHTML = '<option value="">Loading times...</option>';

        fetch(`../backend/get_available_times.php?doctor_id=${doctorId}&date=${date}`)
            .then(res => res.json())
            .then(data => {
                timeSelect.innerHTML = '';
                if(data.success && data.times.length > 0) {
                    data.times.forEach(t => {
                        // Format the label
                        const timeParts = t.split(':');
                        const d = new Date();
                        d.setHours(timeParts[0], timeParts[1]);
                        const formatted = d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
                        timeSelect.innerHTML += `<option value="${t}">${formatted}</option>`;
                    });
                } else {
                    timeSelect.innerHTML = '<option value="">No times available</option>';
                }
            })
            .catch(err => {
                timeSelect.innerHTML = '<option value="">Error loading times</option>';
            });
    };

    window.submitReschedule = function(e) {
        e.preventDefault();
        const appId = document.getElementById('reschedule-id').value;
        const date = document.getElementById('reschedule-date').value;
        const time = document.getElementById('reschedule-time').value;

        if(!time) {
            alert("Please select a valid time.");
            return;
        }

        fetch('../backend/reschedule_appointment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ appointment_id: appId, date: date, time: time })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                alert("Appointment rescheduled successfully!");
                location.reload();
            } else {
                alert("Error: " + data.message);
            }
        })
        .catch(err => {
            alert("Error connecting to server.");
        });
    };
});
