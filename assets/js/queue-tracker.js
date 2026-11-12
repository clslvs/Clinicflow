// ─── State ────────────────────────────────────────────────────────────────────
let isCurrentlyCalled = false;
let audioUnlocked = false;
let lastQueueHash = null;

// Unlock audio on first user interaction (browser autoplay policy)
document.addEventListener('click', () => { audioUnlocked = true; }, { once: true });
document.addEventListener('keydown', () => { audioUnlocked = true; }, { once: true });

// ─── Helpers ──────────────────────────────────────────────────────────────────
function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/** Simple hash to detect meaningful data changes and skip unnecessary re-renders */
function hashData(data) {
    return JSON.stringify([
        data.user_position,
        data.current_patient,
        data.people_ahead,
        data.is_called,
        (data.queue_list || []).map(i => i.state + i.position).join(',')
    ]);
}

// ─── Call Notification ────────────────────────────────────────────────────────
function dismissCallAlert() {
    const banner = document.getElementById('call-alert-banner');
    if (banner) banner.style.display = 'none';
}

function triggerCallNotification(data) {
    // 1. Show glowing announcement banner
    const banner = document.getElementById('call-alert-banner');
    const titleEl = document.getElementById('call-banner-title');
    const descEl  = document.getElementById('call-banner-desc');
    if (banner) {
        if (titleEl) titleEl.textContent = `Queue #${data.user_queue_number} is being called!`;
        if (descEl)  descEl.textContent  = `Please proceed to the consultation room for ${data.doctor_name}.`;
        banner.style.display = 'block';
    }

    // 2. Play audio chime (only if browser has been unlocked by user interaction)
    if (audioUnlocked) {
        const audio = new Audio('../assets/sound/mixkit-happy-bells-notification-937.wav');
        audio.play().catch(e => console.warn('Audio play failed:', e));
    }

    // 3. Spoken Voice Announcement via Web Speech API
    if ('speechSynthesis' in window && audioUnlocked) {
        try {
            window.speechSynthesis.cancel();
            const text = `Now serving, Queue Number ${data.user_queue_number}. Please proceed to your consultation with ${data.doctor_name}.`;
            const utterance = new SpeechSynthesisUtterance(text);
            utterance.rate  = 0.95;
            utterance.pitch = 1.0;
            window.speechSynthesis.speak(utterance);
        } catch (e) {
            console.warn('Speech synthesis unavailable:', e);
        }
    }
}

// ─── Queue Fetch & Render ─────────────────────────────────────────────────────
function fetchQueueData() {
    fetch('../backend/get_queue_data.php')
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                console.error(data.message || 'Failed to load queue data.');
                return;
            }

            if (!data.has_queue) {
                document.getElementById('queue-content').style.display = 'none';
                document.getElementById('no-queue-msg').style.display = 'block';
                document.getElementById('queue-subtitle').textContent = 'No active queue found.';
                dismissCallAlert();
                isCurrentlyCalled = false;
                lastQueueHash = null;
                return;
            }

            // Skip DOM update if nothing meaningful changed
            const newHash = hashData(data);
            if (newHash === lastQueueHash) return;
            const isInitialLoad = lastQueueHash === null;
            lastQueueHash = newHash;

            document.getElementById('queue-content').style.display = 'grid';
            document.getElementById('no-queue-msg').style.display = 'none';
            document.getElementById('queue-subtitle').textContent = `Real-time position in the queue for ${data.doctor_name}.`;

            document.getElementById('user-position').textContent    = data.user_position;
            document.getElementById('user-queue-number').textContent = data.user_queue_number;
            document.getElementById('current-patient-pos').textContent = data.current_patient;
            document.getElementById('estimated-wait').textContent   = data.estimated_wait;
            document.getElementById('people-ahead').textContent     = data.people_ahead;

            // Trigger calm single pulse to reassure user that queue shifted forward
            if (!isInitialLoad && window.ClinicFlowMotion && typeof window.ClinicFlowMotion.pulse === 'function') {
                const ticketCard = document.querySelector('.ticket-card, .user-queue-card, .metric-card');
                if (ticketCard) window.ClinicFlowMotion.pulse(ticketCard);
            }

            const listContainer = document.getElementById('queue-list-container');
            listContainer.innerHTML = '';
            (data.queue_list || []).forEach(item => {
                const el = document.createElement('div');
                el.className = `queue-item ${item.state}`;
                el.innerHTML = `
                    <div class="queue-position-circle">${escapeHtml(item.position)}</div>
                    <div class="queue-item-details">
                        <span class="queue-item-number">${escapeHtml(item.queue_number)}</span>
                        <span class="queue-item-service">${escapeHtml(item.service)}</span>
                    </div>
                    <div class="queue-item-status">${escapeHtml(item.stateLabel)}</div>
                `;
                listContainer.appendChild(el);
            });

            // Edge-trigger: fire notification only on transition into in_progress
            if (data.is_called) {
                if (!isCurrentlyCalled) {
                    isCurrentlyCalled = true;
                    triggerCallNotification(data);
                }
            } else {
                if (isCurrentlyCalled) dismissCallAlert();
                isCurrentlyCalled = false;
            }
        })
        .catch(err => console.error('Error connecting to server.', err));
}

// ─── Boot ─────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    fetchQueueData();
    setInterval(fetchQueueData, 5000); // poll every 5s

    const logoutBtn = document.getElementById('logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', e => {
            e.preventDefault();
            fetch('../backend/logout.php').finally(() => { window.location.href = '../login.html'; });
        });
    }
});
