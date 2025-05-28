// assets/js/modules/columns.js
export function setColumnWidth(size) {
	localStorage.setItem('columnSize', size);
	const columnsDiv = document.querySelector('.columns');
	if (!columnsDiv) return;

	columnsDiv.classList.forEach(cls => {
		if (cls.startsWith('max-')) {
			columnsDiv.classList.remove(cls);
		}
	});

	if (size !== 'auto') {
		columnsDiv.classList.add(size);
	}
}

export function cycleColumnWidth(direction, columnSizes) {
	let currentSize = localStorage.getItem('columnSize');
	let index = columnSizes.indexOf(currentSize);

	if (index === -1) {
		index = direction === 'next' ? -1 : 0;
	}

	if (direction === 'next' && index < columnSizes.length - 1) {
		index += 1;
	} else if (direction === 'prev' && index > 0) {
		index -= 1;
	}

	setColumnWidth(columnSizes[index]);
}

export function initColumnFeatures() {
	const columnSizes = ['max-xs', 'max-sm', 'max-md', 'max-lg'];

	// Restore and cycle column size
	document.addEventListener('DOMContentLoaded', () => {
		const savedSize = localStorage.getItem('columnSize');
		if (savedSize) {
			setColumnWidth(savedSize);
		}

		const resetBtn = document.getElementById('reset-width');
		const prevBtn  = document.getElementById('prev-width');
		const nextBtn  = document.getElementById('next-width');

		if (resetBtn) {
			resetBtn.addEventListener('click', () => setColumnWidth('auto'));
		}

		if (prevBtn) {
			prevBtn.addEventListener('click', () => cycleColumnWidth('prev', columnSizes));
		}

		if (nextBtn) {
			nextBtn.addEventListener('click', () => cycleColumnWidth('next', columnSizes));
		}
	});

	// Handle drag-and-drop column order
	document.addEventListener('DOMContentLoaded', () => {
		const container = document.querySelector('.columns');
		if (!container) return;

		let draggedItem = null;

		const savedOrder = JSON.parse(localStorage.getItem('columnOrder') || '[]');
		if (savedOrder.length) {
			savedOrder.forEach(id => {
				const col = document.getElementById(id);
				if (col) container.appendChild(col);
			});
		}

		function saveOrder() {
			const order = Array.from(container.children)
				.filter(el => !el.classList.contains('column-controls'))
				.map(el => el.id);
			localStorage.setItem('columnOrder', JSON.stringify(order));
		}

		container.querySelectorAll('.column:not(.column-controls)').forEach(column => {
			const handle = column.querySelector('.drag-handle');
			if (!handle) return;

			column.setAttribute('draggable', false);

			handle.addEventListener('mousedown', () => {
				column.setAttribute('draggable', true);
			});

			handle.addEventListener('mouseup', () => {
				column.setAttribute('draggable', false);
			});

			column.addEventListener('dragstart', () => {
				draggedItem = column;
				column.style.opacity = '0.5';
			});

			column.addEventListener('dragend', () => {
				draggedItem = null;
				column.style.opacity = '1';
				column.setAttribute('draggable', false);
				saveOrder();
			});

			column.addEventListener('dragover', e => e.preventDefault());

			column.addEventListener('drop', e => {
				e.preventDefault();
				if (draggedItem && draggedItem !== column) {
					const all = [...container.children];
					const dropIndex = all.indexOf(column);
					const dragIndex = all.indexOf(draggedItem);
					if (dragIndex < dropIndex) {
						column.after(draggedItem);
					} else {
						column.before(draggedItem);
					}
				}
			});
		});
	});
}
