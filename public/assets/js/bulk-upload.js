function startBulkUpload() {
    const fileInput = document.getElementById('bulkUploadFile');
    const file = fileInput.files[0];

    if (!file) {
        swalError('Please select a file to upload.');
        return;
    }

    if (!file.name.toLowerCase().endsWith('.csv')) {
        swalError('Please select a CSV file.');
        return;
    }

    const formData = new FormData();
    formData.append('file', file);

    document.getElementById('uploadProgress').style.display = 'block';
    document.getElementById('uploadResults').style.display = 'none';
    document.getElementById('bulkUploadBtn').disabled = true;

    $.ajax({
        url: 'api/employees/bulk-upload.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        xhr: function() {
            const xhr = new window.XMLHttpRequest();
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    document.getElementById('progressBar').style.width = percentComplete + '%';
                    document.getElementById('progressText').textContent = 'Uploading: ' + Math.round(percentComplete) + '%';
                }
            }, false);
            return xhr;
        },
        success: function(data) {
            const response = typeof data === 'string' ? JSON.parse(data) : data;
            document.getElementById('uploadProgress').style.display = 'none';
            document.getElementById('bulkUploadBtn').disabled = false;

            if (response.success) {
                const results = response.results;
                const hasErrors = results.errors && results.errors.some(item => item.error);
                const allProcessed = results.total > 0 && results.success === results.total && !hasErrors;
                const noneProcessed = results.total > 0 && results.success === 0;
                const summaryColor = allProcessed ? '#15803d' : (noneProcessed ? '#b91c1c' : '#b45309');
                const summaryLabel = allProcessed ? 'Upload complete' : (noneProcessed ? 'Upload incomplete' : 'Upload completed with issues');

                let resultsHtml = `
                    <div style="margin-bottom:12px; font-weight:700; color:${summaryColor};">
                        ${summaryLabel}: ${results.success}/${results.total} records processed.
                    </div>
                `;

                if (results.errors && results.errors.length > 0) {
                    const hasDepartmentErrors = results.errors.some(item => item.error && item.error.includes('Department ') && item.error.includes(' not found.'));

                    if (hasDepartmentErrors) {
                        resultsHtml += `
                            <div style="margin-bottom:10px; padding:10px; background:#fff7ed; border:1px solid #fed7aa; border-radius:6px; color:#9a3412;">
                                Some departments in your CSV do not exist yet. Create them first in the Departments page, or update the CSV to use an existing department name.
                            </div>
                        `;
                    }

                    const hasHeaderErrors = results.errors.some(item => item.error && item.error.includes('CSV headers missing or incorrect'));

                    if (hasHeaderErrors) {
                        resultsHtml += `
                            <div style="margin-bottom:10px; padding:10px; background:#fff7ed; border:1px solid #fed7aa; border-radius:6px; color:#9a3412;">
                                Check the first row of your CSV. It must include these columns: Employee ID, Full Name, Gender, Department. The easiest fix is to download the template and paste your employee rows under its header.
                            </div>
                        `;
                    }

                    resultsHtml += '<div style="margin-bottom:8px; font-weight:600; color:#121c28;">Details:</div>';
                    results.errors.slice(0, 20).forEach(item => {
                        if (item.status) {
                            resultsHtml += `<div style="color:#15803d; margin-bottom:4px;">Row ${item.row}: ${item.employee_code} - ${item.status}</div>`;
                        } else if (item.error) {
                            resultsHtml += `<div style="color:#b91c1c; margin-bottom:4px;">Row ${item.row}: ${item.error}</div>`;
                        }
                    });

                    if (results.errors.length > 20) {
                        resultsHtml += `<div style="color:#737784; margin-top:8px;">... and ${results.errors.length - 20} more</div>`;
                    }
                }

                document.getElementById('uploadResults').style.display = 'block';
                document.getElementById('uploadResults').innerHTML = resultsHtml;
                fileInput.value = '';
                loadEmployees();

                if (allProcessed) {
                    setTimeout(() => {
                        $('#bulkUploadModal').modal('hide');
                        swalSuccess(`Successfully uploaded ${results.success} employees.`);
                    }, 1500);
                }
            } else {
                swalError('Upload failed: ' + response.message);
                document.getElementById('uploadProgress').style.display = 'none';
                fileInput.value = '';
            }
        },
        error: function(xhr) {
            document.getElementById('uploadProgress').style.display = 'none';
            document.getElementById('bulkUploadBtn').disabled = false;
            fileInput.value = '';
            const error = xhr.responseJSON?.message || xhr.responseText || 'Upload failed';
            swalError('Error: ' + error);
        }
    });
}

$(function() {
    $('#bulkUploadModal').on('hidden.bs.modal', function() {
        document.getElementById('bulkUploadFile').value = '';
        document.getElementById('uploadProgress').style.display = 'none';
        document.getElementById('uploadResults').style.display = 'none';
        document.getElementById('uploadResults').innerHTML = '';
        document.getElementById('progressBar').style.width = '0%';
        document.getElementById('progressText').textContent = 'Uploading...';
        document.getElementById('bulkUploadBtn').disabled = false;
    });
});

// Presentation-only enhancements for the existing bulk upload control.
// This does not participate in the upload request or server-side validation.
$(function() {
    const fileInput = document.getElementById('bulkUploadFile');
    const dropzone = document.getElementById('bulkDropzone');
    const emptyState = document.getElementById('bulkDropzoneEmpty');
    const selectedState = document.getElementById('bulkFileSelected');
    const clearButton = document.getElementById('bulkClearFile');
    const preview = document.getElementById('bulkPreview');
    const search = document.getElementById('bulkPreviewSearch');
    const filter = document.getElementById('bulkPreviewFilter');
    const pageSize = document.getElementById('bulkPreviewPageSize');
    const head = document.getElementById('bulkPreviewHead');
    const body = document.getElementById('bulkPreviewBody');
    const count = document.getElementById('bulkPreviewCount');
    let previewHeaders = [];
    let previewRows = [];

    if (!fileInput || !dropzone) return;

    const formatSize = bytes => bytes < 1024 * 1024 ? `${Math.max(1, Math.round(bytes / 1024))} KB` : `${(bytes / (1024 * 1024)).toFixed(2)} MB`;
    const escapeHtml = value => String(value ?? '').replace(/[&<>'"]/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' })[char]);
    const parseCsvForPreview = text => {
        const records = []; let row = []; let value = ''; let quoted = false;
        for (let i = 0; i < text.length; i++) {
            const char = text[i];
            if (char === '"') { if (quoted && text[i + 1] === '"') { value += char; i++; } else quoted = !quoted; }
            else if (char === ',' && !quoted) { row.push(value.trim()); value = ''; }
            else if ((char === '\n' || char === '\r') && !quoted) { if (char === '\r' && text[i + 1] === '\n') i++; row.push(value.trim()); if (row.some(cell => cell !== '')) records.push(row); row = []; value = ''; }
            else value += char;
        }
        row.push(value.trim()); if (row.some(cell => cell !== '')) records.push(row);
        return records;
    };
    const renderPreview = () => {
        const query = search.value.trim().toLowerCase();
        // Current upload logic validates only on import; filters intentionally affect display only.
        const requestedStatus = filter.value;
        const rows = previewRows.filter(row => {
            const matchesSearch = !query || row.some(cell => cell.toLowerCase().includes(query));
            const matchesStatus = requestedStatus === 'all' || requestedStatus === 'valid';
            return matchesSearch && matchesStatus;
        });
        const limit = pageSize.value === 'all' ? rows.length : Number(pageSize.value);
        head.innerHTML = `<tr>${previewHeaders.map(header => `<th>${escapeHtml(header)}</th>`).join('')}</tr>`;
        body.innerHTML = rows.slice(0, limit).map(row => `<tr>${previewHeaders.map((_, index) => `<td>${escapeHtml(row[index] || '—')}</td>`).join('')}</tr>`).join('') || `<tr><td colspan="${Math.max(previewHeaders.length, 1)}" style="text-align:center; color:#737784;">No preview rows match this filter.</td></tr>`;
        count.textContent = `${Math.min(rows.length, limit)} of ${rows.length} records shown`;
    };
    const clearPresentation = () => {
        emptyState.style.display = '';
        selectedState.classList.remove('is-visible');
        preview.classList.remove('is-visible');
        previewRows = []; previewHeaders = [];
        search.value = ''; filter.value = 'all'; pageSize.value = '25';
    };
    const showFile = file => {
        if (!file) { clearPresentation(); return; }
        emptyState.style.display = 'none';
        selectedState.classList.add('is-visible');
        document.getElementById('bulkFileName').textContent = file.name;
        document.getElementById('bulkFileSize').textContent = formatSize(file.size);
        document.getElementById('bulkPreviewFileName').textContent = file.name;
        document.getElementById('bulkPreviewMeta').textContent = `Selected ${new Date().toLocaleString()} · Preview is read-only; server validation occurs during import.`;
        // Limit only the visual preview read; the existing upload still receives the untouched file.
        if (!file.name.toLowerCase().endsWith('.csv') || file.size > 10 * 1024 * 1024) { preview.classList.remove('is-visible'); return; }
        const reader = new FileReader();
        reader.onload = event => {
            const records = parseCsvForPreview(event.target.result);
            previewHeaders = records.shift() || [];
            previewRows = records;
            document.getElementById('bulkTotalRecords').textContent = previewRows.length;
            document.getElementById('bulkReadyRecords').textContent = previewRows.length;
            document.getElementById('bulkWarningRecords').textContent = '—';
            document.getElementById('bulkErrorRecords').textContent = '—';
            preview.classList.toggle('is-visible', previewHeaders.length > 0);
            renderPreview();
        };
        reader.readAsText(file);
    };

    fileInput.addEventListener('change', () => showFile(fileInput.files[0]));
    clearButton.addEventListener('click', () => { fileInput.value = ''; clearPresentation(); fileInput.focus(); });
    ['dragenter', 'dragover'].forEach(eventName => dropzone.addEventListener(eventName, event => { event.preventDefault(); dropzone.classList.add('is-dragging'); }));
    ['dragleave', 'drop'].forEach(eventName => dropzone.addEventListener(eventName, event => { event.preventDefault(); dropzone.classList.remove('is-dragging'); }));
    dropzone.addEventListener('drop', event => {
        const droppedFile = event.dataTransfer.files[0];
        if (!droppedFile) return;
        const transfer = new DataTransfer(); transfer.items.add(droppedFile); fileInput.files = transfer.files;
        fileInput.dispatchEvent(new Event('change', { bubbles: true }));
    });
    [search, filter, pageSize].forEach(control => control.addEventListener('input', renderPreview));
    $('#bulkUploadModal').on('hidden.bs.modal', clearPresentation);

    const uploadButton = document.getElementById('bulkUploadBtn');
    const progress = document.getElementById('uploadProgress');
    const syncLoadingPresentation = () => {
        const uploading = uploadButton.disabled && progress.style.display === 'block';
        document.getElementById('bulkUploadModal').classList.toggle('bulk-uploading', uploading);
        uploadButton.innerHTML = uploading ? '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Importing...' : 'Import Employees';
    };
    new MutationObserver(syncLoadingPresentation).observe(uploadButton, { attributes: true, attributeFilter: ['disabled'] });
    new MutationObserver(syncLoadingPresentation).observe(progress, { attributes: true, attributeFilter: ['style'] });
});
