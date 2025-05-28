// assets/js/modules/clock.js
export function initClock() {
    document.addEventListener('DOMContentLoaded', () => {
        updateClock();
        setInterval(updateClock, 1000);
    });
}

export function updateClock() {
    const clock = document.querySelector('.clock');
    if (clock) {
        const now = new Date();
        clock.textContent = now.toLocaleTimeString();
    }
}
