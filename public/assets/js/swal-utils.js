function swalSuccess(message, title = 'Success') {
    Swal.fire({
        icon: 'success',
        title: title,
        text: message,
        confirmButtonText: 'OK'
    });
}

function swalError(message, title = 'Error') {
    Swal.fire({
        icon: 'error',
        title: title,
        text: message,
        confirmButtonText: 'OK'
    });
}

function swalInfo(message, title = 'Info') {
    Swal.fire({
        icon: 'info',
        title: title,
        text: message,
        confirmButtonText: 'OK'
    });
}

function swalConfirm(message, onConfirm, title = 'Are you sure?') {
    Swal.fire({
        title: title,
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes',
        cancelButtonText: 'No'
    }).then((result) => {
        if (result.isConfirmed) {
            onConfirm();
        }
    });
}
