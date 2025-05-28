// assets/js/modules/dock.js
export function initDockConfig() {
    document.addEventListener('DOMContentLoaded', () => {
        const dockList = document.getElementById('dock-list');
        const addBtn = document.getElementById('add-dock-item');
        const form = document.querySelector('#settings-view form');

        if (!dockList || !addBtn || !form) return;

        // Load current config
        fetch('partials/dock.json')
            .then(res => res.json())
            .then(data => {
                data.forEach(addDockItem);
            });

        function addDockItem(item = {}) {
            const li = document.createElement('li');
            li.className = 'dock-item';
            li.draggable = true;
            li.innerHTML = `
                <input type="text" placeholder="Label" value="${item.label || ''}">
                <input type="text" placeholder="URL" value="${item.url || ''}">
                <input type="text" placeholder="Icon" value="${item.icon || ''}">
                <input type="text" placeholder="Alt Text" value="${item.alt || ''}">
                <button class="remove">🗑️</button>
            `;
            dockList.appendChild(li);
            li.querySelector('.remove').onclick = () => li.remove();
            handleDrag(li);
        }

        addBtn.onclick = () => addDockItem();

        function handleDrag(item) {
            item.addEventListener('dragstart', () => {
                item.classList.add('dragging');
            });
            item.addEventListener('dragend', () => {
                item.classList.remove('dragging');
            });
        }

        dockList.addEventListener('dragover', e => {
            e.preventDefault();
            const dragging = dockList.querySelector('.dragging');
            const afterElement = getDragAfterElement(dockList, e.clientY);
            if (!dragging) return;

            if (afterElement == null) {
                dockList.appendChild(dragging);
            } else {
                dockList.insertBefore(dragging, afterElement);
            }
        });

        function getDragAfterElement(container, y) {
            const items = [...container.querySelectorAll('.dock-item:not(.dragging)')];
            return items.reduce((closest, child) => {
                const box = child.getBoundingClientRect();
                const offset = y - box.top - box.height / 2;
                return offset < 0 && offset > closest.offset
                    ? { offset: offset, element: child }
                    : closest;
            }, { offset: Number.NEGATIVE_INFINITY }).element;
        }

        // Save hook
        form.addEventListener('submit', () => {
            const items = [...dockList.querySelectorAll('.dock-item')].map(li => {
                const [label, url, icon, alt] = li.querySelectorAll('input');
                return {
                    label: label.value,
                    url: url.value,
                    icon: icon.value,
                    alt: alt.value
                };
            });
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'dock_json';
            hidden.value = JSON.stringify(items);
            form.appendChild(hidden);
        });
    });
}
