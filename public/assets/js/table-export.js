(function () {
    function cleanText(value) {
        return String(value || '').replace(/\s+/g, ' ').trim();
    }

    function safeFileName(value) {
        return cleanText(value).toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') || 'export';
    }

    function shouldSkipColumn(th, index) {
        const label = cleanText(th.textContent).toLowerCase();
        if (label === 'action' || label === 'actions') {
            return true;
        }

        return index === 0 && th.querySelector('input[type="checkbox"]');
    }

    function getTableData(table) {
        const headers = Array.from(table.querySelectorAll('thead th'));
        const skippedIndexes = headers
            .map(function (th, index) { return shouldSkipColumn(th, index) ? index : -1; })
            .filter(function (index) { return index >= 0; });

        const columns = headers
            .filter(function (_, index) { return !skippedIndexes.includes(index); })
            .map(function (th) { return cleanText(th.textContent); });

        const rows = Array.from(table.querySelectorAll('tbody tr'))
            .filter(function (tr) {
                return !tr.querySelector('td[colspan]') && cleanText(tr.textContent) !== '';
            })
            .map(function (tr) {
                return Array.from(tr.children)
                    .filter(function (_, index) { return !skippedIndexes.includes(index); })
                    .map(function (td) { return cleanText(td.textContent); });
            });

        return { columns: columns, rows: rows };
    }

    function downloadBlob(content, mimeType, fileName) {
        const blob = new Blob([content], { type: mimeType });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = fileName;
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(url);
    }

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, function (char) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[char];
        });
    }

    function toHtmlTable(title, data) {
        const header = data.columns.map(function (column) {
            return '<th>' + escapeHtml(column) + '</th>';
        }).join('');

        const rows = data.rows.map(function (row) {
            return '<tr>' + row.map(function (cell) {
                return '<td>' + escapeHtml(cell) + '</td>';
            }).join('') + '</tr>';
        }).join('');

        return '<h2>' + escapeHtml(title) + '</h2><table><thead><tr>' + header + '</tr></thead><tbody>' + rows + '</tbody></table>';
    }

    function exportCsv(title, data) {
        const lines = [data.columns].concat(data.rows).map(function (row) {
            return row.map(function (cell) {
                return '"' + String(cell).replace(/"/g, '""') + '"';
            }).join(',');
        });

        downloadBlob('\uFEFF' + lines.join('\r\n'), 'text/csv;charset=utf-8;', safeFileName(title) + '.csv');
    }

    function exportExcel(title, data) {
        const html = '<html><head><meta charset="utf-8"></head><body>' + toHtmlTable(title, data) + '</body></html>';
        downloadBlob(html, 'application/vnd.ms-excel;charset=utf-8;', safeFileName(title) + '.xls');
    }

    function exportWord(title, data) {
        const html = '<html><head><meta charset="utf-8"></head><body>' + toHtmlTable(title, data) + '</body></html>';
        downloadBlob(html, 'application/msword;charset=utf-8;', safeFileName(title) + '.doc');
    }

    function exportPdf(title, data) {
        const printWindow = window.open('', '_blank');
        if (!printWindow) {
            alert('Please allow pop-ups to export PDF.');
            return;
        }

        printWindow.document.write(
            '<!doctype html><html><head><meta charset="utf-8"><title>' + escapeHtml(title) + '</title>' +
            '<style>body{font-family:Arial,sans-serif;margin:24px;color:#111827}h2{font-size:20px;margin:0 0 16px}table{border-collapse:collapse;width:100%;font-size:12px}th,td{border:1px solid #d1d5db;padding:6px 8px;text-align:left;vertical-align:top}th{background:#eef2ff}</style>' +
            '</head><body>' + toHtmlTable(title, data) + '</body></html>'
        );
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
    }

    function addExportToolbar(table) {
        if (table.dataset.exportReady === '1') {
            return;
        }

        table.dataset.exportReady = '1';
        const title = table.dataset.exportTitle || document.title || 'Table Export';
        const dropdownId = 'tableExportDropdown-' + Math.random().toString(36).slice(2);
        const toolbar = document.createElement('div');
        toolbar.className = 'table-export-toolbar';
        toolbar.style.cssText = 'display:flex;justify-content:flex-end;padding:10px 16px;border-bottom:1px solid #e5e7eb;background:#fff;';

        const dropdown = document.createElement('div');
        dropdown.className = 'dropdown';

        const toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.id = dropdownId;
        toggle.className = 'btn btn-outline-primary btn-sm dropdown-toggle';
        toggle.setAttribute('data-bs-toggle', 'dropdown');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.textContent = 'Export Data';

        const menu = document.createElement('ul');
        menu.className = 'dropdown-menu dropdown-menu-end';
        menu.setAttribute('aria-labelledby', dropdownId);

        [
            ['MS-Excel', exportExcel],
            ['MS-Word', exportWord],
            ['CSV', exportCsv],
            ['PDF', exportPdf]
        ].forEach(function (item) {
            const menuItem = document.createElement('li');
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'dropdown-item';
            button.textContent = item[0];
            button.addEventListener('click', function () {
                const data = getTableData(table);
                if (!data.rows.length) {
                    alert('No table data to export.');
                    return;
                }
                item[1](title, data);
            });
            menuItem.appendChild(button);
            menu.appendChild(menuItem);
        });

        dropdown.appendChild(toggle);
        dropdown.appendChild(menu);
        toolbar.appendChild(dropdown);

        const scrollWrapper = table.parentElement;
        if (scrollWrapper && scrollWrapper.parentElement) {
            scrollWrapper.parentElement.insertBefore(toolbar, scrollWrapper);
        } else if (table.parentElement) {
            table.parentElement.insertBefore(toolbar, table);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('table[data-export-title]').forEach(addExportToolbar);
    });
})();
