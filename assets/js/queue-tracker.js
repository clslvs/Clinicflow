

let isCurrentlyCalled = false;

function dismissCallAlert() {
    const banner = document.getElementById('call-alert-banner');
    if (banner) banner.style.display = 'none';
}

function triggerCallNotification(data) {
    // 1. Show glowing announcement banner
    const banner = document.getElementById('call-alert-banner');
    const titleEl = document.getElementById('call-banner-title');
    const descEl = document.getElementById('call-banner-desc');
    if (banner) {
        if (titleEl) titleEl.textContent = `Queue #${data.user_queue_number} is being called!`;
        if (descEl) descEl.textContent = `Please proceed to the consultation room for ${data.doctor_name}.`;
        banner.style.display = 'block';
    }

    // 2. Play audio notification chime
    playNotificationSound();

    // 3. Spoken Voice Announcement via Web Speech API
    if ('speechSynthesis' in window) {
        try {
            window.speechSynthesis.cancel();
            const text = `Now serving, Queue Number ${data.user_queue_number}. Please proceed to your consultation with ${data.doctor_name}.`;
            const utterance = new SpeechSynthesisUtterance(text);
            utterance.rate = 0.95;
            utterance.pitch = 1.0;
            window.speechSynthesis.speak(utterance);
        } catch (e) {
            console.warn("Speech synthesis unavailable:", e);
        }
    }
}

function fetchQueueData() {
    fetch('../backend/get_queue_data.php')
        .then(res => res.json())
        .then(data => {
            if (data.success && data.has_queue) {

                document.getElementById('queue-content').style.display = 'grid';
                document.getElementById('no-queue-msg').style.display = 'none';

                document.getElementById('queue-subtitle').textContent = `Real-time position in the queue for ${data.doctor_name}.`;
                
                document.getElementById('user-position').textContent = data.user_position;
                document.getElementById('user-queue-number').textContent = data.user_queue_number;

                document.getElementById('current-patient-pos').textContent = data.current_patient;
                document.getElementById('estimated-wait').textContent = data.estimated_wait;
                document.getElementById('people-ahead').textContent = data.people_ahead;

                const listContainer = document.getElementById('queue-list-container');
                listContainer.innerHTML = '';
                
                data.queue_list.forEach(item => {
                    const el = document.createElement('div');
                    el.className = `queue-item ${item.state}`;
                    
                    el.innerHTML = `
                        <div class="queue-position-circle">${item.position}</div>
                        <div class="queue-item-details">
                            <span class="queue-item-number">${item.queue_number}</span>
                            <span class="queue-item-service">${item.service}</span>
                        </div>
                        <div class="queue-item-status">${item.stateLabel}</div>
                    `;
                    listContainer.appendChild(el);
                });

                // Detect when staff calls this patient (status === 'in_progress')
                if (data.is_called) {
                    if (!isCurrentlyCalled) {
                        isCurrentlyCalled = true;
                        triggerCallNotification(data);
                    }
                } else {
                    isCurrentlyCalled = false;
                    dismissCallAlert();
                }
                
            } else if (data.success && !data.has_queue) {
                document.getElementById('queue-content').style.display = 'none';
                document.getElementById('no-queue-msg').style.display = 'block';
                document.getElementById('queue-subtitle').textContent = "No active queue found.";
                dismissCallAlert();
            } else {
                console.error(data.message || "Failed to load queue data.");
            }
        })
        .catch(err => {
            console.error("Error connecting to server.", err);
        });
}

function playNotificationSound() {
    const audio = new Audio('../assets/sound/mixkit-happy-bells-notification-937.wav');
    audio.play().catch(e => console.error("Audio play failed, user interaction may be required:", e));
}

document.addEventListener('DOMContentLoaded', () => {
    fetchQueueData();

    setInterval(fetchQueueData, 5000);
});

document.addEventListener('DOMContentLoaded', () => {
    const logoutBtn = document.getElementById('logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', (e) => {
            e.preventDefault();
            fetch('../backend/logout.php')
                .finally(() => window.location.href = '../login.html');
        });
    }
});
