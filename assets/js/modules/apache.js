// assets/js/modules/apache.js
export function toggleApache(action) {
    const messageBox = document.getElementById('apache-status-message');
    if (!messageBox) return;
    messageBox.innerText = 'Executing ' + action + '...';
    fetch('ajax_toggle_apache.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=' + encodeURIComponent(action)
    })
    .then(response => response.text())
    .then(text => {
        try {
            const data = JSON.parse(text);
            messageBox.innerText = data.message;
            if (typeof renderServerInfo === 'function') {
                renderServerInfo(); // Refresh Apache status
            }
        } catch (e) {
            console.error('Invalid JSON:', text);
            messageBox.innerText = 'Error: Invalid server response';
        }
    })
    .catch(error => {
        messageBox.innerText = 'Error: ' + error;
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('restart-apache-button');
    if (btn) {
        btn.addEventListener('click', () => toggleApache('restart'));
    }
});
