// queue-tracker.js

document.addEventListener('DOMContentLoaded', () => {
    fetch('../backend/get_queue_data.php')
        .then(res => res.json())
        .then(data => {
            if (data.success && data.has_queue) {
                // Populate DOM
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
            } else if (data.success && !data.has_queue) {
                document.getElementById('queue-content').style.display = 'none';
                document.getElementById('no-queue-msg').style.display = 'block';
                document.getElementById('queue-subtitle').textContent = "No active queue found.";
            } else {
                alert(data.message || "Failed to load queue data.");
            }
        })
        .catch(err => {
            console.error(err);
            alert("Error connecting to server.");
        });
});
