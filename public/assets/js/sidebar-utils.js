function displayValue(value) {
    return value === undefined || value === null || value === '' ? '-' : value;
}

function escapeHtml(unsafe) {
    if (unsafe === undefined || unsafe === null) return '';
    return String(unsafe).replace(/[&<>"]+/g, function (s) {
        switch (s) {
            case '&': return '&amp;';
            case '<': return '&lt;';
            case '>': return '&gt;';
            case '"': return '&quot;';
            default: return s;
        }
    });
}

function renderStatusBadge(status) {
    if (status === undefined || status === null || status === '') return '-';
    const s = String(status);
    let cls = 'bg-secondary';
    if (s === 'Active' || s === 'Available') cls = 'bg-success';
    else if (s === 'Inactive' || s === 'Unavailable') cls = 'bg-danger';
    else if (s === 'Occupied' || s === 'Reserved') cls = 'bg-warning';
    else if (s === 'Maintenance') cls = 'bg-dark';

    return `<span class="badge ${cls}">${escapeHtml(s)}</span>`;
}

function renderEmptyTableRow(columnCount) {
    const cells = Array(columnCount).fill('<td class="text-center">-</td>').join('');
    return `<tr>${cells}</tr>`;
}

function paginateArray(items, currentPage = 1, perPage = 10) {
    const totalItems = Array.isArray(items) ? items.length : 0;
    const totalPages = Math.max(1, Math.ceil(totalItems / perPage));
    currentPage = Math.min(Math.max(1, Number(currentPage) || 1), totalPages);
    const start = (currentPage - 1) * perPage;
    const pageItems = Array.isArray(items) ? items.slice(start, start + perPage) : [];

    return {
        items: pageItems,
        currentPage,
        totalPages,
        totalItems
    };
}

function renderPaginationControls(currentPage, totalPages) {
    if (totalPages <= 1) {
        return '';
    }

    const pageButtons = [];
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, startPage + 4);

    pageButtons.push(`
        <li class="page-item${currentPage === 1 ? ' disabled' : ''}">
            <button type="button" class="page-link" data-page="${currentPage - 1}">Previous</button>
        </li>
    `);

    for (let page = startPage; page <= endPage; page++) {
        pageButtons.push(`
            <li class="page-item${page === currentPage ? ' active' : ''}">
                <button type="button" class="page-link" data-page="${page}">${page}</button>
            </li>
        `);
    }

    pageButtons.push(`
        <li class="page-item${currentPage === totalPages ? ' disabled' : ''}">
            <button type="button" class="page-link" data-page="${currentPage + 1}">Next</button>
        </li>
    `);

    return `
        <nav aria-label="Table pagination">
            <ul class="pagination justify-content-end mb-0">
                ${pageButtons.join('')}
            </ul>
        </nav>
    `;
}

const tableStates = {};

function getTableState(tableSelector, currentPage, perPage) {
    if (!tableStates[tableSelector]) {
        tableStates[tableSelector] = {
            currentPage: currentPage || 1,
            perPage: perPage || 10,
            sortKey: '',
            sortDirection: 'asc'
        };
    }

    const state = tableStates[tableSelector];
    state.currentPage = currentPage || state.currentPage || 1;
    state.perPage = state.perPage || perPage || 10;

    return state;
}

function normalizeSortValue(value) {
    return value === undefined || value === null ? '' : String(value).trim();
}

function getSortValue(row, key) {
    if (typeof key === 'function') {
        return key(row);
    }

    return row[key];
}

function sortTableData(data, sortKey, sortDirection) {
    if (!sortKey || !Array.isArray(data)) {
        return Array.isArray(data) ? data.slice() : [];
    }

    return data.slice().sort(function(a, b) {
        const first = normalizeSortValue(getSortValue(a, sortKey));
        const second = normalizeSortValue(getSortValue(b, sortKey));
        const result = first.localeCompare(second, undefined, { numeric: true, sensitivity: 'base' });

        return sortDirection === 'asc' ? result : -result;
    });
}

function setupSortableHeaders(tableSelector, sortColumns, state) {
    const table = $(tableSelector).closest('table');

    if (!table.length || !Array.isArray(sortColumns) || sortColumns.length === 0) {
        return;
    }

    sortColumns.forEach(function(column) {
        const th = table.find('thead th').eq(column.index);
        if (!th.length) {
            return;
        }

        if (!th.find('.table-sort-btn').length) {
            const label = th.text().trim();
            th.html(`
                <button type="button" class="table-sort-btn" data-sort-index="${column.index}">
                    ${escapeHtml(label)}
                    <span class="table-sort-indicator" data-sort-index="${column.index}"></span>
                </button>
            `);
        }
    });

    table.find('.table-sort-btn').off('click.tableSort').on('click.tableSort', function() {
        const columnIndex = Number($(this).attr('data-sort-index'));
        const column = sortColumns.find(function(item) {
            return item.index === columnIndex;
        });

        if (!column) {
            return;
        }

        if (state.sortKey === column.key) {
            state.sortDirection = state.sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            state.sortKey = column.key;
            state.sortDirection = 'asc';
        }

        state.currentPage = 1;
        renderPaginatedTable({
            data: state.data,
            tableSelector: tableSelector,
            currentPage: state.currentPage,
            perPage: state.perPage,
            renderRow: state.renderRow,
            sortColumns: sortColumns
        });
    });

    table.find('.table-sort-indicator').text('');

    if (state.sortKey) {
        const activeColumn = sortColumns.find(function(column) {
            return column.key === state.sortKey;
        });

        if (activeColumn) {
            table.find(`.table-sort-indicator[data-sort-index="${activeColumn.index}"]`).text(state.sortDirection === 'asc' ? '^' : 'v');
        }
    }
}

function ensurePaginationContainer(tableSelector) {
    const table = $(tableSelector);
    const card = table.closest('.ams-card, .card');
    let container;

    if (card.length) {
        container = card.find('.pagination-container');
        if (!container.length) {
            card.append('<div class="pagination-container p-3"></div>');
            container = card.find('.pagination-container');
        }
        return container;
    }

    container = table.next('.pagination-container');
    if (!container.length) {
        container = $('<div class="pagination-container p-3"></div>');
        table.after(container);
    }

    return container;
}

function renderPaginatedTable({ data, tableSelector, currentPage = 1, perPage = 10, renderRow, sortColumns = [] }) {
    const state = getTableState(tableSelector, currentPage, perPage);
    state.data = data;
    state.renderRow = renderRow;

    setupSortableHeaders(tableSelector, sortColumns, state);

    const sortedData = sortTableData(data, state.sortKey, state.sortDirection);
    const pagination = paginateArray(sortedData, state.currentPage, state.perPage);
    state.currentPage = pagination.currentPage;

    let rows = pagination.items.map(renderRow).join('');

    if (!rows) {
        rows = renderEmptyTableRow($(tableSelector).closest('table').find('thead th').length);
    }

    $(tableSelector).html(rows);

    const startItem = pagination.totalItems === 0 ? 0 : ((pagination.currentPage - 1) * state.perPage) + 1;
    const endItem = Math.min(pagination.currentPage * state.perPage, pagination.totalItems);
    const paginationHtml = renderPaginationControls(pagination.currentPage, pagination.totalPages);
    const container = ensurePaginationContainer(tableSelector);
    container.html(`
        <div class="table-controls">
            <div class="table-controls-left">
                <label class="table-entries-label">
                    Entries
                    <select class="form-select form-select-sm table-entries-select">
                        <option value="10"${state.perPage === 10 ? ' selected' : ''}>10</option>
                        <option value="25"${state.perPage === 25 ? ' selected' : ''}>25</option>
                        <option value="50"${state.perPage === 50 ? ' selected' : ''}>50</option>
                        <option value="100"${state.perPage === 100 ? ' selected' : ''}>100</option>
                    </select>
                </label>
                <div class="table-page-info">${startItem}-${endItem} of ${pagination.totalItems} entries</div>
            </div>
            ${paginationHtml}
        </div>
    `);

    container.find('.table-entries-select').on('change', function() {
        state.perPage = Number(this.value) || 10;
        state.currentPage = 1;
        renderPaginatedTable({ data, tableSelector, currentPage: state.currentPage, perPage: state.perPage, renderRow, sortColumns });
    });

    container.find('.page-link').on('click', function(event) {
        event.preventDefault();
        const page = Number($(this).attr('data-page'));
        if (!isNaN(page) && page >= 1 && page <= pagination.totalPages && page !== pagination.currentPage) {
            state.currentPage = page;
            renderPaginatedTable({ data, tableSelector, currentPage: page, perPage: state.perPage, renderRow, sortColumns });
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // Rotate chevrons when collapse opens/closes
    document.querySelectorAll('.chev').forEach(function(ch) {
        const targetId = ch.getAttribute('data-target');
        if (!targetId) return;
        const collapseEl = document.getElementById(targetId);
        if (!collapseEl) return;

        collapseEl.addEventListener('show.bs.collapse', function() {
            ch.classList.add('rotated');
        });
        collapseEl.addEventListener('hide.bs.collapse', function() {
            ch.classList.remove('rotated');
        });
    });

    // Small CSS injection for rotation
    const style = document.createElement('style');
    style.innerHTML = '.chev{transition:transform .2s ease;} .chev.rotated{transform:rotate(180deg);}.table-controls{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}.table-controls-left{display:flex;align-items:center;gap:10px;flex-wrap:wrap}.table-entries-label{font-size:13px;color:#434653;display:flex;align-items:center;gap:6px;margin:0}.table-entries-select{width:78px}.table-page-info{font-size:13px;color:#434653}.table-sort-btn{border:0;background:transparent;color:inherit;font:inherit;font-weight:inherit;padding:0;text-align:left;cursor:pointer;display:inline-flex;align-items:center;gap:4px}.table-sort-indicator{display:inline-block;min-width:10px;font-size:10px;line-height:1}';
    document.head.appendChild(style);

    // Populate `data-label` on each nav-link for custom collapsed tooltips
    document.querySelectorAll('.sidebar .nav-link').forEach(function(a) {
        try {
            const labelEl = a.querySelector('span:not(.badge)');
            let label = '';
            if (labelEl) label = labelEl.innerText.trim();
            if (!label) label = a.getAttribute('title') || a.textContent.trim();
            if (label) {
                a.setAttribute('data-label', label);
                if (!a.getAttribute('title')) a.setAttribute('title', label);
            }
        } catch (e) { /* ignore */ }
    });

    // When sidebar is collapsed, clicking a parent collapse-link opens a floating submenu
    document.querySelectorAll('.sidebar .nav-link[data-bs-toggle="collapse"]').forEach(function(link) {
        link.addEventListener('click', function(ev) {
            const sidebar = document.querySelector('.sidebar');
            if (!sidebar || !sidebar.classList.contains('collapsed')) return; // default behaviour when expanded
            ev.preventDefault();

            // find target collapse element
            const href = link.getAttribute('href') || link.getAttribute('data-target');
            const targetId = (href || '').replace('#','');
            if (!targetId) return;
            const collapseEl = document.getElementById(targetId);
            if (!collapseEl) return;

            // remove any existing floating submenu
            const existing = document.querySelector('.floating-submenu');
            if (existing) existing.remove();

            // clone the inner nav list and show as floating popup
            const clone = collapseEl.cloneNode(true);
            clone.classList.remove('collapse','collapsing');
            clone.classList.add('floating-submenu');
            clone.removeAttribute('id');

            // position near the clicked link
            const rect = link.getBoundingClientRect();
            const left = rect.right + 8;
            const top = rect.top;
            clone.style.position = 'fixed';
            clone.style.left = left + 'px';
            clone.style.top = top + 'px';
            clone.style.zIndex = 2000;

            document.body.appendChild(clone);

            // close when clicking outside
            function onDocClick(e) {
                if (!clone.contains(e.target) && !link.contains(e.target)) {
                    clone.remove();
                    document.removeEventListener('click', onDocClick);
                }
            }
            // delay to avoid immediately closing from the current click
            setTimeout(function() { document.addEventListener('click', onDocClick); }, 0);
        });
    });

    // Update badges for arrivals/departures
    function updateTxBadges() {
        // arrivals
        fetch('api/transactions/index.php/type/arrival')
            .then(r => r.json())
            .then(data => {
                const count = Array.isArray(data) ? data.length : 0;
                const el = document.getElementById('badge-arrivals');
                if (el) el.textContent = count;
            }).catch(()=>{});

        // departures
        fetch('api/transactions/index.php/type/departure')
            .then(r => r.json())
            .then(data => {
                const count = Array.isArray(data) ? data.length : 0;
                const el = document.getElementById('badge-departures');
                if (el) el.textContent = count;
            }).catch(()=>{});
    }

    window.updateTxBadges = updateTxBadges;
    updateTxBadges();
    // refresh every 30 seconds
    setInterval(updateTxBadges, 30000);
});
