function showErrorMessage(message) {
    $('#errorMessage').text(message);
    $('#errorContainer')
        .removeClass('success')
        .addClass('error')
        .fadeIn();
    setTimeout(function () {
        $('#errorContainer').fadeOut();
    }, 5000);
}

function showSuccessMessage(message) {
    $('#errorMessage').text(message);
    $('#errorContainer')
        .removeClass('error')
        .addClass('success')
        .fadeIn();
    setTimeout(function () {
        $('#errorContainer').fadeOut();
    }, 5000);
}

$(document).ready(function () {
    $('#closeError').on('click', function () {
        $('#errorContainer').fadeOut();
    });
});