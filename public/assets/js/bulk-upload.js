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
